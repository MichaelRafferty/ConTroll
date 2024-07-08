<?php

// library AJAX Processor: regpos_processPayment.php
// Balticon Registration System
// Author: Syd Weinstein
// create payment record

require_once('../lib/base.php');

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
if (!check_atcon('cashier', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// processPayment
//  cart_membership: memberships to have payment applied
//  new_payment: payment being added
//  pay_tid: current master transaction

$user_id = $_POST['user_id'];
if ($user_id != $_SESSION['user']) {
    ajaxError('Invalid credentials passed');
}

$master_tid = $_POST['pay_tid'];
if ($master_tid <= 0) {
    ajaxError('No current transaction in process');
}

try {
    $cart_membership = json_decode($_POST['cart_membership'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

if (sizeof($cart_membership) <= 0) {
    ajaxError('No memberships are in the cart');
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
foreach ($cart_membership as $cart_row) {
    if ($cart_row['price'] == '')
        $cart_row['price'] = 0;

    if (array_key_exists('couponDiscount', $cart_row)) {
        if ($cart_row['couponDiscount'] == '')
            $cart_row['couponDiscount'] = 0;
    } else
        $cart_row['couponDiscount'] = 0;

    if ($cart_row['paid'] == '')
        $cart_row['paid'] = 0;
    $total_due += $cart_row['price'] - ($cart_row['couponDiscount'] + $cart_row['paid']);
}

if (round($amt,2) > round($total_due,2)) {
    ajaxError('invalid payment amount passed');
    return;
}

$complete = round($amt,2) == round($total_due,2);

// now add the payment and process to which rows it applies
$upd_rows = 0;
$cupd_rows = 0;
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
$paramarray = array($master_tid, $new_payment['type'], $desc, $new_payment['amt'], 0, $new_payment['amt'], $new_payment['ccauth'], $user_id);
$new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);
if ($new_pid === false) {
    ajaxError("Error adding payment to database");
    return;
}

$new_payment['id'] = $new_pid;
$response['prow'] = $new_payment;
$response['message'] = "1 payment added";
$updPaymentSQL = <<<EOS
UPDATE reg
SET paid = ?, complete_trans = ?
WHERE id = ?;
EOS;
$ptypestr = 'sii';

$updCouponSQL = <<<EOS
UPDATE reg
SET couponDiscount = ?, coupon = ?
WHERE id = ? AND coupon IS NULL;
EOS;
$ctypestr = 'sii';
foreach ($cart_membership as $cart_row) {
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
            $cart_membership[$cart_row['index']] = $cart_row;
            $amt -= $amt_paid;
            $upd_rows += dbSafeCmd($updPaymentSQL, $ptypestr, array($cart_row['paid'], $master_tid, $cart_row['regid']));
        } else {
            $cupd_rows += dbSafeCmd($updCouponSQL, $ctypestr, array($cart_row['couponDiscount'], $coupon, $cart_row['regid']));
        }
    }
}

// if coupon is specified, mark transaction as having a coupon
if ($coupon) {
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET coupon = ?, couponDiscount = ?
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'isi', array($coupon, $amt, $master_tid));
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
$response['cart_membership'] = $cart_membership;
ajaxSuccess($response);
