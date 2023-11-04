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
$specialRequests = $_POST['requests'];
$taxid = $_POST['taxid'];

$aggreeNone = false;
if (array_key_exists('agreeNone', $_POST))
    $aggreeNone = $_POST['agreeNone'] == 'on';

// get the specific information allowed
// get current vendor information
$vendorQ = <<<EOS
SELECT name, email, website, description, addr, addr2, city, state, zip, publicity, need_new
FROM vendors
WHERE id=?;
EOS;
$vendorR = dbSafeQuery($vendorQ, 'i', array($venId));
$vendor = $vendorR->fetch_assoc();

// now the space  information for this item
$spaceQ = <<<EOS
SELECT v.id AS spaceId, v.shortname, v.name, v.includedMemId, v.additionalMemId, mi.price AS includedMemPrice, ma.price AS additionalMemPrice,
    vp.id as priceid, vp.code, vp.description, vp.units, vp.price, vp.includedMemberships, vp.additionalMemberships
FROM vendorSpacePrices vp
JOIN vendorSpaces v ON (vp.spaceId = v.id)
JOIN memList mi ON (v.includedMemId = mi.id)
JOIN memList ma ON (v.additionalMemId = ma.id)
WHERE vp.id = ?
EOS;
$spaceR = dbSafeQuery($spaceQ, 'i', array($priceId));
$space =  $spaceR->fetch_assoc();

// get the buyer info
$buyer['fname'] = $_POST['cc_fname'];
$buyer['lname'] = $_POST['cc_lname'];
$buyer['addr'] = $_POST['cc_addr'];
$buyer['city'] = $_POST['cc_city'];
$buyer['state'] = $_POST['cc_state'];
$buyer['zip'] = $_POST['cc_zip'];
$buyer['country'] = $_POST['cc_country'];
$buyer['email'] = $_POST['cc_email'];

$membership_fields = array('fname' => 1, 'mname' => 0, 'lname' => 1, 'suffix' => 0, 'addr' => 1, 'addr2' => 0, 'city' => 1, 'state' => 1, 'zip' => 1,
    'country' => 1, 'email' => 1, 'phone' => 0, 'badgename' => 0);
$membership_names = array('fname' => 'First Name', 'mname' => 'Middle Name', 'lname' => 'Last Name', 'suffix' => 'Suffix', 'addr' => 'Address Line 1',
    'addr2' => 'Company/Address Line 2', 'city' => 'City', 'state' => 'State', 'zip' => 'Zip Code/Postal Code', 'country' => 'Country',
     'email' => 'Email Address', 'phone' => 'Phone Number', 'badgename' => 'Badge Name');

$missing_msg = '';
$valid = true;
$allrequired = true;
$notfound = array();
// validate credit card fields
foreach($membership_fields as $field => $required) {
    $postfield = 'cc_' . $field;
    if (array_key_exists($postfield, $_POST)) {
        $val = trim($_POST[$postfield]);
    } else {
        $val = '';
    }
    if ($val == '') {
        if ($required) {
            $notfound[] = $membership_names[$field];
            $allrequired = false;
        }
    }
}
if ($allrequired == false) {
    $missing_msg .= 'Some credit card payment information is missing: ' . implode(',', $notfound) . "\n";
    $valid = false;
}


// validate the form, returning any errors on missing data
$includedMembershipStatus = array();
$includedMemberships = 0;
for ($num = 0; $num < $space['includedMemberships']; $num++) {
    $notfound = array();
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
            if ($required) {
                $notfound[] = $membership_names[$field];
                $allrequired = false;
            }
        }
    }

    // for this included membership, must be either all or none found
    $includedMembershipStatus[$num] = $allrequired && !$nonefound;
    if ($nonefound || $allrequired) { // both of these are valid cases
        if ($allrequired)
            $includedMemberships++;
        continue;
    }
    // some required data is missing
    $missing_msg .= "Included Membership " . $num + 1 . " is missing " . implode(',', $notfound) . "\n";
    $valid = false;
}

$totprice = $space['price'];
$additionalMembershipStatus = array();
$additionalMemberships = 0;
for ($num = 0; $num < $space['additionalMemberships']; $num++) {
    $notfound = array();
    $allrequired = true;
    $nonefound = true;
    foreach ($membership_fields as $field => $required) {
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
            if ($required) {
                $notfound[] = $membership_names[$field];
                $allrequired = false;
            }
        }
    }

    // for this included membership, must be either all or none found
    $additionalMembershipStatus[$num] = $allrequired && !$nonefound;
    if ($nonefound || $allrequired) {  // both of these are valid cases
        if ($allrequired) {
            $totprice += $space['additionalMemPrice'];
            $additionalMemberships++;
        }
        continue;
        // some required data is missing
        $missing_msg .= 'Additional Membership ' . $num + 1 . ' is missing ' . implode(',', $notfound) . "\n";
        $valid = false;
    }
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

