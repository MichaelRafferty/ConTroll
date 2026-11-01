<?php
// library AJAX Processor: artpos_buildOrder.php
// ConTroll Registration System
// Author: Syd Weinstein
// create art sales order from cart for payment processing

require_once '../lib/base.php';
require_once('../../lib/tax.php');
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
// build the list for the order, do not include the already paid items
$amount = 0;
$totalAmountDue = 0;
$totalPaid = 0;
$art = [];

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

// now recompute the records in the results array

$results = array(
    'custid' => "p-$payorId",
    'source' => 'artpos',
    'artSales' => 1,
    'price' => $totalAmountDue,
    'art' => $art,
    'totalPaid' => $totalPaid,
    'payorId' => $payorId,
    'discount' => 0,
    'transid' => $transId,
    'name' => $_POST['name'],
    'email' => $_POST['email'],
    'phone' => $_POST['phone'],
);
$response['amount'] = $amount;

//log art to check out

labeled_logWrite('artpos_buildOrder data', array('con'=>$con['label'], 'payorId'=>$payorId, 'results'=>$results, 'request'=>$art));

$locationId = getSessionVar('terminal');
if ($locationId) {
    $locationId = $locationId['locationId'];
} else if (array_key_exists('location_artpos', $cc)) {
    $locationId = $cc['location_artpos'];
} else if (array_key_exists('location', $cc)) {
    $locationId = $cc['location'];
} else {
    $locationId = 'Unknown';
}

if ($cancelOrderId) // cancel the old order if it exists
    cc_cancelOrder($results['source'], $cancelOrderId, true, $locationId);

$rtn = cc_buildOrder($results, true, $locationId);
if ($rtn == null) {
    // note there is no reason cc_buildOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
    labeled_logWrite('artpos_buildOrder-cc_bulldOrder returned null',
        array ('con' => $con['label'], 'payorId' => $payorId, 'error' => 'Order unable to be created'));
    ajaxSuccess(array ('status' => 'error', 'error' => 'Order not built'));
    exit();
}
$rtn['totalPaid'] = $totalPaid;

// update the cart_art structure and the art sales items
$items = $rtn['items'];
$currency = cc_getCurrency();
$currencyMultiplier = get_currencyMultiplier($currency);
for ($index = 0; $index < COUNT($cart_art); $index++) {
    $row = $cart_art[$index];

    // find the matching order line
    $matchItem = null;
    for ($itemIndex = 0; $itemIndex < COUNT($items); $itemIndex++) {
        $item = $items[$itemIndex];
        if (array_key_exists('metadata', $item))
            $meta = $item['metadata']; // cc_test
        else {
            // cc_stripe
            $metas = $rtn['order']['metadata'];
            $metaName = 'im' . ($itemIndex + 1);
            $meta = json_decode($metas[$metaName], true, 512, JSON_THROW_ON_ERROR);
        }
        if ($meta['artId'] == $row['id']) {
            $matchItem = $item;
            break;
        }
    }

    if ($matchItem === null) {
        error_log("cannot find item " . $row['id'] . " in order list, order id " . $rtn['orderId']);
        continue;
    }

    if (array_key_exists('basepriceMoney', $matchItem)) { // cc_test
        $itemPrice = $matchItem['basePriceMoney'];
        $taxAmts = $matchItem['taxAmounts'];
        if (is_array($itemPrice) && array_key_exists('amount', $itemPrice)) {
            $money = $itemPrice['amount'] / $currencyMultiplier;
            $itemTax = $taxAmts['totalTax']['amount'] / $currencyMultiplier;
        } else {
            $money = $itemPrice / $currencyMultiplier;
            $itemTax = $taxAmts['totalTax'] / $currencyMultiplier;
        }
    }
    if (array_key_exists('unit_cost', $matchItem)) { // cc_stripe
        $money = $matchItem['unit_cost'] / $currencyMultiplier;
        $itemTax = $matchItem['tax']['total_tax_amount'] / $currencyMultiplier;
    }
    $cart_art[$index]['netAmount'] = round($money, 2);
    $cart_art[$index]['totalTax'] = round($itemTax, 2);
}

$response['cart_art'] = $cart_art;

$taxes = $rtn['taxes'];
[$taxSql, $taxStr, $taxValues] = buildTaxUpdate($taxes);
$upT = <<<EOS
UPDATE transaction
SET price = ?, tax = ?, withTax = ?, netPrice = ?, couponDiscountCart = ?, orderId = ?, paymentStatus = 'ORDER', orderDate = now(), $taxSql
WHERE id = ?;
EOS;
$preTax = $rtn['preTaxAmt'];
$netAmount = $rtn['netAmount'];
$taxAmt = $rtn['taxAmt'];
$withTax = $rtn['totalAmt'];
$valArray = array($preTax, $taxAmt, $withTax, $netAmount, 0, $rtn['orderId']);
$typeStr = 'ddddds' . $taxStr . 'i';
$valArray = array_merge($valArray, $taxValues);
$valArray[] = $transId;

$rows_upd = dbSafeCmd($upT, $typeStr, $valArray);

$response['rtn'] = $rtn;
labeled_logWrite('artpos_buildorder-rtn', array('con' => $con['label'], 'payorId' => $payorId, 'ccrtn' => $rtn));
ajaxSuccess($response);
return;
