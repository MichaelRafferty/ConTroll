<?php

// library AJAX Processor: artpos_processPayment.php
// ConTroll Registration System
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
if (!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// processPayment
//  cart_art: artSales to have payment applied
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
    $cart_art = json_decode($_POST['cart_art'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

if (sizeof($cart_art) <= 0) {
    ajaxError('No art is in the cart');
    return;
}

$new_payment = $_POST['new_payment'];
if (!array_key_exists('amt', $new_payment) || $new_payment['amt'] <= 0) {
    ajaxError('invalid payment amount passed');
    return;
}

if (array_key_exists('change', $_POST)) {
    $response['crow'] = $_POST['change'];
}

$amt = (float) $new_payment['amt'];
// validate that the payment amount is not too large
$total_due = 0;
foreach ($cart_art as $cart_row) {
    if ($cart_row['display_price'] == '')
        $cart_row['display_price'] = 0;

    if ($cart_row['amount'] == null || $cart_row['amount'] == '')
        $cart_row['amount'] = $cart_row['display_price'];

    if ($cart_row['paid'] == null ||$cart_row['paid'] == '')
        $cart_row['paid'] = 0;
    $total_due += $cart_row['amount'] - $cart_row['paid'];
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
INSERT INTO payments(transid, type,category, description, source, amount, time, cc_approval_code, cashier)
VALUES (?,?,'artshow',?,'cashier',?,now(),?, ?);
EOS;
$typestr = 'issssi';
if ($new_payment['type'] == 'check')
    $desc = 'Check No: ' . $new_payment['checkno'] . '; ';
else
    $desc = '';
$desc .= $new_payment['desc'];
$paramarray = array($master_tid, $new_payment['type'], $desc, $new_payment['amt'], $new_payment['ccauth'], $user_id);
$new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);
if ($new_pid === false) {
    ajaxError("Error adding payment to database");
    return;
}

$new_payment['id'] = $new_pid;
$response['prow'] = $new_payment;
$response['message'] = "1 payment added";
$updPaymentSQL = <<<EOS
UPDATE artSales
SET paid = ?, transid = ?
WHERE id = ?;
EOS;
$ptypestr = 'sii';

foreach ($cart_art as $cart_row) {
    if ($cart_row['display_price'] == '')
        $cart_row['display_price'] = 0;

    if ($cart_row['amount'] == null || $cart_row['amount'] == '')
        $cart_row['amount'] = $cart_row['display_price'];

    if ($cart_row['paid'] == null ||$cart_row['paid'] == '')
        $cart_row['paid'] = 0;

    $unpaid = $cart_row['amount'] - $cart_row['paid'];
    if ($unpaid > 0) {
        $amt_paid = min($amt, $unpaid);
        $cart_row['paid'] += $amt_paid;
        $cart_art[$cart_row['index']] = $cart_row;
        $amt -= $amt_paid;
        $upd_rows += dbSafeCmd($updPaymentSQL, $ptypestr, array($cart_row['paid'], $master_tid, $cart_row['artSalesId']));
    }
}

// if coupon is specified, mark transaction as having a coupon


    $updCompleteSQL = <<<EOS
UPDATE transaction
SET paid = IFNULL(paid,'0.00') + ?
WHERE id = ?;
EOS;
$completed = dbSafeCmd($updCompleteSQL, 'si', array($new_payment['amt'], $master_tid));
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
$response['cart_art'] = $cart_art;
ajaxSuccess($response);
