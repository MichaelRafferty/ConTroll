<?php
// library AJAX Processor: pos_processPayment.php
// ConTroll Registration System
// Author: Syd Weinstein
// create payment record and send same to credit card provider

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/term__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'processPayment') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$user_id = $_POST['user_id'];
if ($user_id != getSessionVar('user')) {
    ajaxError('Invalid credentials passed');
}

$user_perid = $user_id;
$paymentType = 'credit'; // default type
$source = 'atcon';

$log = get_conf('log');
$con = get_conf('con');
$debug = get_conf('debug');
$ini = get_conf('reg');
$cc = get_conf('cc');
load_cc_procs();
logInit($log['term']);

$conid = $con['id'];
$upd_rows = 0;
$cupd_rows = 0;

// processPayment
//  orderId: this.#pay_currentOrderId,
//  new_payment: prow,
//  coupon: prow.coupon,
//  change: crow,
//  nonce: nonce,
//  user_id: this.#user_id,
//  pay_tid: this.#pay_tid,
//  pay_tid_amt: this.#pay_tid_amt,

$master_tid = $_POST['pay_tid'];
if ($master_tid <= 0) {
    ajaxError('No current transaction in process');
}

$orderId = $_POST['orderId'];
if ($orderId == null || $orderId == '') {
    ajaxError('No current order in process');
}

$new_payment = $_POST['new_payment'];
if (!array_key_exists('amt', $new_payment) || $new_payment['amt'] <= 0) {
    ajaxError('invalid payment amount passed: payment <= 0');
    return;
}
$amt = (float) $new_payment['amt'];

if (array_key_exists('preTaxAmt', $_POST))
    $preTaxAmt = $_POST['preTaxAmt'];
else
    $preTaxAmt = $_POST['totalAmtDue'];

if (array_key_exists('taxAmt', $_POST))
    $taxAmt = $_POST['taxAmt'];
else
    $taxAmt = 0;

if (array_key_exists('couponDiscount', $_POST))
    $couponDiscount = $_POST['couponDiscount'];
else
    $couponDiscount = 0;

if (array_key_exists('discountAmt', $_POST))
    $discountAmt = $_POST['discountAmt'];
else
    $discountAmt = 0;

$preTaxAmt -= $couponDiscount + $discountAmt;

if ($amt != $preTaxAmt + $taxAmt) {
    ajaxError('Invalid payment amount passed: preTax + Tax != Amount');
    return;
}

