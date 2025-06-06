<?php
require_once('../lib/base.php');
require_once("../../lib/log.php");
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
$badgestruct = $_POST['badges'];
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
$policyInterestForm = $_POST['policyInterestForm'];
$badges = $badgestruct['badges'];
$webtotal = $badgestruct['total'];
$couponDiscount = null;
if ($couponCode == null) {
    $couponSubtotal = $webtotal;
} else {
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
$con = get_conf('con');
$conid = $condata['id'];
logInit($log['reg']);
//web_error_log("badgestruct");
//var_error_log($badgestruct);
//web_error_log("couponCode");
//var_error_log($couponCode);
//web_error_log("nonce");
//var_error_log($nonce);
//web_error_log("purchaseform");
//var_error_log($purchaseform);

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
$newid_list = '';

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

// see if there is an exact match

// now resolve exact matches
        $exactMsql = <<<EOF
SELECT id
FROM perinfo p
WHERE
	REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.first_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.middle_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.last_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.suffix, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.email_addr, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.phone, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.badge_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.address, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.addr_2, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.city, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.state, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.zip, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.country, ''))), '  *', ' ');
EOF;
        $value_arr = array(
            trim($badge['fname']),
            trim($badge['mname']),
            trim($badge['lname']),
            trim($badge['suffix']),
            trim($badge['email1']),
            trim($badge['phone']),
            trim($badge['badgename']),
            trim($badge['addr']),
            trim($badge['addr2']),
            trim($badge['city']),
            trim($badge['state']),
            trim($badge['zip']),
            $badge['country']
        );

        $res = dbSafeQuery($exactMsql, 'sssssssssssss', $value_arr);
        if ($res !== false) {
            if ($res->num_rows > 0) {
                $match = $res->fetch_assoc();
                $id = $match['id'];
            } else {
                $id = null;
            }
        } else {
            $id = null;
        }
        $value_arr = array(
            trim($badge['lname']),
            trim($badge['mname']),
            trim($badge['fname']),
            trim($badge['suffix']),
            trim($badge['legalname']),
            trim($badge['pronouns']),
            trim($badge['email1']),
            trim($badge['phone']),
            trim($badge['badgename']),
            trim($badge['addr']),
            trim($badge['addr2']),
            trim($badge['city']),
            trim($badge['state']),
            trim($badge['zip']),
            $badge['country'],
            array_key_exists('contact', $badge) ? $badge['contact'] : 'Y',
            array_key_exists('share', $badge) ? $badge['share'] :'Y',
            $id
        );

        $insertQ = <<<EOS
INSERT INTO newperson(last_name, middle_name, first_name, suffix, legalName, pronouns, email_addr, phone,
    badge_name, address, addr_2, city, state, zip, country, contact_ok, share_reg_ok, perid)
    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

        $newid = dbSafeInsert($insertQ, 'sssssssssssssssssi', $value_arr);
        $people[$count]['newperid'] = $newid;
        $people[$count]['perid'] = $id;

        $newid_list .= "id='$newid' OR ";

        $count++;
    } else {
        ajaxSuccess(array('status' => 'error', 'badges' => $badges, 'error' => "Error: invalid badge age category"));
        exit();
    }
}

$transQ = <<<EOS
INSERT INTO transaction(newperid, perid, price, couponDiscountReg, couponDiscountCart, type, conid, coupon)
    VALUES(?, ?, ?, ?, ?, ?, ?, ?);
EOS;
if ($coupon == null)
    $cid = null;
else
    $cid = $coupon['id'];

$transid= dbSafeInsert($transQ, "iidddsii", array($people[0]['newperid'], $id, $preDiscount, $totalDiscount, 0, 'website', $condata['id'], $cid));

$newid_list .= "transid='$transid'";

$person_update = "UPDATE newperson SET transid='$transid' WHERE $newid_list;";
// This dbQuery is all internal veriables, (id's returned by the database functions) so the Safe version is not needed.
dbQuery($person_update);

$badgeQ = <<<EOS
INSERT INTO reg(conid, newperid, perid, create_trans, status, price, couponDiscount, coupon, memID)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
$badge_types = "iiiisddii";

foreach($people as $person) {
    $badge_data = array(
      $condata['id'],
      $person['newperid'],
      $person['perid'],
      $transid,
      $person['price'] > 0 ? 'unpaid' : 'paid',
      $person['price'],
      $person['discount'],
      $cid,
      $person['memId'],
      );

  $badgeId=dbSafeInsert($badgeQ, $badge_types, $badge_data);
}

$all_badgeQ = <<<EOS
SELECT R.id AS badge,
    NP.first_name AS fname, NP.middle_name AS mname, NP.last_name AS lname, NP.suffix AS suffix, NP.legalName AS legalName,
    NP.email_addr AS email,
    NP.address AS street, NP.city AS city, NP.state AS state, NP.zip AS zip, NP.country AS country,
    NP.id as id, R.price AS price, R.couponDiscount as discount, M.memAge AS age, NP.badge_name AS badgename, R.memId, M.glNum
FROM newperson NP
JOIN reg R ON (R.newperid=NP.id)
JOIN memList M ON (M.id = R.memID)
WHERE NP.transid=?;
EOS;

$all_badgeR = dbSafeQuery($all_badgeQ, "i", array($transid));

$badgeResults = [];
while ($row = $all_badgeR->fetch_assoc()) {
  $badgeResults[] = $row;
}

