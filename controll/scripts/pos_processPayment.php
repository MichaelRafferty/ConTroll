 <?php
// library AJAX Processor: pos_processPayment.php
// ConTroll Registration System
// Author: Syd Weinstein
// create payment record and send same to credit card provider

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');

$check_auth = google_init('ajax');
$perm = 'registration';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    RenderErrorAjax('Authentication Failed');
    exit();
}

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'processPayment') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$paymentType = 'credit'; // default type
$source = 'controll-mailinreg';

$log = get_conf('log');
$con = get_conf('con');
logInit($log['reg']);
$conid = $con['id'];
$debug = get_conf('debug');
$ini = get_conf('reg');
$cc = get_conf('cc');
load_cc_procs();
logInit($log['reg']);

$upd_rows = 0;
$cupd_rows = 0;

// processPayment
//  cart_perinfo: perinfo records with memberships embedded
//  new_payment: payment being added
//  pay_tid: current master transaction

$user_id = $_POST['user_id'];
if ($user_id != $_SESSION['user_id']) {
    ajaxError('Invalid credentials passed');
}
$user_perid = $_SESSION['user_perid'];

$master_tid = $_POST['pay_tid'];
if ($master_tid <= 0) {
    ajaxError('No current transaction in process');
}

$orderId = $_POST['orderId'];
if ($orderId == null || $orderId == '') {
    ajaxError('No current order in process');
}

$new_payment = $_POST['new_payment'];
if (!array_key_exists('amt', $new_payment) || $new_payment['amt'] <= 0) {
    ajaxError('invalid payment amount passed: amount <= 0');
    return;
}
$amt = (float) $new_payment['amt'];

if (array_key_exists('preTaxAmt', $_POST))
    $preTaxAmt = $_POST['preTaxAmt'];
else
    $preTaxAmt = $_POST['totalAmtDue'];

if (array_key_exists('taxAmt', $_POST))
    $taxAmt = $_POST['taxAmt'];
else
    $taxAmt = 0;

