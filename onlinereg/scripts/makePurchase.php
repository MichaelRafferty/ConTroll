<?php
require_once('../lib/base.php');
require_once("../../lib/log.php");
require_once("../../lib/tax.php");
require_once("../../lib/cc__load_methods.php");
require_once("../../lib/purchase.php");
require_once("../../lib/policies.php");
require_once("../../lib/interests.php");
require_once("../../lib/coupon.php");
require_once("../../lib/email__load_methods.php");
require_once "../lib/email.php";

if (!isset($_POST) || !isset($_POST['action'])) {
    ajaxSuccess(array('status'=>'error', 'error'=>"Error: No Info Passed"));
    exit();
}

$action = $_POST['action'];
if ($action != 'payOrder' && $action != 'paymentComplete') {
    ajaxSuccess(array('status'=>'error', 'error'=>"Error: Invalid Action"));
    exit();
}

$orderResults = $_POST['results'];
$rtn = $_POST['orderRtn'];
load_cc_procs();
load_email_procs();

$condata = get_con();
$log = get_conf('log');
$cc = get_conf('cc');

// Build the required parameters for cc_payOrder

$buyer = $orderResults['buyer'];
$transId = $orderResults['transid'];
if (array_key_exists('taxes', $orderResults))
    $taxes = $orderResults['taxes'];
else
    $taxes = array();
$withTax = $orderResults['totalAmt'];
$totalDiscount = $orderResults['totalDiscount'];
if (array_key_exists('coupon', $orderResults))
    $coupon = $orderResults['coupon'];
else
    $coupon = null;
$results = array(
    'source' => $orderResults['source'],
    'nonce' => $orderResults['nonce'],
    'totalAmt' => $orderResults['totalAmt'],
    'orderId' => $orderResults['orderId'],
    'custid' => $orderResults['custid'],
    'locationId' => $orderResults['locationId'],
    'referenceId' => $orderResults['referenceId'],
    'transid' => $transId,
    'preTaxAmt' => $orderResults['preTaxAmt'],
    'taxAmt' => $orderResults['taxAmt'],
    'total' => $orderResults['totalAmt'],
    'taxList' => $orderResults['taxList'],
    'taxes' => $taxes,
    'badges' => $orderResults['badges'],
    );

// call the credit card processor to make the payment
if ($action != 'paymentComplete')
    $ccrtn = cc_payOrder($results, $buyer, true);
else
    $ccrtn = cc_payComplete($_POST['payParams'], $_POST['paymentIntent'], true);

if ($ccrtn === null) {
    // note there is no reason cc_payOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
    labeled_logWrite('o/makePurchase-cc_payOrder returned null',
        array('con'=>$condata['name'], 'trans'=>$transId, 'error' => 'Credit card transaction not approved'));
    ajaxSuccess(array('status' => 'error', 'error' => 'Credit card not approved'));
    exit();
}

labeled_logWrite('o/makePurchase-pay succeeded', array('con'=>$condata['name'], 'trans'=>$transId, 'ccrtn'=>$rtn));
$num_fields = sizeof($ccrtn['txnfields']);
$val = array_fill(0, $num_fields, '?');
[$taxFields, $taxSql, $taxStr, $taxValues] = buildTaxInsert($taxes);
if ($taxFields != '')
    $taxFields = ", $taxFields";
if ($taxSql != '')
    $taxSql = ", $taxSql";
$txnQ = 'INSERT INTO payments(time,' . implode(',', $ccrtn['txnfields']) . $taxFields . ")\n" .
    'VALUES(current_time(),' . implode(',', $val) . $taxSql . ');';
$txnT = implode('', $ccrtn['tnxtypes']) . $taxStr;
$txnid = dbSafeInsert($txnQ, $txnT, array_merge($ccrtn['tnxdata'], $taxValues));
$approved_amt = $ccrtn['amount'];