try {
    $cart_perinfo = json_decode($_POST['cart_perinfo'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}
if (sizeof($cart_perinfo) <= 0) {
    ajaxError('The cart is empty');
    return;
}

if (array_key_exists('drow', $_POST))
    $drow = $_POST['drow'];
else
    $drow = null;

$override = $_POST['override'];
if (array_key_exists('poll', $_POST)) {
    $poll = $_POST['poll'];
} else {
    $poll = 0;
}

// we need an available terminal, so get the latest status
if ($new_payment['type'] == 'terminal') {
    load_term_procs();
    $terminal = getSessionVar('terminal');
    $name = $terminal['name'];
    $statusResponse = term_getStatus($name);
    $termStatus = $statusResponse['updatedRow'];

    if ($override == 1) {  // force the operation to continue, try to cancel anything in progress
        resetTerminalStatus($name);
        if ($termStatus['currentPayment'] != null && $termStatus['currentPayment'] != '') {
            term_cancelPayment($name, $termStatus['currentPayment'], true);
        }
        if ($termStatus['currentOrder'] != null && $termStatus['currentOrder'] != '$orderId') {
            cc_cancelOrder('atcon', $orderId, true);
        }
    } else {
        $status = $termStatus['status'];
        $inUseBy = $termStatus['currentOperator'];
        $controllStatus = $termStatus['controllStatus'];
        $currentOrder = $termStatus['currentOrder'];
        $currentPayment = $termStatus['currentPayment'];
        if ($status != 'AVAILABLE' || ($inUseBy > 0 && ($inUseBy != $user_id || $poll == 0))) {
            $msg = "Terminal $name is not available, it's status is $status";
            if ($inUseBy != null && $inUseBy != '') {
                if ($inUseBy != $user_id) {
                    $operatorNameSQL = <<<EOS
SELECT TRIM(REGEXP_REPLACE(CONCAT_WS(' ', first_name, middle_name, last_name, suffix), '  *', ' ')) AS fullName
FROM perinfo
WHERE id = ?;
EOS;
                    $inUseName = '';
                    $operatorR = dbSafeQuery($operatorNameSQL, 'i', array ($inUseBy));
                    if ($operatorR !== false) {
                        if ($operatorR->num_rows == 1) {
                            $inUseName = $operatorR->fetch_row()[0];
                        }
                        $operatorR->free();
                    }

                    $msg .= " and is in use by $inUseName ($inUseBy)";
                } else {
                    $msg .= " and is in use by you";
                }
            }
            if ($controllStatus != null && $controllStatus != '') {
                $msg .= "<br/>And the system says it is in use for $controllStatus<br/>For order $currentOrder and payment operation $currentPayment";
            }
            $response['termStatus'] = $termStatus;
            $response['inUseBy'] = $inUseBy;
            $response['status'] = $status;
            $response['warn'] = $msg;
            ajaxSuccess($response);
            exit();
        }
    }
}

if (array_key_exists('coupon', $_POST)) {
    $coupon = $_POST['coupon'];
}
else {
    $coupon = null;
}

$crow = null;
if (array_key_exists('change', $_POST)) {
    $crow = $_POST['change'];
    $response['crow'] = $crow;
}

 $couponPayment = null;
 if (array_key_exists('couponPayment', $_POST)) {
     $couponPayment = $_POST['couponPayment'];
     $response['couponPayment'] = $couponPayment;
 }

$payor_email = $new_payment['payor']['email'];
$payor_phone = $new_payment['payor']['phone'];
$payor_perid = $new_payment['payor']['perid'];
$payor_country = $new_payment['payor']['country'];

$pay_tid_amt = -1;
if (array_key_exists('pay_tid_amt', $_POST)) {
    $pay_tid_amt = $_POST['pay_tid_amt'];
}

$buyer['email'] = $payor_email;
$buyer['phone'] = $payor_phone;
$buyer['country'] = $payor_country;

// see if we need to change the master transaction perid, only do this if the amount paid is = 0
if ($pay_tid_amt == 0) {
    // ok, not current payor_perid and no payment yet
    $chgTP = <<<EOS
UPDATE transaction
SET perid = ?
WHERE id = ?;
EOS;
    $chgTPC = dbSafeCmd($chgTP, 'ii', array($payor_perid, $master_tid));
}

$change = 0;
if ($amt > 0) {
    if ($new_payment['type'] != 'terminal') {
        // cash, online credit card (square), cash, external: (offline credit card, check, discount, coupon)
        //      everything now goes to square
        $nonce = 'EXTERNAL';
        $externalType = 'OTHER';
        $desc = '';
        switch ($new_payment['type']) {
            case 'cash':
                $nonce = 'CASH';
                if ($crow)
                    $change = -$crow['amt'];
                break;
            case 'online':
                $nonce = $new_payment['nonce'];
                break;
            case 'discount':
                $desc = 'disc: ';
                break;
            case 'coupon':
                $desc = $coupon;
                break;
            case 'credit':
                $externalType = 'CARD';
                $desc = 'offline cc';
                break;
            case 'check':
                $externalType = 'CHECK';
                $desc = 'Chk No: ' . $new_payment['checkno'];
                break;
        }

        if ($desc == '')
            $desc = $new_payment['desc'];
        else
            $desc = mb_substr($desc . '/' . $new_payment['desc'], 0, 64);

        $locationId = getSessionVar('terminal');
        if ($locationId) {
            $locationId = $locationId['locationId'];
        } else {
            $locationId = $cc['location'];
        }
        $ccParam = array (
            'transid' => $master_tid,
            'counts' => 0,
            'price' => null,
            'badges' => null,
            'taxAmt' => $taxAmt,
            'preTaxAmt' => $preTaxAmt,
            'total' => $amt,
            'orderId' => $orderId,
            'nonce' => $nonce,
            'coupon' => $coupon,
            'externalType' => $externalType,
            'desc' => $desc,
            'source' => $source,
            'change' => $change,
            'locationId' => $locationId,
        );

        //log requested badges
        logWrite(array ('type' => 'online', 'con' => $con['conname'], 'trans' => $master_tid, 'results' => $ccParam));
        load_cc_procs();
        $rtn = cc_payOrder($ccParam, $buyer, true);
        if ($rtn === null) {
            ajaxSuccess(array ('error' => 'Credit card not approved'));
            exit();
        }
    } else {
        // this is a terminal do a terminal pay request
        if ($poll == 1) {
            $checkout = term_getPayStatus($name, $termStatus['currentPayment'], true);
            if ($checkout == null) {
                ajaxSuccess(array ('error' => "Unable to get payment status from terminal $name"));
                exit();
            }
            $status = $checkout['status'];
            switch ($status) {
                case 'CANCELED':
                    resetTerminalStatus($name);
                    ajaxSuccess(array ('error' => "The terminal cancelled the payment due to " . $checkout['cancel_reason'] .
                        '<br/>If the customer still wishes to pay for the transaction, ' .
                        'please click "Confirm Pay" again to start a new payment session with the terminal'));
                    exit();

                case 'IN_PROGRESS':
                case 'PENDING':
                    ajaxSuccess(array ('error' => 'The terminal is still busy processing the payment.' .
                        '<br/>Please wait until the customer has finished paying and try again.'));
                    exit();

                case 'CANCEL_REQUESTED':
                    resetTerminalStatus($name);
                    ajaxSuccess(array ('error' => 'The terminal is working on cancelling the payment.' .
                        '<br/>Please wait until the terminal resets to the splash screen and then' .
                        '<br/>If the customer still wishes to pay for the transaction, ' .
                        'please click "Confirm Pay" again to start a new payment session with the terminal'));
                    exit();
            }
            if ($status != 'COMPLETED') {
                ajaxSuccess(array ('error' => "Terminal returned  an unknown payment status of '$status', please seek assistance"));
                exit();
            }
            // get the payment id to get the payment details
            $paymentIds = $checkout['payment_ids'];
            if (count($paymentIds) > 1) {
                web_error_log("pos_processPayment: terminal: returned more than one paymentId");
                web_error_log($paymentIds);
            }
            $paymentId = $paymentIds[0];
            $payment = cc_getPayment('atcon', $paymentId, true);

            $approved_amt = $payment['approved_money']['amount'] / 100;
            $category = 'atcon';
            $desc = $payment['application_details']['square_product'];
            $txtime = $payment['created_at'];
            $receiptNumber = $payment['receipt_number'];
            $receiptUrl = $payment['receipt_url'];
            $last4 = $payment['card_details']['card']['last_4'];
            $id = $payment['id'];
            $location_id = $payment['location_id'];
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

            if ($desc == '')
                $desc = $new_payment['desc'];
            else
                $desc = mb_substr($desc . '/' . $new_payment['desc'], 0, 64);

            $rtn = array ();
            $rtn['amount'] = $approved_amt;
            $rtn['txnfields'] = array ('transid', 'type', 'category', 'description', 'source', 'pretax', 'tax', 'amount',
                'txn_time', 'cc', 'nonce', 'cc_txn_id', 'cc_approval_code', 'receipt_url', 'status', 'receipt_id', 'cashier');
            $rtn['tnxtypes'] = array ('i', 's', 's', 's', 's', 'd', 'd', 'd',
                's', 's', 's', 's', 's', 's', 's', 's', 'i');
            $rtn['tnxdata'] = array ($master_tid, 'credit', $category, $desc, $source, $preTaxAmt, $taxAmt, $approved_amt,
                $txtime, $last4, $nonce, $id, $auth, $receiptUrl, $status, $receiptNumber, $user_perid);
            $rtn['url'] = $receiptUrl;
            $rtn['rid'] = $receiptNumber;
            $rtn['paymentType'] = $paymentType;
            $rtn['payment'] = $payment;
            $rtn['preTaxAmt'] = $preTaxAmt;
            $rtn['couponDiscount'] = $couponDiscount;
            $rtn['taxAmt'] = $taxAmt;
            $rtn['paymentId'] = $paymentId;
            $rtn['auth'] = $auth;
            $rtn['last4'] = $last4;
            $rtn['txTime'] = $txtime;
            $rtn['status'] = $status;
            $rtn['transId'] = $master_tid;
            $rtn['category'] = $category;
            $rtn['description'] = $desc;
            $rtn['source'] = $source;
            $rtn['amount'] = $approved_amt;
            $rtn['nonce'] = $nonce;

            resetTerminalStatus($name);
        } else {
            // this is the send the request to the terminal, then we need a separate poll section to get it back and continue to record the payment.
            $checkout = term_payOrder($name, $orderId, $amt, true);
            $status = $checkout['status'];
            if ($status == 'PENDING') {
                $updQ = <<<EOS
UPDATE terminals
SET currentOperator = ?, currentOrder = ?, currentPayment = ?, controllStatus = ?, controllStatusChanged = NOW()
WHERE name = ?;
EOS;
                $upd = dbSafeCmd($updQ, 'issss', array ($user_id, $orderId, $checkout['id'], $status, $name));
                if ($upd === false) {
                    ajaxSuccess(array ('error' => "Unable to update terminal ($name) status"));
                    exit();
                }
                $response['status'] = 'success';
                $response['poll'] = 1;
                $response['id'] = $checkout['id'];
                $response['message'] = "Payment request sent to terminal $name,<br/>" .
                    'click "Payment Complete when payment has been made or "Cancel Payment" to cancel the request.';
                ajaxSuccess($response);
                exit();
            }
            ajaxSuccess(array ('error' => "Unable to send payment request to terminal $name"));
            exit();
        }
    }

    $approved_amt = $rtn['amount'];
    $type = $rtn['paymentType'];
    $preTaxAmt = $rtn['preTaxAmt'];
    $taxAmt = $rtn['taxAmt'];
    $paymentId = $rtn['paymentId'];
    $receiptUrl = $rtn['url'];
    $receiptNumber = $rtn['rid'];
    $paymentType = $rtn['paymentType'];
    $auth = $rtn['auth'];
    $payment = $rtn['payment'];
    $last4 = $rtn['last4'];
    $txTime = $rtn['txTime'];
    $status = $rtn['status'];
    $transId = $rtn['transId'];
    $category = $rtn['category'];
    $description = $rtn['description'];
    $source = $rtn['source'];
    $nonce = $rtn['nonce'];
    if ($nonce == 'EXTERNAL')
        $nonceCode = $ccParam['externalType'];
    else
        $nonceCode = $nonce;
    $complete = round($approved_amt,2) == round($amt,2);

    // now add the payment and process to which rows it applies
    $insPmtSQL = <<<EOS
INSERT INTO payments(transid, type,category, description, source, pretax, tax, amount, time, cc_approval_code, cashier, 
    cc, nonce, cc_txn_id, txn_time, receipt_url, receipt_id, userPerid, status, paymentId)
VALUES (?,?,'reg',?,'cashier',?,?,?,now(),?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
    $typestr = 'issdddsissssssiss';

    // coupon payment if it exist
    if ($couponPayment != null) {
        $paramarray = array ($master_tid, 'coupon', $couponPayment['desc'], $couponPayment['amt'], 0, $couponPayment['amt'], null, $user_perid,
            null, null, null, null, null, null, $user_perid, 'APPLIED', null);
        $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);

        if ($new_pid === false) {
            ajaxError('Error adding coupon payment to database');
            return;
        }
    }

    if ($drow != null) {
        $paramarray = array ($master_tid, 'discount', $drow['desc'], $drow['amt'], 0, $drow['amt'], null, $user_perid,
            null, null, null, null, null, null, $user_perid, 'APPLIED', null);
        $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);

        if ($new_pid === false) {
            ajaxError('Error adding manager discount payment to database');
            return;
        }
    }

    // now the main payment
    $paramarray = array ($master_tid, $paymentType, $desc, $preTaxAmt, $taxAmt, $approved_amt, $auth, $user_perid,
        $last4, $nonceCode, $paymentId, $txTime, $receiptUrl, $receiptNumber, $user_perid, $status, $paymentId);
    $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);

    if ($new_pid === false) {
        ajaxError('Error adding payment to database');
        return;
    }
    $new_payment['id'] = $new_pid;
} else {
    $complete = true;
}

