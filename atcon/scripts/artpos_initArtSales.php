<?php

// library AJAX Processor: artpos_initArtSales.php
// ConTroll Registration System
// Author: Syd Weinstein
// Store the cart into the system using add/update/delete and create appropriate transaction records

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
if ($ajax_request_action != 'initArtSales') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$user_id = $_POST['user_id'];
if ($user_id != $_SESSION['user']) {
    RenderErrorAjax("Invalid credentials passed");
    exit();
}

if ((!array_key_exists('pay_tid', $_POST)) || (!array_key_exists('perid', $_POST))) {
    RenderErrorAjax('Invalid parameters passed');
    exit();
}
$pay_tid = $_POST['pay_tid'];
$master_perid = $_POST['perid'];

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

try {
    $cart_art_map = json_decode($_POST['cart_art_map'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

if ($pay_tid == null || $pay_tid <= 0) {
    // create master transaction for this art sale
    $insTransactionSQL = <<<EOS
INSERT INTO transaction(conid,perid,userid,price,paid,type,create_date)
VALUES (?,?,?,?,?,'artpos',now());
EOS;
// now insert the master transaction
    $paramarray = array($conid, $master_perid, $user_id, 0, 0);
    $typestr = 'iiidd';
    $pay_tid = dbSafeInsert($insTransactionSQL, $typestr, $paramarray);
    if ($pay_tid === false) {
        RenderErrorAjax('Unable to create master transaction');
        return;
    }
}

$response['pay_tid'] = $pay_tid;

$updated_art = [];
$error_message = '';

$artSales_ins = 0;
$artSales_upd = 0;
$artSales_del = 0;
$artItems_upd = 0;
$total_price = 0;
$total_paid = 0;

$insArtSales = <<<EOS
INSERT INTO artSales(transid, artid, unit, status, perid, amount, paid, quantity)
VALUES (?,?,?,?,?,?,?,?);
EOS;
$updArtSales = <<<EOS
UPDATE artSales SET paid = ?
WHERE id = ?;
EOS;
$delArtSales = <<<EOS
DELETE FROM artSales
WHERE id = ?;
EOS;
$updArtItem = <<<EOS
UPDATE artItems
SET status = ?, final_price = ?, bidder = ?, updatedBy = ?
WHERE id = ?;
EOS;


// insert/update/delete all artSales records and compute the transaction price and paid fields
for ($row = 0; $row < sizeof($cart_art); $row++) {
    $cartrow = $cart_art[$row];
    if (!array_key_exists('todelete', $cartrow)) {
        if ($cartrow['display_price'] == '')
            $cartrow['display_price'] = 0;
        $total_price += $cartrow['display_price'];

        if ((!array_key_exists('paid', $cartrow)) || $cartrow['paid'] == '')
            $cartrow['paid'] = 0;
        $total_paid += $cartrow['paid'];

        if ((!array_key_exists('artSalesId', $cartrow)) || $cartrow['artSalesId'] == null || $cartrow['artSalesId'] <= 0) {
            // insert this row
            $paramarray = array($pay_tid, $cartrow['id'], $cartrow['unit'], $cartrow['status'], $master_perid, $cartrow['display_price'], 0, $cartrow['purQuantity']);
            $typestr = 'iiisiddi';
            $new_id = dbSafeInsert($insArtSales, $typestr, $paramarray);
            if ($new_id === false) {
                $error_message .= "Insert of artSales item for $row failed<BR/>";
            } else {
                $updated_art[] = array('rowpos' => $row, 'transid' => $pay_tid, 'artSalesId' => $new_id, 'artSalesStatus' => $cartrow['status'], 'perid' =>
                    $master_perid,
                    'amount' => $cartrow['display_price'], 'paid' => '0.00', 'quantity' => $cartrow['quantity']);
                $artSales_ins++;
            }
        } else {
            // update the row
            $paramarray = array($cartrow['paid'], $cartrow['artSalesId']);
            $typestr = 'di';
            $artSales_upd += dbSafeCmd($updArtSales, $typestr, $paramarray);
        }
        // update the art item for the fields that could change
        $paramarray = array($cartrow['status'], $cartrow['final_price'], $cartrow['bidder'], $user_id, $cartrow['id']);
        $artItems_upd += dbSafeCmd($updArtItem, 'sdiii', $paramarray);
    } else {
        // delete membership
        if (array_key_exists('artSalesId', $cartrow)) {
            $paramarray = array($cartrow['artSalesId']);
            $typestr = 'i';
            $artSales_del += dbSafeCmd($delArtSales, $typestr, $paramarray);
        }
    }
}
// update the transaction associated with this reg
$updTransactionSQL = <<<EOS
UPDATE transaction
SET price = ?, paid = ?
WHERE id = ?
EOS;
$paramarray = array($total_price, $total_paid, $pay_tid);
$typestr = 'ssi';
if (dbSafeCmd($updTransactionSQL, $typestr, $paramarray) === false) {
    $error_message .= "Update of master transaction failed";
}

if ($error_message != '') {
    $response['error'] = $error_message;
    ajaxSuccess($response);
}
$response['message'] = "$artSales_ins sales records inserted, $artSales_upd art sales records updated, $artSales_del art sales records deleted";
$response['updated_art'] = $updated_art;
$response['pay_tid'] = $pay_tid;
ajaxSuccess($response);
