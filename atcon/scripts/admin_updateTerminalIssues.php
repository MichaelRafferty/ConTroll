<?php
// library AJAX Processor: admin_updateTerminalIssues.php
// ConTroll Registration System
// Author: Syd Weinstein
// Try to fix the transaction passed by querying the database and the credit card processor
// when done, refresh the list of terminal payments not yet completed/cancelled

require_once('../lib/base.php');
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/term__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'manager';
$con = get_conf('con');
$conid = $con['id'];
load_cc_procs();
load_term_procs();
$terminal = getSessionVar('terminal');
if ($terminal) {
    $name = $terminal['name'];
} else {
    $name = 'None';
}

logInit(getConfValue('log', 'term'));


$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'update') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if ($_POST && $_POST['transid']) {
    $transid = $_POST['transid'];
} else {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

// get the information for this transaction
$issueSQL = <<<EOS
SELECT t.id, t.paymentId, t.paymentStatus, t.checkoutId, t.create_date, t.complete_date, t.perid, t.userid, t.withtax, t.paid, 
       t.type, t.orderId, t.lastUpdate, TIMESTAMPDIFF(MINUTE, t.lastUpdate, NOW()) as minutes,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
       y.id AS payTableId, IFNULL(y.status, '') AS cardStatus, IFNULL(y.paymentId, '') AS cardPaymentId
FROM transaction t
JOIN perinfo p ON t.perid = p.id
LEFT OUTER JOIN payments y ON t.id = y.transid AND y.type NOT IN ('coupon', 'discount')
WHERE t.conid = ? AND t.id = ? AND (t.checkoutId IS NOT NULL AND IFNULL(t.paymentStatus,'') NOT IN ('COMPLETED', 'CANCELED')) 
   OR IFNULL(y.status,'') = 'APPROVED'
ORDER BY minutes DESC;
EOS;

$issueR = dbSafeQuery($issueSQL, 'ii', array($conid, $transid));
if ($issueR === false || $issueR->num_rows != 1) {
    $response['error'] = 'Query failed, seek assistance';
    ajaxSuccess($response);
    return;
}

$issue = $issueR->fetch_assoc();
$issueR->free();

$message = '';
// ok, the issue under question is in $issue, first look to see if we can change the credit card status
if ($issue['cardStatus'] != '' && $issue['cardStatus'] != 'COMPLETED' && $issue['cardPaymentId'] != '') {
    // it has a card payment id and a card status, it's just not 'COMPLETED', poll the payment record and if it's now completed
    $payment = cc_getPayment('issue', $issue['cardPaymentId'], true);
    if ($payment['status'] == 'COMPLETED') {
        // it's now complete, update the payment record with the status and the receipt URL
        $updPaySQL = <<<EOS
UPDATE payments
SET receipt_url = ?, status = ?
WHERE id = ?;
EOS;
        $numUpd = dbSafeCmd($updPaySQL, 'ssi', array($payment['receipt_url'], $payment['status'], $issue['payTableId']));
        $message .= $numUpd . ' receipt url/card payment statuses updated<br/>';
    }
}

// now that the credit card approval/url is fixed, look at the terminal status
if ($issue['checkoutId'] != '' && $issue['paymentStatus'] != 'COMPLETED' && $issue['paymentId'] != 'CANCELED') {
    $message .= $issue['id'] . " needs to be polled<br/>";
    // get the current status
    $checkout = term_getPayStatus($name, $issue['checkoutId'], true);
    $message .= "got status of " . $checkout['status'] . "<br/>";
    switch ($checkout['status']) {
        case 'CANCELED':
            // update issue to cancelled.  Need to mark transaction paymentStatus to canceled.
            $updSQL = <<<EOS
UPDATE transaction
SET paymentStatus = 'CANCELED'
WHERE id = ?;
EOS;
            $numUpd = dbSafeCmd($updSQL, 'i', array ($issue['id']));
            $message .= $numUpd . ' transaction marked CANCELED due to ' . $checkout['cancel_reason'] . '<br/>';
            break;
        case 'PENDING':
        case 'IN_PROGRESS':
        case 'CANCEL_REQUESTED':
            // it still in progress, let square complete
            $message .= 'Square reports the transaction is still in process, please let it complete and try again in a few minutes.<br/>';
            break;

        case 'COMPLETED':
            $orderId = $issue['orderId'];
            $orderObj = cc_fetchOrder('admin_updateTerminalIssues', $orderId, true);
            $order = json_decode(json_encode($orderObj), true)['order'];
            $orderSource = substr($order['source']['name'], -3);
            $orderType = $orderSource == 'con' ? 'reg' : 'art';
            $paymentIds = $checkout['payment_ids'];
            if (count($paymentIds) > 1) {
                $message .= 'terminal: returned more than one paymentId<br/>';
                break;
            }
            $paymentId = $paymentIds[0];

            $payment = cc_getPayment('admin_updateTerminalIssues', $paymentId, true);
            if (array_key_exists('receipt_url', $payment))
                $receiptUrl = $payment['receipt_url'];
            else
                $receiptUrl = null;
            $status = $payment['status'];
            $message .= "Completing order $orderId of type $orderType and payment $paymentId of status $status with a receipt of $receiptUrl" . "<br/>";
            if ($orderType == 'reg')
                $message .= completeReg($issue['id'], $checkout, $order, $payment);
            else
                $message .= completeArt($issue['id'], $checkout, $order, $payment);
            break;

        default:
            $message .= "Unable to correct this status value currently, see assistance <br/>";
    }
}

// now get the remaining issues
$issueSQL = <<<EOS
SELECT t.id, t.paymentId, t.paymentStatus, t.checkoutId, t.create_date, t.complete_date, t.perid, t.userid, t.withtax, t.paid, 
       t.type, t.orderId, t.lastUpdate, TIMESTAMPDIFF(MINUTE, t.lastUpdate, NOW()) as minutes,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
       y.id AS payTableId, y.status AS cardStatus, y.paymentId AS cardPaymentId
FROM transaction t
JOIN perinfo p ON t.perid = p.id
LEFT OUTER JOIN payments y ON t.id = y.transid AND y.type NOT IN ('coupon', 'discount')
WHERE t.conid = ? AND (t.checkoutId IS NOT NULL AND IFNULL(t.paymentStatus,'') NOT IN ('COMPLETED', 'CANCELED')) 
   OR IFNULL(y.status,'') = 'APPROVED'
ORDER BY minutes DESC;
EOS;

$issueR = dbSafeQuery($issueSQL, 'i', array($conid));
if ($issueR === false) {
    $response['error'] = 'Query failed, seek assistance';
    ajaxSuccess($response);
    exit();
}

$issues = [];
$response['rows'] = $issueR->num_rows;
while ($issue = $issueR->fetch_assoc()) {
    $issues[] = $issue;
}
$message .= $response['rows'] . " payment issues found";
$issueR->free();
$response['issues'] = $issues;
$response['success'] = true;
$response['message'] = $message;
ajaxSuccess($response);


// Action Functions, one per payment order type
// Actions needed to complete an item of source Reg Cashier
function completeReg($master_tid, $checkout, $order, $payment) : string {
    $new_payment_desc = 'Need new_payment';
    $drow = null; // need $drow
    $couponPayment = null; // new coupon payment
    $couponDiscount = 0; // need coupon info
    $coupon = null; // need coupon
    $user_perid = getSessionVar('user');
    $discountAmt = 0; // need discount amount
    $cart_perinfo = []; // need to build from order items
    $message = '';

    // items from $payment
    $amt = $payment['total_money']['amount'] / 100;
    $taxAmt = $order['total_tax_money']['amount'] / 100;
    $preTaxAmt = $amt - $taxAmt;

    $approved_amt = $payment['approved_money']['amount'] / 100;
    $txTime = $payment['created_at'];
    $receiptNumber = $payment['receipt_number'];
    if (array_key_exists('receipt_url', $payment))
        $receiptUrl = $payment['receipt_url'];
    else
        $receiptUrl = null;
    $last4 = $payment['card_details']['card']['last_4'];
    $id = $payment['id'];
    $auth = $payment['card_details']['auth_result_code'];
    $nonce = $payment['card_details']['card']['fingerprint'];
    $status = $payment['status'];
    switch ($payment['source_type']) {
        case 'CARD':
            $paymentType = 'credit';
            break;
        case 'BANK_ACCOUNT':
            $paymentType = 'check';
            break;
        case 'CASH':
            $paymentType = 'cash';
            break;
        default:
            $paymentType = 'other';
    }

    $desc = $payment['application_details']['square_product'];
    if ($desc == '')
        $desc = $new_payment_desc;
    else
        $desc = mb_substr($desc . '/' . $new_payment_desc, 0, 64);

    if ($nonce == 'EXTERNAL')
        $nonceCode = $paymentType;
    else
        $nonceCode = $nonce;

    $complete = round($approved_amt, 2) == round($amt, 2);

    // now add the payment and process to which rows it applies
    $insPmtSQL = <<<EOS
INSERT INTO payments(transid, type,category, description, source, pretax, tax, amount, time, cc_approval_code, cashier, 
    cc, nonce, cc_txn_id, txn_time, receipt_url, receipt_id, userPerid, status, paymentId)
VALUES (?,?,'reg',?,'cashier',?,?,?,NOW(),?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
    $typestr = 'issdddsissssssiss';

    // coupon payment if it exist
    if ($couponPayment != null) {
        $paramarray = array ($master_tid, 'coupon', $couponPayment['desc'], $couponPayment['amt'], 0, $couponPayment['amt'], null, $user_perid,
            null, null, null, null, null, null, $user_perid, 'APPLIED', null);
        $message .= "\$new_pid = dbSafeInsert($insPmtSQL, $typestr, array(" . implode(',', $paramarray) . "));<br/>";
        //$new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);

        //if ($new_pid === false) {
        //   ajaxError('Error adding coupon payment to database');
        //    exit();
        //}
    }

    if ($drow != null) {
        $paramarray = array ($master_tid, 'discount', $drow['desc'], $drow['amt'], 0, $drow['amt'], null, $user_perid,
            null, null, null, null, null, null, $user_perid, 'APPLIED', null);
        $message .= "\$new_pid = dbSafeInsert($insPmtSQL, $typestr, array(" . implode(',', $paramarray) . '));<br/>';
        //$new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray

//        if ($new_pid === false) {
//            ajaxError('Error adding manager discount payment to database');
//            exit();
//        }
    }

    // now the main payment
    if ($amt > 0) {
        $paramarray = array ($master_tid, $paymentType, $desc, $preTaxAmt, $taxAmt, $approved_amt, $auth, $user_perid,
            $last4, $nonceCode, $id, $txTime, $receiptUrl, $receiptNumber, $user_perid, $status, $id);
        $message .= "\$new_pid = dbSafeInsert($insPmtSQL, $typestr, array(" . implode(',', $paramarray) . '));<br/>';
        //$new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray

//        if ($new_pid === false) {
//            ajaxError('Error adding payment to database');
//            exit();
//        }
    }

    $message .= '1 payment added<br/>';
    $updRegSql = <<<EOS
UPDATE reg
SET paid = ?, complete_trans = ?, status = ?, couponDiscount = ?, coupon = ?
WHERE id = ?;
EOS;
    $ptypestr = 'disdii';
    $index = 0;
// allocate pre-tax amount to regs
    $allocateAmt = $preTaxAmt;
    $allocateDiscount = $discountAmt;
    foreach ($cart_perinfo as $perinfo) {
        $cart_perinfo[$index]['rowpos'] = $index;
        unset($cart_perinfo[$index]['dirty']);
        $index++;
        foreach ($perinfo['memberships'] as $cart_row) {
            // Clear args to indicate if an update is needed
            $args = null;

            if ($cart_row['price'] == '')
                $cart_row['price'] = 0;
            if ((!array_key_exists('couponDiscount', $cart_row)) || $cart_row['couponDiscount'] == '')
                $cart_row['couponDiscount'] = 0;
            if ($cart_row['paid'] == '')
                $cart_row['paid'] = 0;
            if ((!array_key_exists('coupon', $cart_row)) || $cart_row['coupon'] == '')
                $cart_row['coupon'] = null;

            if ($cart_row['couponDiscount'] > 0) {
                $allocateDiscount -= $cart_row['couponDiscount'];
            } else {
                $unpaid = $cart_row['price'] - ($cart_row['couponDiscount'] + $cart_row['paid']);
                if ($unpaid > 0) {
                    // first the discount
                    $amt_disc = min($allocateDiscount, $unpaid);
                    if ($amt_disc > 0 && $cart_row['couponDiscount'] == 0) {
                        $cart_row['couponDiscount'] += $amt_disc;
                        if ($amt_disc == $unpaid) {
                            // row is now completely paid
                            $args = array ($cart_row['paid'], $master_tid, 'paid', $cart_row['couponDiscount'], $cart_row['coupon'], $cart_row['regid']);
                            $cart_row['status'] = 'paid';
                            $cart_row['tid2'] = $master_tid;
                        } else {
                            $args = array ($cart_row['paid'], null, $cart_row['status'], $cart_row['couponDiscount'], $cart_row['coupon'], $cart_row['regid']);
                        }
                        $allocateDiscount -= $amt_disc;
                    }
                }
            }

            // now the payment
            $unpaid = $cart_row['price'] - ($cart_row['couponDiscount'] + $cart_row['paid']);
            if ($unpaid > 0) {
                $amt_paid = min($allocateAmt, $unpaid);
                $cart_row['paid'] += $amt_paid;
                if ($amt_paid == $unpaid) {
                    // row is now completely paid
                    $args = array ($cart_row['paid'], $master_tid, 'paid', $cart_row['couponDiscount'], $cart_row['coupon'], $cart_row['regid']);
                    $cart_row['status'] = 'paid';
                    $cart_row['tid2'] = $master_tid;
                } else {
                    $args = array ($cart_row['paid'], null, $cart_row['status'], $cart_row['couponDiscount'], $cart_row['coupon'], $cart_row['regid']);
                }
                $allocateAmt -= $amt_paid;
            }
            // update the data in the system
            $cart_perinfo[$perinfo['index']]['memberships'][$cart_row['index']] = $cart_row;
            if ($args != null) {
                $message .= "\$upd_rows += dbSafeCmd($updRegSql, $ptypestr, array(" . implode(',', $args) . '));<br/>';
                //$upd_rows += dbSafeCmd($updRegSql, $ptypestr, $args);
            }
        }
    }

// if coupon is specified, mark transaction as having a coupon
    if ($coupon || $discountAmt > 0) {
        $updCompleteSQL = <<<EOS
UPDATE transaction
SET coupon = ?, couponDiscountCart = ?, couponDiscountReg = ?
WHERE id = ?;
EOS;
        if ($coupon == '')
            $coupon = null;

        $message .= "\$completed = dbSafeCmd($updCompleteSQL, 'iddi', array ($coupon, $couponDiscount + $discountAmt, 0, $master_tid))<br/>";
        //$completed = dbSafeCmd($updCompleteSQL, 'iddi', array ($coupon, $couponDiscount + $discountAmt, 0, $master_tid));
    }
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET paid = ?
WHERE id = ?;
EOS;
    $message .= "\$completed = dbSafeCmd($updCompleteSQL, 'di', array ($approved_amt, $master_tid))<br/>";
    //$completed = dbSafeCmd($updCompleteSQL, 'di', array ($approved_amt, $master_tid));

    $completed = 0;
    if ($complete) {
        // payment is in full, mark transaction complete
        $updCompleteSQL = <<<EOS
UPDATE transaction
SET complete_date = NOW(), change_due=0
WHERE id = ?;
EOS;
        $message .= "\$completed = dbSafeCmd($updCompleteSQL, 'si', array ($master_tid))<br/>";
        //$completed = dbSafeCmd($updCompleteSQL, 'i', array ($master_tid));
    }

    $message .= "\$upd_rows memberships updated" . ($completed == 1 ? ', transaction completed.<br/>' : '.<br/>');

//  it all worked, update the transaction status
    $updTranStatusSQL = <<<EOS
UPDATE transaction
SET paymentStatus = ?, paymentId = ?
WHERE id = ?;
EOS;
    $message .= "\$updcnt = dbSafeCmd($updTranStatusSQL, 'sssi', array(" . $checkout['status'] . ", $id, $master_tid))<br/>";
    //$updcnt = dbSafeCmd($updTranStatusSQL, 'ssi', array($checkout['status'], $id, $master_tid));
    return $message;
}


function completeArt($issueId, $checkout, $order, $payment) : string {
    return 'In completeArt<br/>';
}