$response['prow'] = $new_payment;
$response['message'] = "1 payment added";
$updRegSql = <<<EOS
UPDATE reg
SET paid = ?, complete_trans = ?, status = ?, couponDiscount = ?, coupon = ?
WHERE id = ?;
EOS;
$ptypestr = 'disdii';
$index = 0;
// allocate pre-tax amount to regs
foreach ($cart_perinfo as $perinfo) {
    $cart_perinfo[$index]['rowpos'] = $index;
    unset($cart_perinfo[$index]['dirty']);
    $index++;
    foreach ($perinfo['memberships'] as $cart_row) {
        if ($cart_row['price'] == '')
            $cart_row['price'] = 0;
        if ((!array_key_exists('couponDiscount', $cart_row)) || $cart_row['couponDiscount'] == '')
            $cart_row['couponDiscount'] = 0;
        if ($cart_row['paid'] == '')
            $cart_row['paid'] = 0;
        if ((!array_key_exists('coupon', $cart_row)) || $cart_row['coupon'] == '')
            $cart_row['coupon'] = null;
        $unpaid = $cart_row['price'] - ($cart_row['couponDiscount'] + $cart_row['paid']);
        if ($unpaid > 0) {
            $amt_paid = min($preTaxAmt, $unpaid);
            $cart_row['paid'] += $amt_paid;
            if ($amt_paid == $unpaid) {
                // row is now completely paid
                $args = array($cart_row['paid'], $master_tid, 'paid', $cart_row['couponDiscount'], $cart_row['coupon'], $cart_row['regid']);
                $cart_row['status'] = 'paid';
            } else {
                $args = array($cart_row['paid'], null, $cart_row['status'], $cart_row['couponDiscount'], $cart_row['coupon'], $cart_row['regid'] );
            }
            $cart_perinfo[$perinfo['index']]['memberships'][$cart_row['index']] = $cart_row;
            $preTaxAmt -= $amt_paid;

            $upd_rows += dbSafeCmd($updRegSql, $ptypestr, $args);
        }
    }
}

// if coupon is specified, mark transaction as having a coupon
if ($coupon) {
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET coupon = ?, couponDiscountCart = ?, couponDiscountReg = ?
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'iddi', array($coupon, $couponDiscount, 0, $master_tid));
}
$updCompleteSQL = <<<EOS
UPDATE transaction
SET paid = IFNULL(paid,'0.00') + ?
WHERE id = ?;
EOS;
$completed = dbSafeCmd($updCompleteSQL, 'di', array($approved_amt, $master_tid));

$completed = 0;
if ($complete) {
    // payment is in full, mark transaction complete
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET complete_date = NOW(), change_due = ?, orderId = ?
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'dsi', array($change, $orderId, $master_tid));
}

$response['pay_amt'] = $new_payment['amt'];
$response['message'] .= ", $upd_rows memberships updated" . $completed == 1 ? ", transaction completed." : ".";
$response['updated_perinfo'] = $cart_perinfo;
ajaxSuccess($response);

function resetTerminalStatus($name) {
    $updQ = <<<EOS
UPDATE terminals
SET currentOperator = 0, currentOrder = '', currentPayment = '', controllStatus = '', controllStatusChanged = now()
WHERE name = ?;
EOS;
    dbSafeCmd($updQ, 's', array($name));
}