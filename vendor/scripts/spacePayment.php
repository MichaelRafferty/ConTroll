<?php
require_once('../lib/base.php');
require_once('../../lib/email__load_methods.php');
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/log.php');
require_once '../lib/email.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$vendor = $_SESSION['id'];

global $con;
$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$vendor_conf = get_conf('vendor');

$response['conid'] = $conid;

$ccauth = get_conf('cc');
load_cc_procs();
load_email_procs();

$log = get_conf('log');
logInit($log['vendors']);

$email = "no send attempt or a failure";

if(!isset($_SESSION['id'])) { ajaxSuccess(array('status'=>'error', 'message'=>'Session Failure')); exit; }

$venId = $_SESSION['id'];

$response = array("post" => $_POST, "get" => $_GET);

// which space purchased
if (!array_key_exists('item_purchased', $_POST)) {
    ajaxError("invalid calling sequence");
}
$priceId = $_POST['item_purchased'];

// get the specific information allowed
// get current vendor information
$vendorQ = <<<EOS
SELECT name, email, website, description, addr, addr2, city, state, zip, publicity, need_new
FROM vendors
WHERE id=?;
EOS;
$vendor = fetch_safe_assoc(dbSafeQuery($vendorQ, 'i', array($venId)));

// now the space  information for this item
$spaceQ = <<<EOS
SELECT v.id AS spaceid, v.shortname, v.name, v.memId, m.price AS memPrice,
    vp.id as priceid, vp.code, vp.description, vp.units, vp.price, vp.includedMemberships, vp.additionalMemberships
FROM vendorSpacePrices vp
JOIN vendorSpaces v ON (vp.spaceId = v.id)
JOIN memList m ON (v.memId = m.id)
WHERE vp.id = ?
EOS;
$space =  fetch_safe_assoc(dbSafeQuery($spaceQ, 'i', array($priceId)));

$membership_fields = array('fname' => 1, 'mname' => 0, 'lname' => 1, 'suffix' => 0, 'addr' => 1, 'addr2' => 0, 'city' => 1, 'state' => 1, 'zip' => 1,
    'country' => 1, 'email' => 1, 'phone' => 0, 'badgename' => 0);
$membership_names = array('fname' => 'First Name', 'mname' => 'Middle Name', 'lname' => 'Last Name', 'suffix' => 'Suffix', 'addr' => 'Address Line 1',
    'addr2' => 'Company/Address Line 2', 'city' => 'City', 'state' => 'State', 'zip' => 'Zip Code/Postal Code', 'country' => 'Country',
     'email' => 'Email Address', 'phone' => 'Phone Number', 'badgename' => 'Badge Name');

// validate the form, returning any errors on missing data
$includedMembershipStatus = array();
$missing_msg = "";
$valid = true;
for ($num = 0; $num < $space['includedMemberships']; $num++) {
    $notfound = array();
    $allfound = true;
    $allrequired = true;
    $nonefound = true;
    foreach($membership_fields as $field => $required) {
        if ($field == 'country')
            continue; // it's a pulldown, so it's always found and messes up required checks.

        $postfield = $field . '_i_' . $num;
        if (array_key_exists($postfield, $_POST)) {
            $val = trim($_POST[$postfield]);
        } else {
            $val = '';
        }
        if ($val != '') {
            $nonefound = false;
        } else {
            $allfound = false;
            if ($required) {
                $notfound[] = $membership_names[$field];
                $allrequired = false;
            }
        }
    }

    // for this included membership, must be either all or none found
    $includedMembershipStatus[$num] = $allfound;
    if ($nonefound || $allfound)  // both of these are valid cases
        continue;
    // some required data is missing
    $missing_msg .= "Included Membership " . $num + 1 . " is missing " . implode(',', $notfound) . "\n";
    $valid = false;
}

$additionalMembershipStatus = array();
for ($num = 0; $num < $space['additionalMemberships']; $num++) {
    $notfound = array();
    $allfound = true;
    $allrequired = true;
    $nonefound = true;
    foreach($membership_fields as $field => $required) {
        if ($field == 'country')
            continue; // it's a pulldown, so it's always found and messes up required checks.

        $postfield = $field . '_a_' . $num;
        if (array_key_exists($postfield, $_POST)) {
            $val = trim($_POST[$postfield]);
        } else {
            $val = '';
        }
        if ($val != '') {
            $nonefound = false;
        } else {
            $allfound = false;
            if ($required) {
                $notfound[] = $membership_names[$field];
                $allrequired = false;
            }
        }
    }

    // for this included membership, must be either all or none found
    $additionalMembershipStatus[$num] = $allfound;
    if ($nonefound || $allfound)  // both of these are valid cases
        continue;
    // some required data is missing
    $missing_msg .= 'Additional Membership ' . $num + 1 . ' is missing ' . implode(',', $notfound) . "\n";
    $valid = false;
}

