<?php

/* split of the old make Purchase into two parts, so square and stripe can re-do credit card auth issues
 */
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

if (!isset($_POST) || !isset($_POST['badges'])) {
    ajaxSuccess(array('status'=>'error', 'error'=>"Error: No Info Passed")); exit();
}

// input parameters
try {
    $badgestruct = json_decode($_POST['badges'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

$action = $_POST['action'];

if (array_key_exists('couponCode', $_POST)) {
    $couponCode = $_POST['couponCode'];
    if ($couponCode == '')
        $couponCode = null;
    if (array_key_exists('couponSerial', $_POST)) {
        $couponSerial = $_POST['couponSerial'];
        if ($couponSerial == '')
            $couponSerial = null;
    } else
        $couponSerial = null;
    }
else {
    $couponCode = null;
    $couponSerial = null;
}
$nonce = $_POST['nonce'];
$purchaseform = $_POST['purchaseform'];
$badges = $badgestruct['badges'];
$webtotal = $badgestruct['total'];
$couponDiscount = 0;
$couponSubtotal = $webtotal;
if ($couponCode != null) {
    if (array_key_exists('couponDiscount', $_POST)) {
        $couponDiscount = $_POST['couponDiscount'];
    }
    if (array_key_exists('couponSubtotal', $_POST)) {
        $couponSubtotal = $_POST['couponSubtotal'];
    }
}

if (array_key_exists('total', $_POST)) {
    $totalDue = $_POST['total'];
} else {
    $totalDue = $webtotal;
}

if (count($badges) == 0) {
    ajaxSuccess(array('status' => 'error', 'error' => 'Error: No Badges Entered'));
    exit();
}

if (!filter_var($purchaseform['cc_email'], FILTER_VALIDATE_EMAIL)) {
    ajaxSuccess(array('status' => 'error', 'error' => 'Error: Invalid Receipt Email passed, use "Edit" button and enter a valid email address for the receipt'));
    exit();
}

$ccauth = get_conf('cc');
load_cc_procs();
load_email_procs();

$condata = get_con();
$log = get_conf('log');
$cc = get_conf('cc');
if (array_key_exists('location_portal', $cc)) {
    $ccLocation = $cc['location_portal'];
} else if (array_key_exists('location', $cc)) {
    $ccLocation = $cc['location'];
} else {
    $ccLocation = 'Unknown';
}
$conid = $condata['id'];
logInit($log['reg']);
$source = 'onlinereg';
//labeled_error_log("makePurchase-badgestruct", $badgestruct);
//labeled_error_log("makePurchase-couponCode", $couponCode);
//labeled_error_log("makePurchase-nonce", $nonce);
//labeled_error_log("makePurchase-purchaseform", $purchaseform);

// we now have an array of badges, it needs to be priced, checked for rules, and have coupons applied.
// first load all the data to process the items
$data = loadPurchaseData($conid, $couponCode, $couponSerial);
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

// all of the data is now loaded

$people = array();

$data = computePurchaseTotals($coupon, $badges, $primary, $counts, $prices, $map, $discounts, $mtypes, $memCategories);

$maxMbrDiscounts = $data['origMaxMbrDiscounts'];
$apply_discount = $data['applyDiscount'];
$preDiscount = $data['preDiscount'];
$total = $data['total'];
$totalDiscount = $data['totalDiscount'];

if ($couponSubtotal != $preDiscount) {
    error_log('bad total: post=' . $webtotal . ', calc=' . $preDiscount);
    ajaxSuccess(array ('status' => 'error', 'error' => 'Unable to process, bad total sent to Server'));
    exit();
}

if ($coupon != null) {
    if ($totalDiscount != $couponDiscount) {
        error_log('bad coupon discount: post=' . $couponDiscount . ', calc=' . $totalDiscount);
        ajaxSuccess(array ('status' => 'error', 'error' => 'Unable to process, bad coupon data sent to Server'));
        exit();
    }
}

// now process the people and the memberships to add them to the tables
$npInsertQ = <<<EOS
INSERT INTO newperson(last_name, middle_name, first_name, suffix, legalName, pronouns, email_addr, phone,
    badge_name, badgeNameL2, address, addr_2, city, state, zip, country, contact_ok, share_reg_ok, currentAgeType, currentAgeConId)
    VALUES(IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''),
           IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''),  ?, ?, ?, ?);
EOS;

$intInsertQ = <<<EOS
INSERT INTO memberInterests(newperid, conid, interest, interested, notes)
VALUES (?, ?, ?, ?, ?);
EOS;

$polInsertQ = <<<EOS
INSERT INTO memberPolicies(conid, newperid, policy, response)
VALUES(?, ?, ?, ?);
EOS;

$count = 0;
foreach ($badges as $badge) {
    if (!isset($badge) || !isset($badge['memId'])) {
        continue;
    }
    if (array_key_exists($badge['memId'], $counts)) {
        $discount = 0;
        if ($apply_discount && $primary[$badge['memId']]) {
            if ($maxMbrDiscounts > 0) {
                $discount = $discounts[$badge['memId']];
                $maxMbrDiscounts--;
            }
        }
        $people[$count] = array(
            'info' => $badge,
            'price' => $prices[$badge['memId']],
            'memId' => $badge['memId'],
            'coupon' => $coupon,
            'discount' => $discount,
        );

        if (array_key_exists('share', $badge) && $badge['share'] == "") {
            $badge['share'] = 'Y';
        }
        if (array_key_exists('contact', $badge) && $badge['contact'] == "") {
            $badge['contact'] = 'Y';
        }

        // fix the badge phone number to remove the L-R character if present
        $phone = trim($badge['phone']);
        if ($phone != null && $phone != '') {
            $phone = preg_replace('/' . mb_chr(0x202d) . '/', '',  $phone);
            $badge['phone'] = $phone;
        }

        $value_arr = array(
            trim($badge['lname']),
            trim($badge['mname']),
            trim($badge['fname']),
            trim($badge['suffix']),
            trim($badge['legalName']),
            trim($badge['pronouns']),
            trim($badge['email1']),
            trim($badge['phone']),
            trim($badge['badge_name']),
            trim($badge['badgeNameL2']),
            trim($badge['addr']),
            trim($badge['addr2']),
            trim($badge['city']),
            trim($badge['state']),
            trim($badge['zip']),
            $badge['country'],
            array_key_exists('contact', $badge) ? $badge['contact'] : 'Y',
            array_key_exists('share', $badge) ? $badge['share'] :'Y',
            $badge['age'] == '' ? null : $badge['age'],
            $conid
        );

        $newid = dbSafeInsert($npInsertQ, 'sssssssssssssssssssi', $value_arr);
        $people[$count]['newperid'] = $newid;
        $count++;
    } else {
        ajaxSuccess(array('status' => 'error', 'badges' => $badges, 'error' => "Error: invalid badge age category"));
        exit();
    }

    // now do policies and interests
    $policies = $badge['policyInterest'];
    foreach ($policies as $key => $resp) {
        if (str_starts_with($key, 'p_')) {
            $key = substr($key, 2);
            $polid = dbSafeInsert($polInsertQ, 'iiss', array($conid, $newid, $key, 'Y'));
        } else {
            if (str_ends_with($key, '_notes'))
                continue;
            $nkey = $key . '_notes';
            if (array_key_exists($nkey, $policies))
                $notes = $policies[$nkey];
            else
                $notes = null;
            $intid = dbSafeInsert($intInsertQ, 'iiss', array($conid, $newid, $key, 'Y', $notes));
        }
    }
}

$transQ = <<<EOS
INSERT INTO transaction(newperid, price, couponDiscountReg, couponDiscountCart, type, conid, coupon)
    VALUES(?, ?, ?, ?, ?, ?, ?);
EOS;
if ($coupon == null)
    $cid = null;
else
    $cid = $coupon['id'];

$transId= dbSafeInsert($transQ, "idddsii", array($people[0]['newperid'], $preDiscount, $totalDiscount, 0, 'website', $conid, $cid));

$person_update = "UPDATE newperson SET transid=? WHERE id = ?;";
// This dbQuery is all internal veriables, (id's returned by the database functions) so the Safe version is not needed.
$numupd = dbSafeCmd($person_update, 'is', array($transId, $newid));

$badgeQ = <<<EOS
INSERT INTO reg(conid, newperid, create_trans, status, price, couponDiscount, coupon, memID)
VALUES(?, ?, ?, ?, ?, ?, ?, ?);
EOS;
$badge_types = "iiisddii";

foreach($people as $person) {
    $badge_data = array(
      $conid,
      $person['newperid'],
      $transId,
      $person['price'] > 0 ? 'unpaid' : 'paid',
      $person['price'],
      $person['discount'],
      $cid,
      $person['memId'],
      );

  $badgeId=dbSafeInsert($badgeQ, $badge_types, $badge_data);
}

$all_badgeQ = <<<EOS
SELECT R.id AS badge, R.id AS regId,
    NP.first_name AS fname, NP.middle_name AS mname, NP.last_name AS lname, NP.suffix AS suffix, NP.legalName,
    NP.email_addr AS email,
    NP.address AS street, NP.city AS city, NP.state AS state, NP.zip AS zip, NP.country AS country,
    NP.id as id, R.price AS price, R.couponDiscount as discount, M.memAge AS age, NP.badge_name, NP.badgeNameL2, R.memId, M.glNum,
    M.label, M.memCategory, M.memType, M.taxable, M.ageShortName AS ageshortname, M.memAge, M.shortname
FROM newperson NP
JOIN reg R ON (R.newperid=NP.id)
JOIN memLabel M ON (M.id = R.memID)
WHERE NP.transid=?;
EOS;

$all_badgeR = dbSafeQuery($all_badgeQ, "i", array($transId));

$badgeResults = [];
while ($row = $all_badgeR->fetch_assoc()) {
  $badgeResults[] = $row;
}

$taxList = getTaxRates();

$custId = "onlinereg-$transId";
$results = array(
    'custid' => $custId,
    'source' => $source,
    'transid' => $transId,
    'counts' => $counts,
    'price' => $totalDue,
    'badges' => $badgeResults,
    'total' => $total,
    'coupon' => $coupon,
    'discount' => $totalDiscount,
    'taxList' => $taxList,
);

// end compute, create the order if there is something to pay
if ($total > 0) {
    //log requested badges
    labeled_logWrite('o/makePurchase-pre-buldOrder',
        array ('con' => $condata['name'], 'trans' => $transId, 'results' => $results, 'request' => $badges));

    $rtn = cc_buildOrder($results, true, $ccLocation);
    if ($rtn == null) {
        // note there is no reason cc_buildOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
        labeled_logWrite('o/makePurchase-cc_buildOrder returned null',
            array ('con' => $condata['name'], 'trans' => $transId, 'error' => 'Order unable to be created'));
        ajaxSuccess(array ('status' => 'error', 'error' => 'Order not built, seek assistance'));
        exit();
    }
    $response['orderRtn'] = $rtn;
    labeled_logWrite('o/makePurchase-order created',
        array ('status' => 'order create', 'con' => $condata['name'], 'trans' => $transId, 'ccrtn' => $rtn));

    $buyer['email'] = $purchaseform['cc_email'];
    $buyer['phone'] = '';
    $buyer['country'] = '';
    $referenceId = $transId . '-' . 'pay-' . time();
    $preTaxAmt = $rtn['preTaxAmt'];
    $taxAmt = $rtn['taxAmt'];
    if ($taxAmt > 0)
        $taxes = $rtn['taxes'];
    else
        $taxes = [];
    $withTax = $rtn['totalAmt'];

    $results = array (
        'source' => $source,
        'nonce' => $nonce,
        'totalAmt' => $withTax,
        'orderId' => $rtn['orderId'],
        'custid' => $custId,
        'locationId' => $ccLocation,
        'referenceId' => $referenceId,
        'transid' => $transId,
        'preTaxAmt' => $preTaxAmt,
        'taxAmt' => $taxAmt,
        'total' => $withTax,
        'taxList' => $taxList,
        'taxes' => $taxes,
        'badges' => $badgeResults,
        'buyer' => $buyer,
    );
    $response['results'] = $results;

    $typeStr = 'dddds';
    $valArray = array ($preTaxAmt, $taxAmt, $withTax, 0, $rtn['orderId']);
    [$taxSql, $taxStr, $taxValues] = buildTaxUpdate($taxes);
    $upT = <<<EOS
UPDATE transaction
SET price = ?, tax = ?, withTax = ?, couponDiscountCart = ?, orderId = ?, paymentStatus = 'ORDER', orderDate = now(), $taxSql
WHERE id = ?;
EOS;
    $typeStr .= $taxStr . 'i';
    $valArray = array_merge($valArray, $taxValues);
    $valArray[] = $transId;

    $rows_upd = dbSafeCmd($upT, $typeStr, $valArray);

// return and let the caller call the payment side
    $response['nextStep'] = 'payment';
    ajaxSuccess($response);
    exit();
}

// build everything for no payment required (should this also move to pay order and do it there in one place instead of being in both?

$approved_amt = 0;
$withTax = 0;
$ccrtn = array('url' => '');

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

if ($total > 0) {
    $body = getEmailBody($transId, $totalDiscount);
}
else {
    $body = getNoChargeEmailBody($results, $totalDiscount);
}

$return_arr = send_email(getConfValue('con', 'regadminemail'),
    trim($purchaseform['cc_email']), /* cc */ getConfValue('con', 'regconfirmcc', null),
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
    "nextStep"=>'receipt',
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
