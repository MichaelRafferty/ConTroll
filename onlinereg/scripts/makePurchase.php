<?php
require_once('../lib/base.php');
require_once("../../lib/log.php");
require_once("../../lib/cc__load_methods.php");
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
$badges = $badgestruct['badges'];
$webtotal = $badgestruct['total'];

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
logInit($log['reg']);
//web_error_log("badgestruct");
//var_error_log($badgestruct);
//web_error_log("couponCode");
//var_error_log($couponCode);
//web_error_log("nonce");
//var_error_log($nonce);
//web_error_log("purchaseform");
//var_error_log($purchaseform);


// get the membership prices
$prices = array();
$memId = array();
$counts = array();
$discounts = array();
$primary = array();
$map = array();

$priceQ = <<<EOQ
SELECT m.id, m.memGroup, m.label, m.shortname, m.price, m.memCategory
FROM memLabel m
WHERE
    m.conid=?
    AND m.online = 'Y'
    AND startdate <= current_timestamp()
    AND enddate > current_timestamp()
;
EOQ;
$mtypes = array();
$priceR = dbSafeQuery($priceQ, 'i', array($condata['id']));
while($priceL = $priceR->fetch_assoc()) {
    $mtypes[$priceL['id']] = $priceL;
}

// get the coupon data, if any
$coupon = null;
if ($couponCode !== null) {
    $result = load_coupon_data($couponCode, $couponSerial);
    if ($result['status'] == 'error') {
        ajaxSuccess($result);
        exit();
    }
    $coupon = $result['coupon'];
    if (array_key_exists('mtypes', $result))
        $mtypes = $result['mtypes'];
    //web_error_log("coupon:");
    //var_error_log($coupon);
}

// now apply the price discount to the array
if ($coupon !== null) {
    $mtypes =  apply_coupon_data($mtypes, $coupon);
}

foreach ($mtypes as $id => $mbrtype) {
    $map[$mbrtype['id']] = $mbrtype['id'];
    $prices[$mbrtype['id']] = $mbrtype['price'];
    $memId[$mbrtype['id']] = $mbrtype['id'];
    $counts[$mbrtype['id']] = 0;
    $isprimary = (!$mbrtype['price'] == 0 || ($mbrtype['memCategory'] != 'standard' && $mbrtype['memCategory'] != 'virtual'));
    if ($coupon !== null) {
        $discounts[$mbrtype['id']] = $mbrtype['discount'];
        if ($coupon['memId'] == $mbrtype['id']) {  // ok this is a forced primary
            $isprimary = true; // need a statement here, as combining the if's gets difficult
        }
    }
    $primary[$mbrtype['id']] = $isprimary;
}

$num_primary = 0;
$total = 0;
// compute the pre-discount total to see if the ca
foreach ($badges as $badge) {
    if(!isset($badge) || !isset($badge['memType'])) { continue; }
    if (array_key_exists($badge['memType'], $counts)) {
        if ($primary[$badge['memType']]) {
            $num_primary++;
        }
        $total += $prices[$badge['memType']];
        $counts[$badge['memType']]++;
    }
}


// now figure out if coupon applies
$apply_discount = coupon_met($coupon, $total, $num_primary, $map, $counts);

$people = array();

$total = 0;
$preDiscount = 0;
$count = 0;
$totalDiscount = 0;
$maxMbrDiscounts = 0;
if ($coupon != NULL) {
    if (array_key_exists('maxMemberships', $coupon)) {
        $maxMbrDiscounts = $coupon['maxMemberships'] != null ? $coupon['maxMemberships'] : 999999;
    }
}
$newid_list = "";
$origMaxMbrDiscounts = $maxMbrDiscounts;

// check that we got valid total from the post before anything is inserted into the database, the empty rows are deleted badges from the site
foreach ($badges as $badge) {
    if(!isset($badge) || !isset($badge['memType'])) { continue; }
    if (array_key_exists($badge['memType'], $counts)) {
        $price = $prices[$badge['memType']];
        $preDiscount += $price;
        if ($apply_discount && $primary[$badge['memType']]) {
            if ($maxMbrDiscounts > 0) {
                $price -= $discounts[$badge['memType']];
                $maxMbrDiscounts--;
                $totalDiscount += $discounts[$badge['memType']];
            }
        }
        $total += $price;
    }
}
if ($apply_discount) {
    $discount = apply_overall_discount($coupon, $total);
    $total -= $discount;
    $totalDiscount += $discount;
}

$total = round($total, 2);

if($webtotal != $preDiscount) {
    error_log("bad total: post=" . $webtotal . ", calc=" . $total);
    ajaxSuccess(array('status'=>'error', 'error'=>'Unable to process, bad total sent to Server'));
    exit();
}

