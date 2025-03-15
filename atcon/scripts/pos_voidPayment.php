<?php

// library AJAX Processor: pos_voidPayment.php
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

// voidPayment
//  cart_membership: memberships to have payment applied
//  new_payment: payment being added
//  pay_tid: current master transaction

$user_id = $_POST['user_id'];
if ($user_id != getSessionVar('user')) {
    ajaxError('Invalid credentials passed');
}

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
    ajaxError('No people are in the cart');
    return;
}

// restore the amount paid on each cart row to the original amount
$updCartNonZeroSQL = <<<EOS
UPDATE reg
SET paid = ?
WHERE id = ?;
EOS;
$updCartZeroSQL = <<<EOS
UPDATE reg
SET paid = 0, complete_trans = NULL
WHERE id = ?;
EOS;
$upd_rows = 0;
foreach ($cart_perinfo AS $perinfo) {
    $cart_memberships = $cart_perinfo['memberships'];
    foreach ($cart_memberships as $cart_row) {
        if (array_key_exists('priorPaid', $cart_row)) {
            $prior_pmt = $cart_row['priorPaid'];
        }
        else {
            $prior_pmt = 0;
        }
        if ($cart_row['paid'] != $prior_pmt) {
            // there is a payment, back it out.
            $cart_row['paid'] = $prior_pmt;
            if ($prior_pmt == 0) {
                $upd_rows += dbSafeCmd($updCartNonZeroSQL, 'si', array ($prior_pmt, $cart_row['regid']));
            }
            else {
                $upd_rows += dbSafeCmd($updCartZeroSQL, 'i', array ($cart_row['regid']));
            }
        }
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
$response['cart_perinfo'] = $cart_perinfo;
ajaxSuccess($response);
