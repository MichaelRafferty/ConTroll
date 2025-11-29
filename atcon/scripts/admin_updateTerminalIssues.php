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
SELECT t.id, t.ccPaymentId, t.paymentStatus, t.checkoutId, t.create_date, t.complete_date, t.perid, t.userid, t.withtax, t.paid, 
       t.type, t.orderId, t.lastUpdate, TIMESTAMPDIFF(MINUTE, t.lastUpdate, NOW()) as minutes, t.paymentInfo,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
       y.id AS payTableId, IFNULL(y.status, '') AS cardStatus, IFNULL(y.ccPaymentId, '') AS cardPaymentId
FROM transaction t
JOIN perinfo p ON t.perid = p.id
LEFT OUTER JOIN payments y ON t.id = y.transid AND y.type NOT IN ('coupon', 'discount')
WHERE t.conid = ? AND t.id = ? AND t.checkoutId IS NOT NULL AND 
    (IFNULL(t.paymentStatus,'') NOT IN ('COMPLETED', 'CANCELED') OR IFNULL(y.status,'') = 'APPROVED')
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
if ($issue['checkoutId'] != '' && $issue['paymentStatus'] != 'COMPLETED' && $issue['ccPaymentId'] != 'CANCELED') {
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
                $message .= 'terminal: returned more than one credit card payment id<br/>';
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

            if ($issue['paymentInfo'] != null && $issue['paymentInfo'] != '')
                $paymentInfo = json_decode($issue['paymentInfo'], true);
            else
                $paymentInfo = array();

            if ($orderType == 'reg')
                $message .= completeReg($issue['id'], $checkout, $order, $payment, $paymentInfo);
            else
                $message .= completeArt($issue['id'], $checkout, $order, $payment, $paymentInfo);
            break;

        default:
            $message .= "Unable to correct this status value currently, see assistance <br/>";
    }
}

// Clear the terminal if it's reserved for this checkout id
$updTermSQL = <<<EOS
UPDATE terminals
SET currentOperator = 0, currentOrder = '', currentPayment = '', controllStatus = '', controllStatusChanged = now()
WHERE currentPayment = ?;
EOS;
$num_upd = dbSafeCmd($updTermSQL, 's', array($issue['checkoutId']));
$message .= "$num_upd terminals released and marked available<br/>";