$maxMbrDiscounts = $origMaxMbrDiscounts;
foreach ($badges as $badge) {
    if (!isset($badge) || !isset($badge['memType'])) {
        continue;
    }
    if (array_key_exists($badge['memType'], $counts)) {
        $discount = 0;
        if ($apply_discount && $primary[$badge['memType']]) {
            if ($maxMbrDiscounts > 0) {
                $discount = $discounts[$badge['memType']];
                $maxMbrDiscounts--;
            }
        }
        $people[$count] = array(
            'info' => $badge,
            'price' => $prices[$badge['memType']],
            'memId' => $memId[$badge['memType']],
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

        // this line is causing problems in full utf8 systems, need to better understand why it was added, for now its removed.
        //$value_arr = mb_convert_encoding($value_arr, 'Windows-1252', 'UTF-8');

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
        // this line is causing problems in full utf8 systems, need to better understand why it was added, for now its removed.
        // $value_arr = mb_convert_encoding($value_arr, 'Windows-1252', 'UTF-8');

        $insertQ = <<<EOS
INSERT INTO newperson(last_name, middle_name, first_name, suffix, legalName, email_addr, phone,
    badge_name, address, addr_2, city, state, zip, country, contact_ok, share_reg_ok, perid)
    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

        $newid = dbSafeInsert($insertQ, 'ssssssssssssssssi', $value_arr);
        $people[$count]['newid'] = $newid;
        $people[$count]['perid'] = $id;

        $newid_list .= "id='$newid' OR ";

        $count++;
    } else {
        ajaxSuccess(array('status' => 'error', 'badges' => $badges, 'error' => "Error: invalid badge age category"));
        exit();
    }
}

$transQ = <<<EOS
INSERT INTO transaction(newperid, perid, price, couponDiscount, type, conid, coupon)
    VALUES(?, ?, ?, ?, ?, ?, ?);
EOS;
if ($coupon == null)
    $cid = null;
else
    $cid = $coupon['id'];

$transid= dbSafeInsert($transQ, "iiddsii", array($people[0]['newid'], $id, $preDiscount, $totalDiscount, 'website', $condata['id'], $cid));

$newid_list .= "transid='$transid'";

$person_update = "UPDATE newperson SET transid='$transid' WHERE $newid_list;";
// This dbQuery is all internal veriables, (id's returned by the database functions) so the Safe version is not needed.
dbQuery($person_update);

$badgeQ = <<<EOS
INSERT INTO reg(conid, newperid, perid, create_trans, price, couponDiscount, coupon, memID)
VALUES(?, ?, ?, ?, ?, ?, ?, ?);
EOS;
$badge_types = "iiiiddii";

foreach($people as $person) {
    $badge_data = array(
      $condata['id'],
      $person['newid'],
      $person['perid'],
      $transid,
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
    NP.id as id, R.price AS price, R.couponDiscount as discount, M.memAge AS age, NP.badge_name AS badgename, R.memId
FROM newperson NP
JOIN reg R ON (R.newperid=NP.id)
JOIN memList M ON (M.id = R.memID)
WHERE NP.transid=?;
EOS;

$all_badgeR = dbSafeQuery($all_badgeQ, "i", array($transid));

$badgeResults = array();
while ($row = $all_badgeR->fetch_assoc()) {
  $badgeResults[count($badgeResults)] = $row;
}

$results = array(
    'transid' => $transid,
    'counts' => $counts,
    'price' => $total,
    'tax' => 0,
    'pretax' => $total,
    'badges' => $badgeResults,
    'total' => $total,
    'nonce' => $nonce,
    'coupon' => $coupon,
    'discount' => $totalDiscount,
);

//log requested badges
logWrite(array('con'=>$condata['name'], 'trans'=>$transid, 'results'=>$results, 'request'=>$badges));
if ($total > 0) {
    $rtn = cc_charge_purchase($results, $ccauth, true);
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

$txnUpdate = "UPDATE transaction SET ";
if($approved_amt == $total) {
    $txnUpdate .= "complete_date=current_timestamp(), ";
}

$txnUpdate .= "paid=?, couponDiscount = ? WHERE id=?;";
$txnU = dbSafeCmd($txnUpdate, "ddi", array($approved_amt, $totalDiscount, $transid) );

$regQ = "UPDATE reg SET paid=price-couponDiscount, complete_trans = ? WHERE create_trans=?;";
dbSafeCmd($regQ, "ii", array($transid, $transid));

// mark coupon used
if ($coupon !== null && $coupon['keyId'] !== null) {
    $cupQ = 'UPDATE couponKeys SET usedBy = ?, useTS = current_timestamp WHERE id = ?';
    dbSafeCmd($cupQ, 'ii', array($transid, $coupon['keyId']));
}

if ($total > 0) {
    $body = getEmailBody($transid);
}
else {
    $body = getNoChargeEmailBody($results);
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