// check email addresses
$email_addresses = [ 'cc_email' => 'Payment Information Email', 'email_i_0' => 'Included Membership 1 Email', 'email_i_1' => 'Included Membership 2 Email',
    'email_a_0' => 'Additional Membership 1 Email', 'email_a_1' => 'Additional Membership 2 Email'];

$invalidEmail_msg = '';
foreach ($email_addresses AS $email => $where) {
    if (array_key_exists($email, $_POST)) {
        $val = trim($_POST[$email]);
        if ($val != '') {
            if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                $invalidEmail_msg .= $where . " is not in the format of a valid email address\n";
                $valid = false;
            }
        }
    }
}
if (!$valid) {
    $response['error'] = "There were some issues with the data on the form.  Please correct and re-submit.\n\n$missing_msg\n$invalidEmail_msg\n";
    ajaxSuccess($response);
    return;
}
$status_msg = '';
// the form passes validation, lets try running it.
// first does the vendor profile need updating
if ($_POST['name'] != $vendor['name'] || $_POST['email'] != $vendor['email'] || $_POST['addr'] != $vendor['addr'] || $_POST['addr2'] != $vendor['addr2'] ||
    $_POST['city'] != $vendor['city'] ||  $_POST['state'] != $vendor['state'] || $_POST['zip'] != $vendor['zip']) {
    // something doesn't match update these fields
    $updateV = <<<EOS
UPDATE vendors
SET name=?, email=?, addr=?, addr2=?,city=?, state=?, zip=?
WHERE id=?;
EOS;
    $vendorA = array(trim($_POST['name']), trim($_POST['email']), trim($_POST['addr']), trim($_POST['addr2']), trim$_POST['city']), trim($_POST['state']),
        trim($_POST['zip']), $venId);
    $num_rows = dbSafeCmd($updateV, 'sssssssi',$vendorA);
    if ($num_rows == 1)
        $status_msg = "Vendor Profile Updated\n";
    else
        $status_msg = "Nothing to update in Vendor Profile\n";
}

// build the badges to insert into newperson and
//
if ($includedMembershipStatus[0]) {
    $badge = build_badge($membership_fields,'_i_0', $space);
}

$response['message'] =  $status_msg;
var_error_log($response);

ajaxSuccess($response);
return;

// build the badge structure and insert the person into newperson after checking for exact match
function build_badge($fields, $suffix, $space) {
    $badge = array();
    foreach ($fields as $field) {
        $badge[$field] = trim($_POST($field . $suffix));
    }
    $badge['age'] = 'all';
    $badge['price'] = $space['memPrice'];
    $badge['memId'] = $space['memId'];
    $badge['contact'] = 'Y';
    $badge['share'] = 'Y';

// now resolve exact matches in perinfo
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
    $value_arr = array($badge['fname'], $badge['mname'], $badge['lname'], $badge['suffix'], $badge['email1'], $badge['phone'], $badge['badgename'],
                $badge['addr'], $badge['addr2'], $badge['city'], $badge['state'], $badge['zip'], $badge['country']);
    $res = dbSafeQuery($exactMsql, 'sssssssssssss', $value_arr);
    if ($res !== false) {
        if ($res->num_rows > 0) {
            $match = fetch_safe_assoc($res);
            $id = $match['id'];
        } else {
            $id = null;
        }
    } else {
        $id = null;
    }
    $badge['perid'] = $id;
    $value_arr = array($badge['lname'], $badge['mname'], $badge['fname'], $badge['suffix'], $badge['email1'], $badge['phone'], $badge['badgename'],
        $badge['addr'], $badge['addr2'], $badge['city'], $badge['state'], $badge['zip'], $badge['country'], $badge['contact'], $badge['share'], $id);

    $insertQ = <<<EOS
INSERT INTO newperson(last_name, middle_name, first_name, suffix, email_addr, phone, badge_name,
                      address, addr_2, city, state, zip, country, contact_ok, share_reg_ok, perid)
    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

    $newid = dbSafeInsert($insertQ, 'sssssssssssssssi', $value_arr);
    $badge['newid'] = $newid;

    return $badge;
    }
}