if ($amt != $preTaxAmt + $taxAmt) {
    ajaxError('Invalid payment amount passed: preTax + Tax != Amount');
    return;
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

if (array_key_exists('coupon', $_POST)) {
    $coupon = $_POST['coupon'];
}
else {
    $coupon = null;
}

$crow = null;
if (array_key_exists('change', $_POST)) {
    $crow = $_POST['change'];
    $response['crow'] = $crow;
}

$payor_email = $new_payment['payor']['email'];
$payor_phone = $new_payment['payor']['phone'];
$payor_perid = $new_payment['payor']['perid'];
$payor_country = $new_payment['payor']['country'];

$pay_tid_amt = -1;
if (array_key_exists('pay_tid_amt', $_POST)) {
    $pay_tid_amt = $_POST['pay_tid_amt'];
}

$buyer['email'] = $payor_email;
$buyer['phone'] = $payor_phone;
$buyer['country'] = $payor_country;

// see if we need to change the master transaction perid, only do this if the amount paid is = 0
if ($pay_tid_amt == 0) {
    // ok, not current payor_perid and no payment yet
    $chgTP = <<<EOS
UPDATE transaction
SET perid = ?
WHERE id = ?;
EOS;
    $chgTPC = dbSafeCmd($chgTP, 'ii', array($payor_perid, $master_tid));
}

$change = 0;
if ($amt > 0) {
    // cash, online credit card (square), cash, external: (offline credit card, check, discount, coupon)
    //      everything now goes to square
    $nonce = 'EXTERNAL';
    $externalType = 'OTHER';
    $desc = '';
    switch ($new_payment['type']) {
        case 'cash':
            $nonce = 'CASH';
            if ($crow)
                $change = $crow['amt'];
            break;
        case 'online':
            $nonce = $new_payment['nonce'];
            break;
        case 'discount':
            $desc = 'disc: ';
            break;
        case 'coupon':
            $desc = $coupon;
            break;
        case 'credit':
            $externalType = 'CARD';
            $desc = 'offline cc';
            break;
        case 'check':
            $externalType = 'CHECK';
            $desc = 'Chk No: ' . $new_payment['checkno'];
            break;
    }

    if ($desc == '')
        $desc = $new_payment['desc'];
    else
        $desc = mb_substr($desc . '/' . $new_payment['desc'], 0, 64);

    $ccParam = array (
        'transid' => $master_tid,
        'counts' => 0,
        'price' => null,
        'badges' => null,
        'taxAmt' => $taxAmt,
        'preTaxAmt' => $preTaxAmt,
        'total' => $amt,
        'orderId' => $orderId,
        'nonce' => $nonce,
        'coupon' => $coupon,
        'externalType' => $externalType,
        'desc' => $desc,
        'source' => $source,
        'change' => $change,
        'locationId' => $cc['location'],
    );

    //log requested badges
    logWrite(array ('type' => 'online', 'con' => $con['conname'], 'trans' => $master_tid, 'results' => $ccParam));
    load_cc_procs();
    $rtn = cc_payOrder($ccParam, $buyer, true);
    if ($rtn === null) {
        ajaxSuccess(array ('error' => 'Credit card not approved'));
        exit();
    }

    $approved_amt = $rtn['amount'];
    $type = $rtn['paymentType'];
    $preTaxAmt = $rtn['preTaxAmt'];
    $taxAmt = $rtn['taxAmt'];
    $paymentId = $rtn['paymentId'];
    $receiptUrl = $rtn['url'];
    $receiptNumber = $rtn['rid'];
    $paymentType = $rtn['paymentType'];
    $auth = $rtn['auth'];
    $payment = $rtn['payment'];
    $last4 = $rtn['last4'];
    $txTime = $rtn['txTime'];
    $status = $rtn['status'];
    $transId = $rtn['transId'];
    $category = $rtn['category'];
    $description = $rtn['description'];
    $source = $rtn['source'];
    $nonce = $rtn['nonce'];
    if ($nonce == 'EXTERNAL')
        $nonceCode = $ccParam['externalType'];
    else
        $nonceCode = $nonce;
    $complete = round($approved_amt,2) == round($amt,2);

    // now add the payment and process to which rows it applies
    $insPmtSQL = <<<EOS
INSERT INTO payments(transid, type,category, description, source, pretax, tax, amount, time, cc_approval_code, cashier, 
    cc, nonce, cc_txn_id, txn_time, receipt_url, receipt_id, userPerid, status, paymentId)
VALUES (?,?,'reg',?,'cashier',?,?,?,now(),?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
    $typestr = 'issdddsissssssiss';
    $paramarray = array ($master_tid, $paymentType, $desc, $preTaxAmt, $taxAmt, $approved_amt, $auth, $user_perid,
        $last4, $nonceCode, $paymentId, $txTime, $receiptUrl, $receiptNumber, $user_perid, $status, $paymentId);
    $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);

    if ($new_pid === false) {
        ajaxError('Error adding payment to database');
        return;
    }
    $new_payment['id'] = $new_pid;
} else {
    $complete = true;
}

$response['prow'] = $new_payment;
$response['message'] = "1 payment added";
$updRegSql = <<<EOS
UPDATE reg
SET paid = ?, complete_trans = ?, status = ?
WHERE id = ?;
EOS;
$ptypestr = 'disi';
$updCouponSQL = <<<EOS
UPDATE reg
SET couponDiscount = ?, coupon = ?
WHERE id = ? AND coupon IS NULL;
EOS;
$ctypestr = 'dii';
$index = 0;
// allocate pre-tax amount to regs
foreach ($cart_perinfo as $perinfo) {
    $cart_perinfo[$index]['rowpos'] = $index;
    unset($cart_perinfo[$index]['dirty']);
    $index++;
    foreach ($perinfo['memberships'] as $cart_row) {
        if ($cart_row['price'] == '')
            $cart_row['price'] = 0;
        if ($cart_row['couponDiscount'] == '')
            $cart_row['couponDiscount'] = 0;
        if ($cart_row['paid'] == '')
            $cart_row['paid'] = 0;
        $unpaid = $cart_row['price'] - ($cart_row['couponDiscount'] + $cart_row['paid']);
        if ($unpaid > 0) {
            if ($coupon == null) {
                $amt_paid = min($preTaxAmt, $unpaid);
                $cart_row['paid'] += $amt_paid;
                if ($amt_paid == $unpaid) {
                    // row is now completely paid
                    $args = array($cart_row['paid'], $master_tid, 'paid', $cart_row['regid']);
                    $cart_row['status'] = 'paid';
                } else {
                    $args = array($cart_row['paid'], null, $cart_row['status'], $cart_row['regid'] );
                }
                $cart_perinfo[$perinfo['index']]['memberships'][$cart_row['index']] = $cart_row;
                $preTaxAmt -= $amt_paid;

                $upd_rows += dbSafeCmd($updRegSql, $ptypestr, $args);
            }
            else {
                $cupd_rows += dbSafeCmd($updCouponSQL, $ctypestr, array ($cart_row['couponDiscount'], $coupon, $cart_row['regid']));
            }
        }
    }
}

// if coupon is specified, mark transaction as having a coupon
if ($coupon) {
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET coupon = ?, couponDiscountCart = ?, couponDiscountReg = ?
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'iddi', array($coupon, $new_payment['cartDiscount'], $new_payment['memDiscount'], $master_tid));
} else { // normal payment
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET paid = IFNULL(paid,'0.00') + ?
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'di', array($approved_amt, $master_tid));
}
$completed = 0;
if ($complete) {
    // payment is in full, mark transaction complete
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET complete_date = NOW(), change_due = ?, orderId = ?
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'ids', array($change, $orderId, $master_tid));
}

$response['pay_amt'] = $new_payment['amt'];
$response['message'] .= ", $upd_rows memberships updated" . $completed == 1 ? ", transaction completed." : ".";
$response['updated_perinfo'] = $cart_perinfo;
ajaxSuccess($response);