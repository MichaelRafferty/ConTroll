<?php
require_once('../lib/base.php');
require_once('../lib/getAccountData.php');
require_once('../lib/portalEmails.php');
require_once('../../lib/paymentPlans.php');
require_once('../../lib/purchase.php');
require_once('../../lib/coupon.php');
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$condata = get_con();
$conid=$condata['id'];
$conf = get_conf('con');
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$log = get_conf('log');
$ccauth = get_conf('cc');
load_cc_procs();
logInit($log['reg']);

$response['conid'] = $conid;

if (!(array_key_exists('action', $_POST) && array_key_exists('plan', $_POST) &&
    array_key_exists('amount', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

$action = $_POST['action'];
if ($action != 'portalOrder') {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

validateLoginId();

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');

$plan = $_POST['plan'];
$newPlan = $_POST['newplan'];
$amount = $_POST['amount'];
$planRec = $_POST['planRec'];
$existingPlan = $_POST['existingPlan'];
$planPayment = $_POST['planPayment'];
$otherPay = $_POST['otherPay'];

// load the amount values
if (array_key_exists('totalAmountDue', $_POST) && $otherPay != 1) {
    $totalAmountDue = $_POST['totalAmountDue'];
} else {
    $totalAmountDue = $amount;
}
if (array_key_exists('couponDiscount', $_POST)) {
    $webCouponDiscount = $_POST['couponDiscount'];
} else {
    $webCouponDiscount = 0;
}
if (array_key_exists('preCouponAmountDue', $_POST)) {
    $preCouponAmountDue = $_POST['preCouponAmountDue'];
} else {
    $preCouponAmountDue = $amount;
}

if (array_key_exists('cancelOrder', $_POST)) {
    $cancelOrderId = $_POST['cancelOrder'];
} else {
    $cancelOrderId = null;
}

if (array_key_exists('otherMemberships', $_POST)) {
    try {
        $otherMemberships = json_decode($_POST['otherMemberships'], true, 512, JSON_THROW_ON_ERROR);
    }
    catch (Exception $e) {
        $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
        $response['error'] = $msg;
        error_log($msg);
        ajaxSuccess($response);
        exit();
    }
} else {
    $otherMemberships = [];
}

// and the coupon values
if (array_key_exists('couponCode', $_POST)) {
    $couponCode = $_POST['couponCode'];
} else {
    $couponCode = null;
}

if (array_key_exists('couponSerial', $_POST)) {
    $couponSerial = $_POST['couponSerial'];
} else {
    $couponSerial = null;
}

if (array_key_exists('planRecast', $_POST)) {
    $planRecast = $_POST['planRecast'];
} else {
    $planRecast = 0;
}

// all the records are in the database, so lets charge the credit card...

$transId = getSessionVar('transId');
if ($transId == null) {
    $transId = getNewTransaction($conid, $loginType == 'p' ? $loginId : null, $loginType == 'n' ? $loginId : null);
}

// get this person
$info = getPersonInfo($conid);

// compute the results array here
$coupon = null;
$counts = null;
$rows_upd = 0;
$newPlanId = null;
$buyer['email'] = $info['email_addr'];
$buyer['phone'] = $info['phone'];
$buyer['country'] = $info['country'];
$phone = $info['phone'];

$deferredAmount = 0;
if ($otherPay == 0) { // this is a plan payment or badge purchase payment
    if ($planPayment == 1 && $newPlan == 0) {
        if ($existingPlan['currentPayment'] > $existingPlan['balanceDue']) {
            $totalAmountDue = $existingPlan['balanceDue'];
        } else {
            $totalAmountDue = $existingPlan['currentPayment'];
        }
        $amount = $totalAmountDue;
        $totalDiscount = 0;
        $badges = [];
    } else {
        $badges = getAccountRegistrations($loginId, $loginType, $conid, ($newPlan || $planPayment == 0) ? 'unpaid' : 'plan');

        if ($planPayment == 1 || $newPlan == 1) {
            $badges = whatMembershipsInPlan($badges, $planRec);
            $deferredAmount = $planRec['balanceDue'];
        } else foreach ($badges as $key => $badge) {
            $badges[$key]['inPlan'] = false;
        }

        // ok, the Portal data is now loaded, now deal with re-pricing things, based on the real tables
        $data = loadPurchaseData($conid, $couponCode, $couponSerial, $planPayment);
        $prices = $data['prices'];
        $memId = $data['memId'];
        $counts = $data['counts'];
        $discounts = $data['discounts'];
        $primary = $data['primary'];
        $map = $data['map'];
        $coupon = $data['coupon'];
        $memCategories = $data['memCategories'];
        $mtypes = $data['mtypes'];

        //// $rules = $data['rules'];
        //// TODO: load and apply rules checks here to $badges
        $data = computePurchaseTotals($coupon, $badges, $primary, $counts, $prices, $map, $discounts, $mtypes, $memCategories);

        $maxMbrDiscounts = $data['origMaxMbrDiscounts'];
        $apply_discount = $data['applyDiscount'];
        $preDiscount = $data['preDiscount'];
        $total = $data['total'];
        $paid = $data['paid'];
        $totalDiscount = $data['totalDiscount'];

        if ($totalAmountDue != ($total - ($paid + $deferredAmount))) {
            error_log('bad total: post=' . $totalAmountDue . ', calc=' . $total);
            ajaxSuccess(array ('status' => 'error', 'error' => 'Unable to process, bad total sent to Server'));
            exit();
        }
        $amount = $totalAmountDue;

        if ($coupon != null) {
            if ($webCouponDiscount != $totalDiscount) {
                error_log('bad coupon discount: post=' . $webCouponDiscount . ', calc=' . $totalDiscount);
                ajaxSuccess(array ('status' => 'error', 'error' => 'Unable to process, bad coupon data sent to Server'));
                exit();
            }
        }
    }
} else { // otherPay = 1, this is a pay against 'other'
    // load the badge array to mark what is being paid for
        $totalDiscount = 0;
        $badges = [];
        foreach ($otherMemberships AS $key => $mem) {
            if (!array_key_exists('payThis', $mem))
                continue;
            if ($mem['payThis'] != 1)
                continue;
            $badges[] = array('id' => $mem['create_trans'],
                              'create_date' => $mem['create_date'],
                              'regId' => $mem['regid'],
                              'memId' => $mem['memId'],
                              'conid' => $mem['conid'],
                              'status' => $mem['status'],
                              'price' => $mem['actPrice'],
                              'paid' => $mem['actPaid'],
                              'complete_trans' => $mem['complete_trans'],
                              'couponDiscount' => $mem['actCouponDiscount'],
                              'balDue' => $mem['actPrice'] - ($mem['actPaid'] + $mem['actCouponDiscount']),
                              'perid' => $mem['regPerid'],
                              'newperid' => $mem['regNewperid'],
                              'sortTrans' => $mem['sortTrans'],
                              'transDate' => $mem['transDate'],
                              'label' => $mem['shortname'],
                              'memAge' => $mem['memAge'],
                              'age' => $mem['memAge'],
                              'memType' => $mem['type'],
                              'memCategory' => $mem['category'],
                              'startdate' => $mem['startdate'],
                              'enddate' => $mem['enddate'],
                              'online' => $mem['online'],
                              'managedBy' => $mem['managedBy'],
                              'managedByNew' => $mem['managedByNew'],
                              'badge_name' => $mem['badge_name'],
                              'fullName' => $mem['fullName'],
                              'memberId' => $mem['memberId'],
                              'planId' => $mem['planId'],
                              'email_addr' => $mem['email_addr'],
                              'phone' => $mem['phone'],
                              'inPlan' => false
            );
            if ($mem['planId'] != 0) {
                $planRecast = 1;
            }
        }
}

// now recompute the records in the badgeResults array

$results = array(
    'custid' => "$loginType-$loginId",
    'source' => 'portal',
    'transid' => $transId,
    'counts' => $counts,
    'price' => $totalAmountDue,
    'badges' => $badges,
    'total' => $amount,
    'coupon' => $coupon,
    'discount' => $totalDiscount,
    'newplan' => $newPlan,
    'planRec' => $planRec,
    'planPayment' => $planPayment,
    'existingPlan' => $existingPlan,
);
$response['amount'] = $amount;

//log requested badges
logWrite(array('con'=>$condata['name'], 'trans'=>$transId, 'results'=>$results, 'request'=>$badges));
$upT = <<<EOS
UPDATE transaction
SET price = ?, withTax = ?, couponDiscountCart = ?, tax = ?
WHERE id = ?;
EOS;
$rows_upd += dbSafeCmd($upT, 'ddddi', array($totalAmountDue, $totalAmountDue, $totalDiscount, 0, $transId));

// end compute, create the order if there is something to pay
if ($amount > 0) {
    if ($cancelOrderId) // cancel the old order if it exists
        cc_cancelOrder($results['source'], $cancelOrderId, true);

    $rtn = cc_buildOrder($results, true);
    if ($rtn == null) {
        // note there is no reason cc_buildOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
        logWrite(array ('con' => $condata['name'], 'trans' => $transId, 'error' => 'Order unable to be created'));
        ajaxSuccess(array ('status' => 'error', 'error' => 'Order not built'));
        exit();
    }
    $response['rtn'] = $rtn;
} else {
    $rtn = array();
}

//$tnx_record = $rtn['tnx'];
logWrite(array('con' => $condata['name'], 'trans' => $transId, 'ccrtn' => $rtn));
ajaxSuccess($response);
return;