$results = array(
    'transid' => $transid,
    'counts' => $counts,
    'price' => $totalDue,
    'tax' => 0,
    'pretax' => $totalDue,
    'badges' => $badgeResults,
    'total' => $total,
    'nonce' => $nonce,
    'coupon' => $coupon,
    'discount' => $totalDiscount,
);

//log requested badges
logWrite(array('con'=>$condata['name'], 'trans'=>$transid, 'results'=>$results, 'request'=>$badges));
if ($total > 0) {
    $email = $purchaseform['cc_email'];
    $phone = '';
    if ($email == '/r')
        $email = '';
    $rtn = cc_charge_purchase($results, $email, $phone, true);
    if ($rtn === null) {
        // note there is no reason cc_charge_purchase will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
        logWrite(array('con'=>$condata['name'], 'trans'=>$transid, 'error' => 'Credit card transaction not approved'));
        ajaxSuccess(array('status' => 'error', 'error' => 'Credit card not approved'));
        exit();
    }

//$tnx_record = $rtn['tnx'];
    logWrite(array('con'=>$condata['name'], 'trans'=>$transid, 'ccrtn'=>$rtn));
    $num_fields = sizeof($rtn['txnfields']);
    $val = array();
    for ($i = 0; $i < $num_fields; $i++) {
        $val[$i] = '?';
    }
    $txnQ = "INSERT INTO payments(time," . implode(',', $rtn['txnfields']) . ') VALUES(current_time(),' . implode(',', $val) . ');';
    $txnT = implode('', $rtn['tnxtypes']);
    $txnid = dbSafeInsert($txnQ, $txnT, $rtn['tnxdata']);
    $approved_amt = $rtn['amount'];
} else {
    $approved_amt = 0;
    $rtn = array('url' => '');
}

if ($totalDiscount > 0) {
    // Insert the payment record for the coupon
    $ipQ = <<<EOS
INSERT INTO payments(transid, type, category, description, source, pretax, tax, amount, time, status) 
VALUES (?, 'coupon', 'reg', ?, 'online', ?, 0, ?, now(), 'APPLIED');
EOS;
    $couponDesc = $coupon['id'] . ':' . $coupon['code'] . ' - ' . $coupon['name'];
    $cpmtID = dbSafeInsert($ipQ, 'isdd', array($transid, $couponDesc, $totalDiscount, $totalDiscount));
    $coupon['totalDiscount'] = $totalDiscount;
}

$txnUpdate = "UPDATE transaction SET ";
if($approved_amt == $total) {
    $txnUpdate .= "complete_date=current_timestamp(), ";
}

$txnUpdate .= "paid=?, couponDiscountCart = ?, coupon = ? WHERE id=?;";
if ($totalDiscount > 0)
    $couponId = $coupon['id'];
else
    $couponId = null;
$txnU = dbSafeCmd($txnUpdate, "ddii", array($approved_amt, $totalDiscount, $couponId, $transid) );

$regQ = "UPDATE reg SET paid=price-couponDiscount, complete_trans = ?, status = 'paid' WHERE create_trans=?;";
dbSafeCmd($regQ, "ii", array($transid, $transid));

// mark coupon used
if ($coupon !== null && $coupon['keyId'] !== null) {
    $cupQ = 'UPDATE couponKeys SET usedBy = ?, useTS = current_timestamp WHERE id = ?';
    dbSafeCmd($cupQ, 'ii', array($transid, $coupon['keyId']));
}

// insert policies
$policies = getPolicies();
$iQ = <<<EOS
INSERT INTO memberPolicies(conid, newperid, policy, response)
VALUES (?,?,?,?);
EOS;

if ($policies != null) {
    $policy_upd = 0;
    foreach ($policies as $policy) {
        $policyName = $policy['policy'];
        $newVal = array_key_exists('p_' . $policyName, $policyInterestForm) ? 'Y' : 'N';
        $ins_key = dbSafeInsert($iQ, 'iiss', array($conid, $newid, $policyName, $newVal));
        if ($ins_key !== false) {
            $policy_upd++;
        }
    }
}

// insert interests
$insInterest = <<<EOS
INSERT INTO memberInterests(newperid, conid, interest, interested)
VALUES (?, ?, ?, ?);
EOS;

$rows_upd = 0;
$interests = getInterests();
if ($interests != null) {
    foreach ($interests as $interest) {
        $interestName = $interest['interest'];
        $newVal = array_key_exists($interestName, $policyInterestForm) ? 'Y' : 'N';
        // row doesn't exist in existing interests
        $newkey = dbSafeInsert($insInterest, 'iiss', array ($newid, $conid, $interestName, $newVal));
        if ($newkey !== false && $newkey > 0)
            $rows_upd++;
    }
}

if ($total > 0) {
    $body = getEmailBody($transid, $totalDiscount);
}
else {
    $body = getNoChargeEmailBody($results, $totalDiscount);
}

$regconfirmcc = null;
if (array_key_exists('regconfirmcc', $con)) {
    $regconfirmcc = trim($con['regconfirmcc']);
    if ($regconfirmcc == '')
        $regconfirmcc = null;
}
$return_arr = send_email($con['regadminemail'], trim($purchaseform['cc_email']), /* cc */ $regconfirmcc, $condata['label']. " Online Registration Receipt",  $body, /* htmlbody */ null);

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
  "url"=>$rtn['url'],
  "data"=> $error_msg,
  "email"=>$return_arr,
  "trans"=>$transid,
  //"email"=>$email_msg,
  "email_error"=>$error_code
);
//var_error_log($response);
ajaxSuccess($response);
?>
