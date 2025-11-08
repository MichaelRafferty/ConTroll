<?php
// library AJAX Processor: artpos_buildOrder.php
// ConTroll Registration System
// Author: Syd Weinstein
// create art sales order from cart for payment processing

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_conf('con');
$conid = $con['id'];
$ini = get_conf('reg');
$log = get_conf('log');
$cc = get_conf('cc');
load_cc_procs();
logInit($log['term']);

$response['conid'] = $conid;

if (!(array_key_exists('ajax_request_action', $_POST) && array_key_exists('perid', $_POST) &&
    array_key_exists('cart_art', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Parameter error - get assistance'));
    exit();
}

$action = $_POST['ajax_request_action'];
if ($action != 'buildOrder') {
    ajaxSuccess(array('status'=>'error', 'error'=>'Parameter error - get assistance'));
    exit();
}

if (!(check_atcon('artsales', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if (!array_key_exists('pay_tid', $_POST)) {
    ajaxError('No transaction passed');
}

$transId = $_POST['pay_tid'];
if ($transId <= 0) {
    ajaxError('No current transaction in process');
}

// build Order
//  cart_perinfo: perinfo records with memberships embedded
//  pay_tid: current master transaction

$payorId = $_POST['perid'];
if ($payorId <= 0) {
    ajaxError('No current person found');
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
    ajaxError('The cart is empty');
    return;
}

if (array_key_exists('cancelOrder', $_POST)) {
    $cancelOrderId = $_POST['cancelOrder'];
} else {
    $cancelOrderId = null;
}

// all the records are in the database, so lets build the order

// get this person
//$info = getPersonInfo($conid);

// build the srte list for the order, do not include the already paid items
$amount = 0;
$totalAmountDue = 0;
$totalPaid = 0;
$art = [];

// weird validation login to confirm from old processPayment
// $art = (float) $new_payment['pretax'];
//// validate that the payment amount is not too large
//$total_due = 0;
//foreach ($cart_art as $cart_row) {
//    if ($cart_row['display_price'] == '')
//        $cart_row['display_price'] = 0;
//
//    if ($cart_row['purQuantity'] != $cart_row['artSalesQuantity'] && $cart_row['type'] == 'print') {
//        $cart_row['artSalesQuantity'] = $cart_row['purQuantity'];
//        $cart_row['amount'] = $cart_row['display_price'];
//        $cart_row['updSales'] = true;
//        }
//    else {
//        $cart_row['updSales'] = false;
//    }
//
//    if ($cart_row['amount'] == null || $cart_row['amount'] == '')
//        $cart_row['amount'] = $cart_row['display_price'];
//
//    if ($cart_row['paid'] == null ||$cart_row['paid'] == '')
//        $cart_row['paid'] = 0;
//    $total_due += $cart_row['amount'] - $cart_row['paid'];
//}
//
//if (round($art,2) > round($total_due,2)) {
//    ajaxError('invalid payment amount passed exceeds total' . " art: $art total: $total_due");
//    return;
//}

foreach ($cart_art as $row) {
    $price = $row['amount'];
    $paid = $row['paid'];
    $qty = $row['artSalesQuantity'];
    $unpaid = $price - $paid;
    if ($unpaid == 0) // skip paid art
        continue;

    $amount += $unpaid;
    $totalAmountDue += $price;
    $totalPaid += $paid;
    $art[] = $row;
}

if (count($art)  == 0) {
    ajaxError('The cart has no unpaid art');
    return;
}

// now recompute the records in the badgeResults array

$results = array(
    'custid' => "p-$payorId",
    'source' => 'artpos',
    'artSales' => 1,
    'price' => $totalAmountDue,
    'art' => $art,
    'total' => $amount,
    'totalPaid' => $totalPaid,
    'payorId' => $payorId,
    'discount' => 0,
    'transid' => $transId,
);
$response['amount'] = $amount;

//log requested badges

logWrite(array('con'=>$con['label'], 'payorId'=>$payorId, 'results'=>$results, 'request'=>$art));

if ($cancelOrderId) // cancel the old order if it exists
    cc_cancelOrder($results['source'], $cancelOrderId, true);

$locationId = getSessionVar('terminal');
if ($locationId) {
    $locationId = $locationId['locationId'];
} else {
    $locationId = $cc['location'];
}

$rtn = cc_buildOrder($results, true, $locationId);
if ($rtn == null) {
    // note there is no reason cc_buildOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
    logWrite(array ('con' => $con['label'], 'payorId' => $payorId, 'error' => 'Order unable to be created'));
    ajaxSuccess(array ('status' => 'error', 'error' => 'Order not built'));
    exit();
}
$rtn['totalPaid'] = $totalPaid;

$upT = <<<EOS
UPDATE transaction
SET price = ?, tax = ?, withTax = ?, couponDiscountCart = ?, orderId = ?, paymentStatus = 'ORDER', orderDate = now()
WHERE id = ?;
EOS;

$preTax = $rtn['preTaxAmt'];
$taxAmt = $rtn['taxAmt'];
$withTax = $rtn['totalAmt'];
$rows_upd = dbSafeCmd($upT, 'ddddsi', array($preTax, $taxAmt, $withTax, 0, $rtn['orderId'], $transId));

$response['rtn'] = $rtn;

//$tnx_record = $rtn['tnx'];
logWrite(array('con' => $con['label'], 'payorId' => $payorId, 'ccrtn' => $rtn));
ajaxSuccess($response);
return;
