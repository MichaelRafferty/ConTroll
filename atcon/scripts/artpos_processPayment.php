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

$perid = $_POST['perid'];

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
    ajaxError('invalid payment amount passed: ' . $new_payment['amt']);
    return;
}

if (array_key_exists('change', $_POST)) {
    $response['crow'] = $_POST['change'];
}

$art = (float) $new_payment['pretax'];
// validate that the payment amount is not too large
$total_due = 0;
foreach ($cart_art as $cart_row) {
    if ($cart_row['display_price'] == '')
        $cart_row['display_price'] = 0;

    if ($cart_row['purQuantity'] != $cart_row['artSalesQuantity'] && $cart_row['type'] == 'print') {
        $cart_row['artSalesQuantity'] = $cart_row['purQuantity'];
        $cart_row['amount'] = $cart_row['display_price'];
        $cart_row['updSales'] = true;
        }
    else {
        $cart_row['updSales'] = false;
    }

    if ($cart_row['amount'] == null || $cart_row['amount'] == '')
        $cart_row['amount'] = $cart_row['display_price'];

    if ($cart_row['paid'] == null ||$cart_row['paid'] == '')
        $cart_row['paid'] = 0;
    $total_due += $cart_row['amount'] - $cart_row['paid'];
}

if (round($art,2) > round($total_due,2)) {
    ajaxError('invalid payment amount passed exceeds total' . " art: $art total: $total_due");
    return;
}

$complete = round($art,2) == round($total_due,2);

// now add the payment and process to which rows it applies
$upd_rows = 0;
$upd_cart = 0;
$insPmtSQL = <<<EOS
INSERT INTO payments(transid, type, category, description, source, pretax, tax, amount, time, cc_approval_code, cashier)
VALUES (?,?,'artshow',?,'cashier',?,?,?,now(),?, ?);
EOS;
$typestr = 'issdddsi';
if ($new_payment['type'] == 'check')
    $desc = 'Check No: ' . $new_payment['checkno'] . '; ';
else
    $desc = '';
$desc .= $new_payment['desc'];
$paramarray = array($master_tid, $new_payment['type'], $desc, $new_payment['pretax'], $new_payment['tax'], $new_payment['amt'], $new_payment['ccauth'],
                    $user_id);
$new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);
if ($new_pid === false) {
    ajaxError("Error adding payment to database");
    return;
}

$new_payment['id'] = $new_pid;
$response['prow'] = $new_payment;
$response['message'] = "1 payment added";
$updArtSalesSQL = <<<EOS
UPDATE artSales
SET paid = ?, transid = ?, quantity = ?, amount = ?
WHERE id = ?;
EOS;
$atypestr = 'diidi';

$updArtSalesQtySQL = <<<EOS
UPDATE artSales
SET quantity = ?, amount = ?
WHERE id = ?;
EOS;
$aqtypestr = 'idi';

$updQuantitySQL = <<<EOS
UPDATE artItems
SET quantity = CASE WHEN quantity - ? < 0 THEN 0 ELSE quantity - ? END
WHERE id = ?;
EOS;
$uqstr = 'iii';

$updStatusSQL = <<<EOS
UPDATE artItems
SET status = 'Quicksale/Sold', bidder = ?, final_price = ?
WHERE id = ?;
EOS;
$usstr = 'idi';

$updArtSalesStatusSQL = <<<EOS
UPDATE artSales
SET status = ?
WHERE id = ?;
EOS;
$usrstr = 'si';

foreach ($cart_art as $cart_row) {
    if ($cart_row['display_price'] == '')
        $cart_row['display_price'] = 0;

    if ($cart_row['amount'] == null || $cart_row['amount'] == '')
        $cart_row['amount'] = $cart_row['display_price'];

    if ($cart_row['paid'] == null ||$cart_row['paid'] == '')
        $cart_row['paid'] = 0;

    $unpaid = $cart_row['amount'] - $cart_row['paid'];
    $quantity = $cart_row['purQuantity'];
    if ($unpaid > 0) {
        $amt_paid = min($art, $unpaid);
        $cart_row['paid'] += $amt_paid;
        $cart_art[$cart_row['index']] = $cart_row;
        $art -= $amt_paid;
        $upd_rows += dbSafeCmd($updArtSalesSQL, $atypestr, array($cart_row['paid'], $master_tid, $quantity, $cart_row['amount'], $cart_row['artSalesId']));

        // change status of items sold by quick sale to quicksale sold, decrease quantity of print items
        if (round($cart_row['amount'],2) == round($cart_row['paid'],2)) {
            $upd_cart += dbSafeCmd($updQuantitySQL, $uqstr, array($quantity, $quantity, $cart_row['id']));

            if ($cart_row['priceType'] == 'Quick Sale') {
                $upd_cart += dbSafeCmd($updStatusSQL, $usstr, array($perid, $cart_row['paid'], $cart_row['id']));
                $upd_rows += dbSafeCmd($updArtSalesStatusSQL, $usrstr, array('Quicksale/Sold', $cart_row['artSalesId']));
            }
            if ($cart_row['type'] == 'print') {
                $upd_rows += dbSafeCmd($updArtSalesStatusSQL, $usrstr, array('Purchased/Released', $cart_row['artSalesId']));
            }
        }
    } else {
        if (array_key_exists('updSales', $cart_row) && $cart_row['updSales'] == true) {
            $upd_rows += dbSafeCmd($updArtSalesQtySQL, $aqtypestr, array($quantity, $cart_row['amount'], $cart_row['artSalesId']));
        }
    }
}

    $updCompleteSQL = <<<EOS
UPDATE transaction
SET paid = IFNULL(paid,'0.00') + ?, tax = IFNULL(tax,'0') + ?, withtax = IFNULL(withtax,'0') + ?
WHERE id = ?;
EOS;
$completed = dbSafeCmd($updCompleteSQL, 'dddi', array($new_payment['amt'], $new_payment['tax'], $new_payment['tax'], $master_tid));
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