$transQ = <<<EOS
INSERT INTO transaction(newperid, perid, price, type, conid)
    VALUES(?, ?, ?, ?, ?);
EOS;

$transid = dbSafeInsert($transQ, 'iidsi', array($people[0]['newid'], $id, $total, 'website', $condata['id']));

$newid_list .= "transid='$transid'";

$person_update = "UPDATE newperson SET transid='$transid' WHERE $newid_list;";
// This dbQuery is all internal veriables, (id's returned by the database functions) so the Safe version is not needed.
dbQuery($person_update);

$badgeQ = <<<EOS
INSERT INTO reg(conid, newperid, perid, create_trans, price, memID)
VALUES(?, ?, ?, ?, ?, ?);
EOS;
$badge_types = 'iiiidi';

foreach ($people as $person) {
    $badge_data = array(
        $condata['id'],
        $person['newid'],
        $id,
        $transid,
        $person['price'],
        $person['memId'],
    );

    $badgeId = dbSafeInsert($badgeQ, $badge_types, $badge_data);
}

$all_badgeQ = <<<EOS
SELECT R.id AS badge,
    NP.first_name AS fname, NP.middle_name AS mname, NP.last_name AS lname, NP.suffix AS suffix,
    NP.email_addr AS email,
    NP.address AS street, NP.city AS city, NP.state AS state, NP.zip AS zip, NP.country AS country,
    NP.id as id, R.price AS price, M.memAge AS age, NP.badge_name AS badgename
FROM newperson NP
JOIN reg R ON (R.newperid=NP.id)
JOIN memList M ON (M.id = R.memID)
WHERE NP.transid=?;
EOS;

$all_badgeR = dbSafeQuery($all_badgeQ, 'i', array($transid));

$badgeResults = array();
while ($row = fetch_safe_assoc($all_badgeR)) {
    $badgeResults[count($badgeResults)] = $row;
}


$results = array(
    'transid' => $transid,
    'counts' => $counts,
    'price' => $total,
    'badges' => $badgeResults,
    'total' => $total,
    'nonce' => $_POST['nonce']
);

//log requested badges
logWrite(array('con' => $condata['name'], 'trans' => $transid, 'results' => $results, 'request' => $badges));

$rtn = cc_charge_purchase($results, $ccauth);
if ($rtn === null) {
    ajaxSuccess(array('status' => 'error', 'data' => 'Credit card not approved'));
    exit();
}