if ($totalDiscount > 0) {
    // Insert the payment record for the coupon
    $ipQ = <<<EOS
INSERT INTO payments(transid, type, category, description, source, pretax, tax, amount, time, status) 
VALUES (?, 'coupon', 'reg', ?, 'online', ?, 0, ?, now(), 'APPLIED');
EOS;
    $couponDesc = $coupon['id'] . ':' . $coupon['code'] . ' - ' . $coupon['name'];
    $cpmtID = dbSafeInsert($ipQ, 'isdd', array($transId, $couponDesc, $totalDiscount, $totalDiscount));
    $coupon['totalDiscount'] = $totalDiscount;
}

$txnUpdate = "UPDATE transaction SET ";
if ($approved_amt == $withTax) {
    $txnUpdate .= "complete_date=current_timestamp(), ";
}

$txnUpdate .= "paid=?, couponDiscountCart = ?, coupon = ?, ccPaymentId = ?, paymentStatus = ? WHERE id=?;";
if ($totalDiscount > 0)
    $couponId = $coupon['id'];
else
    $couponId = null;


$txnU = dbSafeCmd($txnUpdate, 'ddissi',
    array($approved_amt, $totalDiscount, $couponId, $ccrtn['paymentId'], $ccrtn['status'], $transId));

$regQ = "UPDATE reg SET paid=price-couponDiscount, complete_trans = ?, status = 'paid' WHERE create_trans=?;";
dbSafeCmd($regQ, "ii", array($transId, $transId));

// mark coupon used
if ($coupon !== null && $coupon['keyId'] !== null) {
    $cupQ = 'UPDATE couponKeys SET usedBy = ?, useTS = current_timestamp WHERE id = ?';
    dbSafeCmd($cupQ, 'ii', array($transId, $coupon['keyId']));
}

$body = getEmailBody($transId, $totalDiscount);


$return_arr = send_email(getConfValue('con', 'regadminemail'),
    trim($buyer['email']), /* cc */ getConfValue('con', 'regconfirmcc', null),
    /* subject */ $condata['label']. " Online Registration Receipt",  $body, /* htmlbody */ null);

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}

if (array_key_exists('email_error', $return_arr)) {
    $error_msg = $return_arr['email_error'];
} else {
    $error_msg = null;
}
$response = array(
  "status"=>$return_arr['status'],
  "url"=>$ccrtn['url'],
  "data"=> $error_msg,
  "email"=>$return_arr,
  "trans"=>$transId,
  "email_error"=>$error_code
);
//labeled_error_log('makePurchase-response', $response);
ajaxSuccess($response);

// cleanup up on a credit card failure (order or payment)
function cleanRegs($badges, $transid) {
    $delReg = <<<EOS
DELETE FROM reg
WHERE id = ?;
EOS;

    $delInterests = <<<EOS
DELETE FROM memberInterests
WHERE newperid = ?;
EOS;

    $delPolicies = <<<EOS
DELETE FROM memberPolicies
WHERE newperid = ?;
EOS;

    $clrNewperson = <<<EOS
UPDATE newperson
SET transid = NULL
WHERE id = ?;
EOS;

    $clrTransaction = <<<EOS
UPDATE transaction
SET newperid = NULL
WHERE newperid = ?;
EOS;

    $delNewperson = <<<EOS
DELETE FROM newperson
WHERE id = ?;
EOS;

    $delTransaction = <<<EOS
DELETE FROM transaction
WHERE id = ?;
EOS;


// first the regs
    foreach ($badges as $badge) {
        $regId = $badge['regId'];
        // delete the reg entry
        $numDel = dbSafeCmd($delReg, 'i', array ($regId));
    }

    // now the new perid
    foreach ($badges as $badge) {
        if (array_key_exists('id', $badge)) {
            $newPerid = $badge['id'];
            // clear the newperson entry
            $numDel = dbSafeCmd($clrNewperson, 'i', array ($newPerid));
            $numDel = dbSafeCmd($clrTransaction, 'i', array ($newPerid));
            $numDel = dbSafeCmd($delInterests, 'i', array ($newPerid));
            $numDel = dbSafeCmd($delPolicies, 'i', array ($newPerid));

            // delete the newperson entry
            $numDel = dbSafeCmd($delNewperson, 'i', array ($newPerid));
        }
    }
    // delete the transaction
    $numDel = dbSafeCmd($delTransaction, 'i', array ($transid));
}
