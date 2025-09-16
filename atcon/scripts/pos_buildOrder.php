<?php
// library AJAX Processor: pos_buildOrder.php
// ConTroll Registration System
// Author: Syd Weinstein
// create order from cart for payment processing

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/coupon.php');
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

$discount = 0;
if (array_key_exists('couponCode', $_POST) && $_POST['couponCode'] != '') {
    $result = load_coupon_data($_POST['couponCode']);
    if ($result['status'] == 'success') {
        $coupon = $result['coupon'];
        $discount = $_POST['couponDiscount'];
    } else {
        ajaxError($result['error']);
        return;
    }
} else {
    $coupon = null;
}

$drow = null;
if (array_key_exists('drow', $_POST) && $_POST['drow'] != null) {
    $drow = $_POST['drow'];
    $discount = $_POST['discountAmt'];
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

// build the badge list for the order, do not include the already paid items
$amount = 0;
$totalAmountDue = 0;
$totalPaid = 0;
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
            $fullName = $row['fullName'];
        else
            $fullName = trim(trim($row['first_name'] . ' ' . $row['middle_name']) . ' ' . $row['last_name']);
        $badge = [
            'paid' => $paid,
            'fullName' => $fullName,
            'perid' => $row['perid'],
            'memId' => $membership['memId'],
            'glNum' => $membership['glNum'],
            'balDue' => $unpaid,
            'label' => $membership['label'],
            'memType' => $membership['memType'],
            'memCategory' => $membership['memCategory'],
            'memAge' => $membership['memAge'],
            'taxable' => $membership['taxable'],
            'fname' => $row['first_name'],
            'shortname' => ($membership['conid'] != $conid ? $membership['conid'] . ' ' : '') . $membership['shortname'],
            'conid' => $membership['conid'],
            'ageshortname' => $membership['ageShortName'],
            'price' => $price - $paid,
            'status' => $membership['status'],
            'regid' => $membership['regid'],
        ];

        $badges[] = $badge;
        $amount += $unpaid;
        $totalAmountDue += $price;
        $totalPaid += $paid;
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
    'totalPaid' => $totalPaid,
    'discount' => $discount,
    'coupon' => $coupon,
);
$response['amount'] = $amount;

//log requested badges

logWrite(array('con'=>$con['label'], 'trans'=>$transId, 'results'=>$results, 'request'=>$badges));

$locationId = getSessionVar('terminal');
if ($locationId) {
    $locationId = $locationId['locationId'];
} else {
    $locationId = $cc['location'];
}

if ($cancelOrderId) // cancel the old order if it exists
    cc_cancelOrder($results['source'], $cancelOrderId, true, $locationId);

$rtn = cc_buildOrder($results, true, $locationId);
if ($rtn == null) {
    // note there is no reason cc_buildOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
    logWrite(array ('con' => $con['label'], 'trans' => $transId, 'error' => 'Order unable to be created'));
    ajaxSuccess(array ('status' => 'error', 'error' => 'Order not built'));
    exit();
}
$rtn['totalPaid'] = $totalPaid;
$response['rtn'] = $rtn;

// if coupon discount, update the badges with the coupon discount to update the in memory cart
if ($coupon != null) {
    foreach ($rtn['items'] as $item) {
        if (array_key_exists('applied_discounts', $item)) {
            for ($discountNo = 0; $discountNo < count($item['applied_discounts']); $discountNo++) {
                $discount = $item['applied_discounts'][$discountNo];
                if (str_starts_with($discount['uid'], 'couponDiscount')) {
                    if (array_key_exists('applied_amount', $discount))
                        $thisItemDiscount = $discount['applied_amount'];
                    else
                        $thisItemDiscount = $discount['applied_money']['amount'];
                    // now find the reg entry to match this item
                    $rowno = $item['metadata']['rowno'];
                    $badges[$rowno]['couponDiscount'] = $thisItemDiscount / 100;
                    $badges[$rowno]['coupon'] = $coupon['id'];
                }
            }
        }
    }
}
if ($drow != null) {
    foreach ($rtn['items'] as $item) {
        if (array_key_exists('applied_discounts', $item)) {
            for ($discountNo = 0; $discountNo < count($item['applied_discounts']); $discountNo++) {
                $discount = $item['applied_discounts'][$discountNo];
                if (str_starts_with($discount['uid'], 'managerDiscount')) {
                    if (array_key_exists('applied_amount', $discount))
                        $thisItemDiscount = $discount['applied_amount'];
                    else
                        $thisItemDiscount = $discount['applied_money']['amount'];
                    // now find the reg entry to match this item
                    $rowno = $item['metadata']['rowno'];
                    if (!array_key_exists('paid', $badges[$rowno]))
                        $badges[$rowno]['paid'] = 0;
                    if (!array_key_exists('couponDiscount', $badges[$rowno]))
                        $badges[$rowno]['couponDiscount'] = 0;
                    $badges[$rowno]['couponDiscount'] += $thisItemDiscount / 100;
                }
            }
        }
    }
    $response['badges'] = $badges;
    $response['drow'] = $drow;
}

$upT = <<<EOS
UPDATE transaction
SET price = ?, tax = ?, withTax = ?, couponDiscountCart = ?, orderId = ?, paymentStatus = 'ORDER', orderDate = now()
WHERE id = ?;
EOS;

$preTax = $rtn['preTaxAmt'];
$taxAmt = $rtn['taxAmt'];
$withTax = $rtn['totalAmt'];
$rows_upd = dbSafeCmd($upT, 'ddddsi', array($preTax, $taxAmt, $withTax, 0, $rtn['orderId'], $transId));

//$tnx_record = $rtn['tnx'];
logWrite(array('con' => $con['label'], 'trans' => $transId, 'ccrtn' => $rtn));
ajaxSuccess($response);
return;
