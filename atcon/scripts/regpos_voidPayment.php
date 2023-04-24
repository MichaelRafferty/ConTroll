<?php

// library AJAX Processor: regpos_processPayment.php
// Balticon Registration System
// Author: Syd Weinstein
// delete payment records for the master transaction and restore amounts unpaid to cart

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
if ($ajax_request_action != 'voidPayment') {
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

// restore the amount paid on each cart row to the original amount
$updCartSQL = <<<EOS
UPDATE reg
SET paid = ?
WHERE id = ?;
EOS;
$upd_rows = 0;
foreach ($cart_membership as $cart_row) {
    if (array_key_exists('priorPaid', $cart_row)) {
        $prior_pmt = $cart_row['priorPaid'];
    } else {
        $prior_pmt = 0;
    }
    if ($cart_row['paid'] != $prior_pmt) {
        // there is a payment, back it out.
        $cart_row['paid'] = $prior_pmt;
        $upd_rows += dbSafeCmd($updCartSQL, 'ii', array($prior_pmt, $cart_row['regid']));
    }
}

// now delete the payment record
$updPmtSQL = <<<EOS
UPDATE payments
SET status = CONCAT(?, FORMAT(amount, 2)), amount = 0
WHERE transid = ?;
EOS;
$void_rows = dbSafeCmd($updPmtSQL, 'si', array("Voided by $user_id, original paid amount $", $master_tid));
$response['message'] = "$upd_rows membership payments voided, $void_rows payments voided";
$response['cart_membership'] = $cart_membership;
ajaxSuccess($response);