//$vendorR = dbQuery("SELECT * from vendors where id=$venId;");
//$vendor = fetch_safe_assoc($vendorR);
//
//$body = "Vendor " . $vendor['name'] . " Paid an Invoice for $ " . $_POST['total']
//    . " covering " . $_POST['count'] . " " . $_POST['type'] . "'x". $_POST['type']. "' spaces and " . $_POST['mem_cnt'] . " additional memberships.  The information they included with the payment is below.\n\n";
//
//$total = $_POST['total'];
//
//foreach ($_POST as $key => $value) {
//    switch ($key) {
//        case 'vendor':
//        case 'total':
//        case 'nonce':
//        case 'nds-pmd':
//            break;
//        default:
//            $body .= "$key: $value\n";
//    };
//}
//
//$alley_priceQ = "SELECT type, price_full as price from vendor_reg where conid=$conid and type in ('dealer_6', 'dealer_10');";
//$alley_priceR = dbQuery($alley_priceQ);
//$prices = array();
//
//while($price = fetch_safe_assoc($alley_priceR)) {
//    $prices[$price['type']] = $price['price'];
//}
//
//
//$memRow = fetch_safe_assoc(dbQuery("SELECT id, price FROM memList WHERE conid=$conid and label='Vendor';"));
//
//$request = array(
//    'type' => $_POST['type'],
//    'dealer' => $_POST['count'],
//    'custid' => $_POST['vendor'],
//    'memberships' => $_POST['dealer_num_paid'],
//    'prices' => $prices,
//    'memPrice' => $memRow['price'],
//    'total' => $total,
//    'nonce' => $_POST['nonce']);
//
//$transid = dbInsert("INSERT INTO transaction (conid, price, paid, notes) VALUES ($conid, $total, 0, 'Dealers Purchase');");
//
//$request['transid'] = $transid;
//
//$response['request'] = $request;
///* */
//$rtn = cc_vendor_purchase($request);
//$response['purchase_plan']=$rtn;
//  if ($rtn === null) {
//    ajaxSuccess(array('status'=>'error', 'data'=>'Credit card not approved'));
//    exit();
//  }
///* * / ajaxSuccess($response); exit(); /* */
//
//$num_fields = sizeof($rtn['txnfields']);
//$val = array();
//for ($i = 0; $i < $num_fields; $i++) {
//    $val[$i] = '?';
//}
//
//
//$txnQ = "INSERT INTO payments(time," . implode(',', $rtn['txnfields']) . ') VALUES(current_time(),' . implode(',', $val) . ');';
//$txnT = implode('', $rtn['tnxtypes']);
//$txnid = dbSafeInsert($txnQ, $txnT, $rtn['tnxdata']);
//$approved_amt =  $rtn['amount'];
//
//
//$txnUpdate = "UPDATE transaction SET ";
//if($approved_amt == $total) {
//    $txnUpdate .= "complete_date=current_timestamp(), ";
//}
//$txnUpdate .= "paid=? WHERE id=?;";
//$txnU = dbSafeCmd($txnUpdate, "di", array($approved_amt, $transid) );
//
//$tableUpdate = "UPDATE vendor_show SET purchased='" . sql_safe($_POST['count'])
//    . "', price='" . sql_safe($_POST['table_sub']) . "', paid='"
//    . sql_safe($_POST['table_sub']) . "', transid='$transid'"
//    . " WHERE vendor=$venId and type='dealer_" .sql_safe($_POST['type'])."' and conid=$conid;";
//dbQuery($tableUpdate);
//$response['update'] = $tableUpdate;
//
//$regUpdate = "UPDATE vendor_reg SET registered=registered + ".sql_safe($_POST['count'])." WHERE conid=$conid and type='dealer_".sql_safe($_POST['type'])."';";
//dbQuery($regUpdate);
//
//
//$reg1 = "";
//$reg2 = "";
//
//$memId = $memRow['id'];
//
//if($_POST['dealer_mem1_lname'] != '') {
//  $newPeople = "INSERT INTO newperson (last_name, middle_name, first_name, badge_name, address, addr_2, city, state, zip, share_reg_ok, contact_ok) VALUES "
//    . '(\'' . sql_safe($_POST['dealer_mem1_lname']) . '\',\''
//          . sql_safe($_POST['dealer_mem1_mname']) . '\',\''
//          . sql_safe($_POST['dealer_mem1_fname']) . '\',\''
//          . sql_safe($_POST['dealer_mem1_bname']) . '\',\''
//          . sql_safe($_POST['dealer_mem1_address']) . '\',\''
//          . sql_safe($_POST['dealer_mem1_addr2']) . '\',\''
//          . sql_safe($_POST['dealer_mem1_city']) . '\',\''
//          . sql_safe($_POST['dealer_mem1_state']) . '\',\''
//          . sql_safe($_POST['dealer_mem1_zip']) . '\',\'Y\',\'N\');';
//  $per1 = dbInsert($newPeople);
//
//  $reg1 = "INSERT INTO reg (conid, newperid, price, paid, memId, create_trans) VALUES ($conid, $per1, ".$memRow['price'].", ".$memRow['price'].", $memId, $transid);";
//    $response['per1'] = $reg1;
//    dbQuery($reg1);
//}
//
//if($_POST['dealer_mem2_lname'] != '') {
//   $newPeople = "INSERT INTO newperson (last_name, middle_name, first_name, badge_name, address, addr_2, city, state, zip, share_reg_ok, contact_ok) VALUES "
//    . '(\'' . sql_safe($_POST['dealer_mem2_lname']) . '\',\''
//          . sql_safe($_POST['dealer_mem2_mname']) . '\',\''
//          . sql_safe($_POST['dealer_mem2_fname']) . '\',\''
//          . sql_safe($_POST['dealer_mem2_bname']) . '\',\''
//          . sql_safe($_POST['dealer_mem2_address']) . '\',\''
//          . sql_safe($_POST['dealer_mem2_addr2']) . '\',\''
//          . sql_safe($_POST['dealer_mem2_city']) . '\',\''
//          . sql_safe($_POST['dealer_mem2_state']) . '\',\''
//          . sql_safe($_POST['dealer_mem2_zip']) . '\',\'Y\',\'N\');';
//  $per2 = dbInsert($newPeople);
//
//
//  $reg2 = "INSERT INTO reg (conid, newperid, price, paid, memId, create_trans) VALUES ($conid, $per2, ".$memRow['price'].", ".$memRow['price'].", $memId, $transid);";
//    $response['per2'] = $reg2;
//    dbQuery($reg2);
//}
//
//if(($_POST['dealer_num_paid'] >= 1) and ($_POST['dealer_paid1_lname'] != '')) {
//   $newPeople = "INSERT INTO newperson (last_name, middle_name, first_name, badge_name, address, addr_2, city, state, zip, share_reg_ok, contact_ok) VALUES "
//    . '(\'' . sql_safe($_POST['dealer_paid1_lname']) . '\',\''
//          . sql_safe($_POST['dealer_paid1_mname']) . '\',\''
//          . sql_safe($_POST['dealer_paid1_fname']) . '\',\''
//          . sql_safe($_POST['dealer_paid1_bname']) . '\',\''
//          . sql_safe($_POST['dealer_paid1_address']) . '\',\''
//          . sql_safe($_POST['dealer_paid1_addr2']) . '\',\''
//          . sql_safe($_POST['dealer_paid1_city']) . '\',\''
//          . sql_safe($_POST['dealer_paid1_state']) . '\',\''
//          . sql_safe($_POST['dealer_paid1_zip']) . '\',\'Y\',\'N\');';
//  $per3 = dbInsert($newPeople);
//
//
//  $reg3 = "INSERT INTO reg (conid, newperid, price, paid, memId, create_trans) VALUES ($conid, $per3, ".$memRow['price'].", ".$memRow['price'].", $memId, $transid);";
//    $response['per3'] = $reg3;
//    dbQuery($reg3);
//}
//
//if(($_POST['dealer_num_paid'] >= 2) and ($_POST['dealer_paid2_lname'] != '')) {
//   $newPeople = "INSERT INTO newperson (last_name, middle_name, first_name, badge_name, address, addr_2, city, state, zip, share_reg_ok, contact_ok) VALUES "
//    . '(\'' . sql_safe($_POST['dealer_paid2_lname']) . '\',\''
//          . sql_safe($_POST['dealer_paid2_mname']) . '\',\''
//          . sql_safe($_POST['dealer_paid2_fname']) . '\',\''
//          . sql_safe($_POST['dealer_paid2_bname']) . '\',\''
//          . sql_safe($_POST['dealer_paid2_address']) . '\',\''
//          . sql_safe($_POST['dealer_paid2_addr2']) . '\',\''
//          . sql_safe($_POST['dealer_paid2_city']) . '\',\''
//          . sql_safe($_POST['dealer_paid2_state']) . '\',\''
//          . sql_safe($_POST['dealer_paid2_zip']) . '\',\'Y\',\'N\');';
//  $per4 = dbInsert($newPeople);
//
//
//  $reg4 = "INSERT INTO reg (conid, newperid, price, paid, memId, create_trans) VALUES ($conid, $per4, ".$memRow['price'].", ".$memRow['price'].", $memId, $transid);";
//    $response['per4'] = $reg4;
//    dbQuery($reg4);
//}
//
//
//  $body .= "Receipt Link to come";
//
//$info = get_conf('vendor');
//
//$email_msg = "no send attempt or a failure";
//  try {
//    $email_msg = $awsClient->sendEmail(array(
//      'Source' => 'regadmin@bsfs.org',
//      'Destination' => array(
//        'ToAddresses' => array($_POST['email'], $info['alley'])
//      ),
//      'Message' => array(
//        'Subject' => array(
//          'Data' => $con['label']. " Online Vendor Purchase"
//        ),
//        'Body' => array(
//          'Text' => array(
//            'Data' => $body
//          ) // HTML
//        )
//      )// ReplyToAddresses or ReturnPath
//    ));
//    $email_error = "none";
//    $success = "success";
//    $data = "success";
//  } catch (AwsException $e) {
//    $email_error = $e->getCode();
//    $success="error";
//    $data=$e->getMessage();
//}
//
//ajaxSuccess(array(
//  "status"=>$success,
//  'response'=>$response,
////  "url"=>$url,
//  "data"=>$data,
//  "trans"=>$transid,
// // "email"=>$email_msg,
//  "email_error"=>$email_error
//));
//?>
