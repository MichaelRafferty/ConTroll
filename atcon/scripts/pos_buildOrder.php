<?php
// library AJAX Processor: pos_buildOrder.php
// ConTroll Registration System
// Author: Syd Weinstein
// create order from cart for payment processing

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
$debug = get_conf('debug');
$ini = get_conf('reg');
$log = get_conf('log');
$ccauth = get_conf('cc');
load_cc_procs();
logInit($log['term']);

$response['conid'] = $conid;

if (!(array_key_exists('ajax_request_action', $_POST) && array_key_exists('pay_tid', $_POST) &&
    array_key_exists('cart_perinfo', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Parameter error - get assistance'));
    exit();
}

$action = $_POST['ajax_request_action'];
if ($action != 'buildOrder') {
    ajaxSuccess(array('status'=>'error', 'error'=>'Parameter error - get assistance'));
    exit();
}

// build Order
//  cart_perinfo: perinfo records with memberships embedded
//  pay_tid: current master transaction

$transId = $_POST['pay_tid'];
if ($transId <= 0) {
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

if (array_key_exists('cancelOrder', $_POST)) {
    $cancelOrderId = $_POST['cancelOrder'];
} else {
    $cancelOrderId = null;
}

// all the records are in the database, so lets build the order

// get this person
//$info = getPersonInfo($conid);

// build the badge list for the order, do not include the already paid items
$amount = 0;
$totalAmountDue = 0;
$badges = [];
foreach ($cart_perinfo as $row) {
    if (!array_key_exists('memberships', $row))
        continue;
    foreach ($row['memberships'] as $membership) {
        $price = $membership['price'];
        $paid = $membership['paid'];
        $couponDiscount = $membership['couponDiscount'];
        $unpaid = $price - ($paid + $couponDiscount);
        if ($unpaid == 0)
            continue;

        if (array_key_exists('fullName', $row))
            $fullname = $row['fullName'];
        else
            $fullname = trim(trim($row['first_name'] . ' ' . $row['middle_name']) . ' ' . $row['last_name']);
        $badge = [
            'paid' => $paid,
            'fullname' => $fullname,
            'perid' => $row['perid'],
            'memId' => $membership['memId'],
            'glNum' => $membership['glNum'],
            'balDue' => $unpaid,
            'label' => $membership['label'],
            'memType' => $membership['memType'],
            'taxable' => $membership['taxable'],
            'price' => $price - $paid,
        ];

        $badges[] = $badge;
        $amount += $unpaid;
        $totalAmountDue += $price;
    }
}

if (count($badges)  == 0) {
    ajaxError('The cart has no unpaid memberships');
    return;
}

$payorId = $badges[0]['perid'];

// now recompute the records in the badgeResults array

$results = array(
    'custid' => "p-$payorId",
    'source' => 'atcon',
    'transid' => $transId,
    'price' => $totalAmountDue,
    'badges' => $badges,
    'total' => $amount,
    'discount' => 0,
);
$response['amount'] = $amount;

//log requested badges

logWrite(array('con'=>$con['label'], 'trans'=>$transId, 'results'=>$results, 'request'=>$badges));
$upT = <<<EOS
UPDATE transaction
SET price = ?, withTax = ?, couponDiscountCart = ?, tax = ?
WHERE id = ?;
EOS;
$rows_upd = dbSafeCmd($upT, 'ddddi', array($totalAmountDue, $totalAmountDue, 0, 0, $transId));


if ($cancelOrderId) // cancel the old order if it exists
    cc_cancelOrder($results['source'], $cancelOrderId, true);

$rtn = cc_buildOrder($results, true);
if ($rtn == null) {
    // note there is no reason cc_buildOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
    logWrite(array ('con' => $con['label'], 'trans' => $transId, 'error' => 'Credit card order unable to be created'));
    ajaxSuccess(array ('status' => 'error', 'error' => 'Credit card order not built'));
    exit();
}
$response['rtn'] = $rtn;

//$tnx_record = $rtn['tnx'];
logWrite(array('con' => $con['label'], 'trans' => $transId, 'ccrtn' => $rtn));
ajaxSuccess($response);
return;