// now get the remaining issues
$issueSQL = <<<EOS
SELECT t.id, t.ccPaymentId, t.paymentStatus, t.checkoutId, t.create_date, t.complete_date, t.perid, t.userid, t.withtax, t.paid, 
       t.type, t.orderId, t.lastUpdate, TIMESTAMPDIFF(MINUTE, t.lastUpdate, NOW()) as minutes,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
       y.id AS payTableId, y.status AS cardStatus, y.ccPaymentId AS cardPaymentId
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
function completeReg($master_tid, $checkout, $order, $payment, $paymentInfo) : string {
    $new_payment_desc = '';
    $drow = null; // need $drow
    $couponPayment = null; // new coupon payment
    $couponDiscount = 0; // need coupon info
    $coupon = null; // need coupon
    $user_perid = getSessionVar('user');
    $discountAmt = 0; // need discount amount
    $message = '';

    if (is_array($paymentInfo)) {
        // items from payment Info
        if (array_key_exists('prow', $paymentInfo)) {
            $new_payment_desc = $paymentInfo['prow']['desc'];
        }
        if (array_key_exists('drow', $paymentInfo)) {
            $drow = $paymentInfo['drow'];
        }
        if (array_key_exists('discountAmt', $paymentInfo)) {
            $discountAmt = $paymentInfo['discountAmt'];
        }
        if (array_key_exists('couponDiscount', $paymentInfo)) {
            $couponDiscount = $paymentInfo['couponDiscount'];
        }
        if (array_key_exists('couponPayment', $paymentInfo)) {
            $couponPayment = $paymentInfo['couponPayment'];
        }
        if (array_key_exists('coupon', $paymentInfo)) {
            $coupon = $paymentInfo['coupon'];
        }
    }

    if ($coupon == '')
        $coupon = null;

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

    // build the order line items array

    // now add the payment and process to which rows it applies
    $insPmtSQL = <<<EOS
INSERT INTO payments(transid, type,category, description, source, pretax, tax, amount, time, cc_approval_code, cashier, 
    cc, nonce, cc_txn_id, txn_time, receipt_url, receipt_id, userPerid, status, ccPaymentId)
VALUES (?,?,'reg',?,'cashier',?,?,?,NOW(),?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
    $typestr = 'issdddsissssssiss';

    // coupon payment if it exist
    if ($couponPayment != null) {
        $paramarray = array ($master_tid, 'coupon', $couponPayment['desc'], $couponPayment['amt'], 0, $couponPayment['amt'], null, $user_perid,
            null, null, null, null, null, null, $user_perid, 'APPLIED', null);
        //$message .= "\$new_pid = dbSafeInsert($insPmtSQL, $typestr, array(" . implode(',', $paramarray) . "));<br/>";
        $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);

        if ($new_pid === false) {
           ajaxError('Error adding coupon payment to database');
           exit();
        }
        $message .= "Payment Row $new_pid inserted of type 'coupon' for " . $couponPayment['amt'] . "<br/>";
    }

    if ($drow != null) {
        $paramarray = array ($master_tid, 'discount', $drow['desc'], $drow['amt'], 0, $drow['amt'], null, $user_perid,
            null, null, null, null, null, null, $user_perid, 'APPLIED', null);
        //$message .= "\$new_pid = dbSafeInsert($insPmtSQL, $typestr, array(" . implode(',', $paramarray) . '));<br/>';
        $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);

        if ($new_pid === false) {
            ajaxError('Error adding manager discount payment to database');
            exit();
        }
        $message .= "Payment Row $new_pid inserted of type 'discount' for " . $drow['amt'] . " due to " .
            $drow['desc'] . '<br/>';
    }

    // now the main payment
    if ($amt > 0) {
        $paramarray = array ($master_tid, $paymentType, $desc, $preTaxAmt, $taxAmt, $approved_amt, $auth, $user_perid,
            $last4, $nonceCode, $id, $txTime, $receiptUrl, $receiptNumber, $user_perid, $status, $id);
        //$message .= "\$new_pid = dbSafeInsert($insPmtSQL, $typestr, array(" . implode(',', $paramarray) . '));<br/>';
        $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);

        if ($new_pid === false) {
            ajaxError('Error adding payment to database');
            exit();
        }
        $message .= "Payment Row $new_pid inserted of type 'main' for $approved_amt with desc $desc<br/>";
    }

    $message .= '1 payment added<br/>';
    $upd_rows = 0;
    $updRegSql = <<<EOS
UPDATE reg
SET paid = ?, complete_trans = ?, status = ?, couponDiscount = ?, coupon = ?
WHERE id = ?;
EOS;
    // update regs with their amount paid and discounted
    $lines = $order['line_items'];
    foreach ($lines as $line) {
        $applied_disc = $line['total_discount_money']['amount'] / 100;
        $paid = $line['total_money']['amount'] / 100;
        $gross = $line['gross_sales_money']['amount'] / 100;
        $note = $line['note'];
        $note = substr($note, 0, strpos($note, ':'));
        $regid = explode(',', $note)[2];

        // update the database
        //$message .= "\$upd_rows += dbSafeCmd($updRegSql, 'disdii', array($gross, $master_tid, 'paid', $applied_disc, $coupon, $regid));<br/>";
        $upd_rows += dbSafeCmd($updRegSql, 'disdii', array($paid, $master_tid, 'paid', $applied_disc, $coupon, $regid));
        }


// if coupon is specified, mark transaction as having a coupon
    if ($coupon || $discountAmt > 0) {
        $updCompleteSQL = <<<EOS
UPDATE transaction
SET coupon = ?, couponDiscountCart = ?, couponDiscountReg = ?
WHERE id = ?;
EOS;
        //$message .= "\$completed = dbSafeCmd($updCompleteSQL, 'iddi', array ($coupon, $couponDiscount + $discountAmt, 0, $master_tid))<br/>";
        $completed = dbSafeCmd($updCompleteSQL, 'iddi', array ($coupon, $couponDiscount + $discountAmt, 0, $master_tid));
    }

    $updCompleteSQL = <<<EOS
