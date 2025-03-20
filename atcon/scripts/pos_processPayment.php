<?php
// library AJAX Processor: pos_processPayment.php
// ConTroll Registration System
// Author: Syd Weinstein
// create payment record

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');

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

$log = get_conf('log');
$con = get_conf('con');
logInit($log['reg']);
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'processPayment') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

// processPayment
//  cart_perinfo: perinfo records with memberships embedded
//  new_payment: payment being added
//  pay_tid: current master transaction

$master_tid = $_POST['pay_tid'];
if ($master_tid <= 0) {
    ajaxError('No current transaction in process');
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

$new_payment = $_POST['new_payment'];
if (!array_key_exists('amt', $new_payment) || $new_payment['amt'] <= 0) {
    ajaxError('invalid payment amount passed');
    return;
}

if (array_key_exists('coupon', $_POST)) {
    $coupon = $_POST['coupon'];
}
else {
    $coupon = null;
}

if (array_key_exists('change', $_POST)) {
    $response['crow'] = $_POST['change'];
}

$amt = (float) $new_payment['amt'];
// validate that the payment amount is not too large
$total_due = 0;
$email = $cart_perinfo[0]['email_addr'];
$phone = $cart_perinfo[0]['phone'];
if ($email == '/r')
    $email = '';
if ($phone == '/r')
    $phone = '';
foreach ($cart_perinfo as $perinfo) {
    foreach ($perinfo['memberships'] as $cart_row) {
        if ($cart_row['price'] == '')
            $cart_row['price'] = 0;

        if (array_key_exists('couponDiscount', $cart_row)) {
            if ($cart_row['couponDiscount'] == '')
                $cart_row['couponDiscount'] = 0;
        }
        else
            $cart_row['couponDiscount'] = 0;

        if ($cart_row['paid'] == '')
            $cart_row['paid'] = 0;
        $total_due += $cart_row['price'] - ($cart_row['couponDiscount'] + $cart_row['paid']);
    }
}

if (round($amt,2) > round($total_due,2)) {
    ajaxError('invalid payment amount passed');
    return;
}

// if we need to process a credit card, do it now before applying the payment record
if ($new_payment['type'] == 'online') {
    $cc_params = array(
        'transid' => $master_tid,
        'counts' => 0,
        'price' => null,
        'badges' => null,
        'tax' => 0,
        'pretax' => $amt,
        'total' => $amt,
        'nonce' => $new_payment['nonce'],
        'coupon' => $coupon,
    );

//log requested badges
    logWrite(array('type' => 'online', 'con' => $con['conname'], 'trans' => $master_tid, 'results' => $cc_params));
    if ($amt > 0) {
        $ccauth = get_conf('cc');
        load_cc_procs();
        $rtn = cc_charge_purchase($cc_params, $email, $phone, true);
        if ($rtn === null) {
            ajaxSuccess(array('status' => 'error', 'data' => 'Credit card not approved'));
            exit();
        }

//$tnx_record = $rtn['tnx'];

        $num_fields = sizeof($rtn['txnfields']);
        $val = array();
        for ($i = 0; $i < $num_fields; $i++) {
            $val[$i] = '?';
        }
        $txnQ = 'INSERT INTO payments(time,' . implode(',', $rtn['txnfields']) . ') VALUES(current_time(),' . implode(',', $val) . ');';
        $txnT = implode('', $rtn['tnxtypes']);
        $new_pid = dbSafeInsert($txnQ, $txnT, $rtn['tnxdata']);
        $approved_amt = $rtn['amount'];
    } else {
        $approved_amt = 0;
        $rtn = array('url' => '');
    }
}


$complete = round($amt,2) == round($total_due,2);
// now add the payment and process to which rows it applies
$upd_rows = 0;
$cupd_rows = 0;
if ($new_payment['type'] != 'online') { // online already added the payment record
    $insPmtSQL = <<<EOS
INSERT INTO payments(transid, type,category, description, source, pretax, tax, amount, time, cc_approval_code, cashier)
VALUES (?,?,'reg',?,'cashier',?,?,?,now(),?, ?);
EOS;
    $typestr = 'issdddsi';
    if ($new_payment['type'] == 'check')
        $desc = 'Check No: ' . $new_payment['checkno'] . '; ';
    else
        $desc = '';
    $desc .= $new_payment['desc'];
    $paramarray = array($master_tid, $new_payment['type'], $desc, $new_payment['amt'], 0, $new_payment['amt'], $new_payment['ccauth'], $user_perid);
    $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);
}

if ($new_pid === false) {
    ajaxError("Error adding payment to database");
    return;
}

$new_payment['id'] = $new_pid;
$response['prow'] = $new_payment;
$response['message'] = "1 payment added";
$updPaymentSQL = <<<EOS
UPDATE reg
SET paid = ?, complete_trans = ?, status = ?
WHERE id = ?;
EOS;
$ptypestr = 'disi';

$updCouponSQL = <<<EOS
UPDATE reg
SET couponDiscount = ?, coupon = ?
WHERE id = ? AND coupon IS NULL;
EOS;
$ctypestr = 'dii';
$index = 0;
foreach ($cart_perinfo as $perinfo) {
    $cart_perinfo[$index]['rowpos'] = $index;
    unset($cart_perinfo[$index]['dirty']);
    $index++;
    foreach ($perinfo['memberships'] as $cart_row) {
        if ($cart_row['price'] == '')
            $cart_row['price'] = 0;
        if ($cart_row['couponDiscount'] == '')
            $cart_row['couponDiscount'] = 0;
        if ($cart_row['paid'] == '')
            $cart_row['paid'] = 0;
        $unpaid = $cart_row['price'] - ($cart_row['couponDiscount'] + $cart_row['paid']);
        if ($unpaid > 0) {
            if ($coupon == null) {
                $amt_paid = min($amt, $unpaid);
                $cart_row['paid'] += $amt_paid;
                if ($amt_paid == $unpaid) {
                    // row is now completely paid
                    $args = array($cart_row['paid'], $master_tid, 'paid', $cart_row['regid']);
                    $cart_row['status'] = 'paid';
                } else {
                    $args = array($cart_row['paid'], null, $cart_row['status'], $cart_row['regid'] );
                }
                $cart_perinfo[$perinfo['index']]['memberships'][$cart_row['index']] = $cart_row;
                $amt -= $amt_paid;

                $upd_rows += dbSafeCmd($updPaymentSQL, $ptypestr, $args);
            }
            else {
                $cupd_rows += dbSafeCmd($updCouponSQL, $ctypestr, array ($cart_row['couponDiscount'], $coupon, $cart_row['regid']));
            }
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
    $completed = dbSafeCmd($updCompleteSQL, 'iddi', array($coupon, $new_payment['cartDiscount'], $new_payment['memDiscount'], $master_tid));
} else { // normal payment
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET paid = IFNULL(paid,'0.00') + ?
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'si', array($new_payment['amt'], $master_tid));
}
$completed = 0;
if ($complete) {
    // payment is in full, mark transaction complete
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET complete_date = NOW()
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'i', array($master_tid));
}

$response['message'] .= ", $upd_rows memberships updated" . $completed == 1 ? ", transaction completed." : ".";
$response['updated_perinfo'] = $cart_perinfo;
ajaxSuccess($response);