if ($additionalMemberships > 0 && $includedMemberships < $space['includedMemberships']) {
    $missing_msg .= "You must use all included memberships before using additional ones\n";
    $valid = false;
}

if (($additionalMemberships + $includedMemberships == 0) && !$aggreeNone) {
    $missing_msg .= "You must buy at least one membership for your space or check the box at the top of the invoice noting that you are not purchasing any memberships at this time and acknowledge the need for memberships for all working in your space.";
    $valid = false;
}


if (!$valid) {
    $response['error'] = "There were some issues with the data on the form.  Please correct and re-submit.\n\n$missing_msg\n$invalidEmail_msg\n";
    ajaxSuccess($response);
    return;
}
$space['totprice'] = $totprice;
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
    $vendorA = array(trim($_POST['name']), trim($_POST['email']), trim($_POST['addr']), trim($_POST['addr2']), trim($_POST['city']), trim($_POST['state']),
        trim($_POST['zip']), $venId);
    $num_rows = dbSafeCmd($updateV, 'sssssssi',$vendorA);
    if ($num_rows == 1)
        $status_msg = "Vendor Profile Updated\n";
    else
        $status_msg = "Nothing to update in Vendor Profile\n";
}

// build the badges to insert into newperson and create the transaction
//
$error_msg = '';
$badges = array();
$transid = null;
for ($i = 0; $i < count($includedMembershipStatus); $i++) {
    if ($includedMembershipStatus[$i]) {
        $badge = build_badge($membership_fields, 'i', $i, $space, $conid, $transid);
        $transid = $badge['transid'];
        $status_msg .= $badge['status'];
        $error_msg .= $badge['error'];
        $badges[] = $badge;
    }
}
for ($i = 0; $i < count($additionalMembershipStatus); $i++) {
    if ($additionalMembershipStatus[$i]) {
        $badge = build_badge($membership_fields, 'a', $i, $space, $conid, $transid);
        $transid = $badge['transid'];
        $badges[] = $badge;
        $status_msg .= $badge['status'];
        $error_msg .= $badge['error'];
    }
}
if ($transid === null) {
    // no tranasction yet, because no badges
    $transQ = <<<EOS
INSERT INTO transaction(price, type, conid, notes)
    VALUES(?, ?, ?);
EOS;

    $transid = dbSafeInsert($transQ, 'dsi', array($space['totprice'], 'vendor', $conid, $space['spaceId']));
    if ($transid === false) {
        $status_msg .= 'Add of transaction for vendor ' . $_POST['name'] . " failed.\n";
    }
}
// now charge the credit card, built the result structure to log the item and build the order
// first the badges
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
while ($row = $all_badgeR->fetch_assoc()) {
    $badgeResults[count($badgeResults)] = $row;
}

// prepare the credit card request
$results = array(
    'transid' => $transid,
    'counts' => null,
    'spacePrice' => $space['price'],
    'spaceName' => $space['name'],
    'spaceDescription' => $space['description'],
    'price' => $totprice,
    'badges' => $badgeResults,
    'formbadges' => $badges,
    'total' => $totprice,
    'nonce' => $_POST['nonce'],
    'vendorId' => $venId,
    'taxid' => $taxid,
    'specialrequests' => $specialRequests,
    'space' => $space,
    'vendor' => $vendor,
    'buyer' => $buyer,
);

//log requested badges
logWrite(array('con' => $conid, 'vendor' => $vendor, 'space' => $space, 'trans' => $transid, 'results' => $results, 'request' => $badges));

$rtn = cc_charge_purchase($results, $ccauth);
if ($rtn === null) {
    ajaxSuccess(array('status' => 'error', 'data' => 'Credit card not approved'));
    exit();
}

//$tnx_record = $rtn['tnx'];
var_error_log($rtn);

// create the payment record
$num_fields = sizeof($rtn['txnfields']);
$val = array();
for ($i = 0; $i < $num_fields; $i++) {
    $val[$i] = '?';
}
$txnQ = 'INSERT INTO payments(time,' . implode(',', $rtn['txnfields']) . ') VALUES(current_time(),' . implode(',', $val) . ');';
$txnT = implode('', $rtn['tnxtypes']);
$txnid = dbSafeInsert($txnQ, $txnT, $rtn['tnxdata']);
if ($txnid == false) {
    $error_msg .= "Insert of payment failed\n";
} else {
    $status_msg .= "Payment for " . $rtn['amount'] . " processed\n";
}
$approved_amt = $rtn['amount'];
$results['approved_amt'] = $approved_amt;

// update the other records with the payment information
// Transaction
$txnUpdate = 'UPDATE transaction SET ';
if ($approved_amt == $totprice) {
    $txnUpdate .= 'complete_date=current_timestamp(), ';
}

