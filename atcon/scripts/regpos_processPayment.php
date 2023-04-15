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

if ($_POST && array_key_exists('nopay', $_POST)) {
    if ($_POST['nopay'] == 'true') {
        $method = 'data_entry';
    }
}

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

$cart_membership = $_POST['cart_membership'];
if (sizeof($cart_membership) <= 0) {
    ajaxError('No memberships are in the cart');
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
// validate that the payment ammount is not too large
$total_due = 0;
foreach ($cart_membership as $cart_row) {
    $total_due += $cart_row['price'] - $cart_row['paid'];
}

if (round($amt,2) > round($total_due,2)) {
    ajaxError('invalid payment amount passed');
    return;
}

// now add the payment and process to which rows it applies
$upd_rows = 0;
$insPmtSQL = <<<EOS
INSERT INTO payments(transid, type,category, description, source, amount, time, cc_approval_code, cashier)
VALUES (?,?,'reg',?,'cashier',?,now(),?, ?);
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
UPDATE reg
SET paid = ?
WHERE id = ?;
EOS;
$typestr = 'si';
foreach ($cart_membership as $cart_row) {
    $unpaid = $cart_row['price'] - $cart_row['paid'];
    if ($unpaid > 0) {
        $amt_paid = min($amt, $unpaid);
        $cart_row['paid'] += $amt_paid;
        $cart_membership[$cart_row['index']] = $cart_row;
        $amt -= $amt_paid;
        $upd_rows += dbSafeCmd($updPaymentSQL, $typestr, array($cart_row['paid'], $cart_row['regid']));
        if ($amt <= 0)
            break;
    }
}

$response['message'] .= ", $upd_rows memberships updated.";
$response['cart_membership'] = $cart_membership;
ajaxSuccess($response);