UPDATE transaction
SET paid = ?,  paymentStatus = ?, ccPaymentId = ?
WHERE id = ?;
EOS;
    //$message .= "\$completed = dbSafeCmd($updCompleteSQL, 'dssi', array ($approved_amt, " . $checkout['status'] . ", $id, $master_tid))<br/>";
    $completed = dbSafeCmd($updCompleteSQL, 'dssi', array ($approved_amt, $checkout['status'], $id, $master_tid));

    $completed = 0;
    if ($complete) {
        // payment is in full, mark transaction complete
        $updCompleteSQL = <<<EOS
UPDATE transaction
SET complete_date = NOW(), change_due=0
WHERE id = ?;
EOS;
        //$message .= "\$completed = dbSafeCmd($updCompleteSQL, 'i', array ($master_tid))<br/>";
        $completed = dbSafeCmd($updCompleteSQL, 'i', array ($master_tid));
    }

    $message .= "$upd_rows memberships updated" . ($completed == 1 ? ', transaction completed.<br/>' : '.<br/>');
    return $message;
}

// Actions needed to complete an item of source Reg Cashier
function completeArt($master_tid, $checkout, $order, $payment, $paymentInfo) : string {
    // check to see if the payment record exists, if so, update it, else and create it
    $new_payment_desc = '';
    $drow = null; // need $drow
    $couponPayment = null; // new coupon payment
    $couponDiscount = 0; // need coupon info
    $coupon = null; // need coupon
    $user_perid = getSessionVar('user');
    $discountAmt = 0; // need discount amount
    $message = '';
    $paymentStatus = $payment['status'];
    $paymentId = $payment['id'];
    if ($paymentStatus == 'APPROVED' || $paymentStatus == 'PENDING') {
        return "Payment is still in $paymentStatus state, try again later.";
    }

    $updTranStatusSQL = <<<EOS
UPDATE transaction
SET paymentStatus = ?, ccPaymentId = ?
WHERE id = ?;
EOS;
    // check to see if the payment was 'declined'
    if ($paymentStatus != 'COMPLETED') {
        // payment failed, mark transaction cancelled and return, it should not be stuck in approved state if declined
        $updcnt = dbSafeCmd($updTranStatusSQL, 'ssi', array ('CANCELLED', null, $master_tid));
        return "Payment status $paymentStatus, transaction marked CANCELLED";
    }

    // ok, the payment is complete, add the payment records if they are not there already
    $pmtSQL = <<<EOS
SELECT *
FROM payments
WHERE transId = ?;
EOS;
    $pmtResult = dbSafeQuery($pmtSQL, 'i', array($master_tid));
    if ($pmtResult === false) {
        $response['error'] = 'Query failed, seek assistance';
        ajaxSuccess($response);
        exit();
    }
    if ($pmtResult->num_rows == 0) {
        $taxAmt = $order['total_tax_money']['amount'] / 100;
        $taxes = $order['taxes'];
        // payment doesn't exist, insert it and create
        if ($taxAmt > 0) {
            [$taxFields, $taxSql, $taxStr, $taxValues] = buildTaxInsert($taxes);
            if ($taxFields != '')
                $taxFields = ", $taxFields";
            if ($taxSql != '')
                $taxSql = ", $taxSql";
        } else {
            $taxFields = '';
            $taxSql = '';
            $taxStr = '';
            $taxValues = [];
        }

        $insPmtSQL = <<<EOS
INSERT INTO payments(transid, type,category, description, source, pretax, tax, amount, time, cc_approval_code, cashier,
    cc, nonce, cc_txn_id, txn_time, receipt_url, receipt_id, userPerid, status, ccPaymentId $taxFields)
VALUES (?,?,?,?,'cashier',?,?,?,now(),?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? $taxSql);
EOS;
        $typestr = 'isssdddsissssssiss' . $taxStr;
        $amt = $payment['total_money']['amount'] / 100;
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

        if (is_array($paymentInfo)) {
            // items from payment Info
            if (array_key_exists('prow', $paymentInfo)) {
                $new_payment_desc = $paymentInfo['prow']['desc'];
            }
            if (array_key_exists('drow', $paymentInfo)) {
                $drow = $paymentInfo['drow'];
            }
            if (array_key_exists('discountAmt', $paymentInfo)) {
                $discountAmt = $paymentInfo['discountAmt'];
            }
            if (array_key_exists('couponDiscount', $paymentInfo)) {
                $couponDiscount = $paymentInfo['couponDiscount'];
            }
            if (array_key_exists('couponPayment', $paymentInfo)) {
                $couponPayment = $paymentInfo['couponPayment'];
            }
            if (array_key_exists('coupon', $paymentInfo)) {
                $coupon = $paymentInfo['coupon'];
            }
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

        $category = 'artsales';
        $approved_amt = $payment['approved_money']['amount'] / 100;
        $paymentId = $payment['id'];
        $paymentType = $payment['paymentType'];
        $paramarray = array ($master_tid, $paymentType, $category, $desc, $preTaxAmt, $taxAmt, $approved_amt, $auth, $user_perid,
            $last4, $nonceCode, $paymentId, $txTime, $receiptUrl, $receiptNumber, $user_perid, $status, $paymentId);

        $new_pid = dbSafeInsert($insPmtSQL, $typestr, array_merge($paramarray, $taxValues));
        if ($new_pid === false) {
            return 'Error adding payment to database';
        }
        $message .= '1 Payment added<br/>';
    }
    $pmtResult->free();

    // mark transaction as it's updated status
    $updcnt = dbSafeCmd($updTranStatusSQL, 'ssi', array ($paymentStatus, $paymentId, $master_tid));
    $complete = true;

    // update the art items and sales records
    $updArtSalesSQL = <<<EOS
UPDATE artSales
SET paid = amount, transid = ?
WHERE id = ?;
EOS;
    $atypestr = 'ii';

    $updQuantitySQL = <<<EOS
UPDATE artItems
SET quantity = CASE WHEN quantity - ? < 0 THEN 0 ELSE quantity - ? END
WHERE id = ?;
EOS;
    $uqstr = 'iii';

    $updStatusSQL = <<<EOS
UPDATE artItems
SET status = ?, bidder = ?, final_price = ?
WHERE id = ?;
EOS;
    $usstr = 'sidi';

    $updArtSalesStatusSQL = <<<EOS
UPDATE artSales
SET status = ?
WHERE id = ?;
EOS;
    $usrstr = 'si';
    $upd_cart = 0;
    $upd_rows = 0;
    $orderLines = $order['line_items'];
    foreach ($orderLines as $line) {
        $quantity = $line['quantity'];
        $meta = $line['metadata'];
        $artId = $meta['artId'];
        $artSalesId = $meta['artSalesId'];
        $perId = $meta['perId'];
        $type = $meta['type'];
        $priceType = $meta['priceType'];
        $paid = $line['gross_sales_money'];
        $upd_rows += dbSafeCmd($updArtSalesSQL, $atypestr, array ($master_tid, $artSalesId));

        // change status of items sold, decrease quantity of print items
        $upd_cart += dbSafeCmd($updQuantitySQL, $uqstr, array ($quantity, $quantity, $artId));

        if ($priceType == 'Quick Sale') {
            $upd_cart += dbSafeCmd($updStatusSQL, $usstr, array ('Quicksale/Sold', $perId, $paid, $artId));
            $upd_rows += dbSafeCmd($updArtSalesStatusSQL, $usrstr, array ('Quicksale/Sold', $artSalesId));
        } else {
            $upd_cart += dbSafeCmd($updStatusSQL, $usstr, array ('Purchased/Released', $perId, $paid, $artId));
            $upd_rows += dbSafeCmd($updArtSalesStatusSQL, $usrstr, array ('Purchased/Released', $artSalesId));
        }

        if ($type == 'print') {
            $upd_rows += dbSafeCmd($updArtSalesStatusSQL, $usrstr, array ('Purchased/Released', $artSalesId));
        }
    }

    $updCompleteSQL = <<<EOS
UPDATE transaction
SET paid = ?
WHERE id = ?;
EOS;
    $approved_amt = $payment['approved_money']['amount'] / 100;
    $completed = dbSafeCmd($updCompleteSQL, 'di', array ($approved_amt, $master_tid));

    $completed = 0;

    // payment is in full, mark transaction complete
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET complete_date = NOW(), orderId = ?
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'dsi', array ($order['id'], $master_tid));

    return $message;
}