$txnUpdate .= 'paid=? WHERE id=?;';
$txnU = dbSafeCmd($txnUpdate, 'di', array($approved_amt, $transid));
// reg (badge)
$regQ = 'UPDATE reg SET paid=price, complete_trans=? WHERE create_trans=?;';
$numrows = dbSafeCmd($regQ, 'ii', array($transid, $transid));
if ($numrows != 1) {
    $error_msg .= "Unable to mark transaction completed\n";
}

// vendor_space
$vendorUQ = <<<EOS
UPDATE vendor_space
SET item_purchased = ?, paid=?, transid = ?, membershipCredits = 0, time_purchased = now()
WHERE conid = ? AND spaceId = ? AND vendorId = ?
EOS;
$num_rows = dbSafeCmd($vendorUQ, 'idiiii', array($priceId, $totprice, $transid, $conid, $space['spaceId'], $venId));
if ($num_rows == 0) {
    $error_msg .= "Unable to mark space purchased\n";
} else {
    $status_msg .= "Space marked purchased\n";
}


$return_arr = send_email($conf['regadminemail'], array($vendor['email'], $buyer['email']), $vendor_conf[$space['shortname']], $space['name'] . ' Payment', payment($results), /* htmlbody */ null);

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

ajaxSuccess(array(
    'status' => $return_arr['status'],
    'url' => $rtn['url'],
    'error' => $error_msg,
    'email' => $return_arr,
    'trans' => $transid,
    //"email"=>$email_msg,
    'email_error' => $error_code,
    'message' => $status_msg
));
return;

// build the badge structure and insert the person into newperson, trans, reg after checking for exact match
function build_badge($fields, $type, $index, $space, $conid, $transid) {
    $badge = array();
    $suffix = '_' . $type . '_' . $index;
    if ($type == 'i') {
        $memid = $space['includedMemId'];
        $memprice = $space['includedMemPrice'];
    } else {
        $memid = $space['additionalMemId'];
        $memprice = $space['additionalMemPrice'];
    }

    foreach ($fields as $field => $required) {
        $badge[$field] = trim($_POST[$field . $suffix]);
    }
    $badge['age'] = 'all';
    $badge['price'] = $memprice;
    $badge['memId'] = $memid;
    $badge['contact'] = 'Y';
    $badge['share'] = 'Y';
    $badge['type'] = $type;
    $badge['index'] = $index + 1;

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
    $value_arr = array($badge['fname'], $badge['mname'], $badge['lname'], $badge['suffix'], $badge['email'], $badge['phone'], $badge['badgename'],
                $badge['addr'], $badge['addr2'], $badge['city'], $badge['state'], $badge['zip'], $badge['country']);
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
    $badge['perid'] = $id;

    $value_arr = array($badge['lname'], $badge['mname'], $badge['fname'], $badge['suffix'], $badge['email'], $badge['phone'], $badge['badgename'],
        $badge['addr'], $badge['addr2'], $badge['city'], $badge['state'], $badge['zip'], $badge['country'], $badge['contact'], $badge['share'], $id);

    $insertQ = <<<EOS
INSERT INTO newperson(last_name, middle_name, first_name, suffix, email_addr, phone, badge_name,
                      address, addr_2, city, state, zip, country, contact_ok, share_reg_ok, perid)
    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

    $newid = dbSafeInsert($insertQ, 'sssssssssssssssi', $value_arr);
    $badge['error'] = '';
    if ($newid === false) {
        $badge['error'] .= 'Add of person of badge for ' . $badge['fname'] . ' ' . $badge['lname'] . " failed.\n";
    }

    $badge['newid'] = $newid;
    // if no tranasction yet, insert one
    if ($transid == null) {
        $transQ = <<<EOS
INSERT INTO transaction(newperid, perid, price, type, conid)
    VALUES(?, ?, ?, ?, ?);
EOS;

        $transid = dbSafeInsert($transQ, 'iidsi', array($newid, $id, $space['totprice'], 'vendor', $conid));
        if ($transid === false) {
            $badge['error'] .= 'Add of transaction for ' . $badge['fname'] . ' ' . $badge['lname'] . " failed.\n";
        }
    }
    $badge['transid'] = $transid;
    dbSafeCmd("UPDATE newperson SET transid=? WHERE id = ?;", 'ii', array($badge['transid'], $badge['newid']));

    $badgeQ = <<<EOS
INSERT INTO reg(conid, newperid, perid, create_trans, price, memID)
VALUES(?, ?, ?, ?, ?, ?);
EOS;
    $badgeId = dbSafeInsert($badgeQ,  'iiiidi', array(
            $conid,
            $badge['newid'],
            $badge['perid'],
            $transid,
            $badge['price'],
            $badge['memId'])
        );

    if ($badgeId === false) {
        $badge['error'] .= 'Add of registration for ' . $badge['fname'] . ' ' . $badge['lname'] . " failed.\n";
    }
    $badge['badgeId'] = $badgeId;
    if ($badge['error'] == '') {
        $badge['status'] = 'Badge Created: ' . $badge['fname'] . ' ' . $badge['lname'] . "\n";
    }

    return $badge;
}
