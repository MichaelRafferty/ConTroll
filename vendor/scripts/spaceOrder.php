<?php
// spaceOrder.php - build the credit card order for a space purchase to get the tax amounts
require_once('../lib/base.php');
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/tax.php');
require_once('../../lib/log.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

global $condata;
$condata = get_con();
$conid=$condata['id'];
$required = getConfValue('reg', 'required', 'addr');
$cc = get_conf('cc');

$response['conid'] = $conid;
$currency = getConfValue('con', 'currency', 'USD');

$ccauth = get_conf('cc');
load_cc_procs();

$log = get_conf('log');
logInit($log['vendors']);

if(!isSessionVar('id')) { ajaxSuccess(array('status'=>'error', 'message'=>'Session Failure')); exit; }

$exhId = getSessionVar('id');
$eyID = getSessionVar('eyID');

$response = array("post" => $_POST, "get" => $_GET);

// which space purchased
if (!array_key_exists('regionYearId', $_POST)) {
    ajaxError("invalid calling sequence");
    exit();
}

if (array_key_exists('portalType', $_POST))
    $portalType = $_POST['portalType'];
else
    $portalType = 'exhibits';

if (array_key_exists('location_' . $portalType, $cc)) {
    $ccLocation = $cc['location_' . $portalType];
} else if (array_key_exists('location', $cc)) {
    $ccLocation = $cc['location'];
} else {
    $ccLocation = 'Unknown';
}

$regionYearId = $_POST['regionYearId'];
if (array_key_exists('requests', $_POST)) {
    $specialRequests = trim($_POST['requests']);
    if ($specialRequests == '')
        $specialRequests = null;
}
else
    $specialRequests = null;

if (array_key_exists('salesTaxId', $_POST)) {
    $salesTaxId = trim($_POST['salesTaxId']);
    if ($salesTaxId == '')
        $salesTaxId = null;
} else
    $salesTaxId = null;

$portalName = $_POST['portalName'];
if (array_key_exists('includedMemberships', $_POST))
    $includedMembershipsMax = $_POST['includedMemberships'];
else
    $includedMembershipsMax = 0;
if (array_key_exists('additionalMemberships', $_POST))
    $additionalMembershipsMax = $_POST['additionalMemberships'];
else
    $additionalMembershipsMax = 0;
if (array_key_exists('spacePrice', $_POST))
    $spacePrice = $_POST['spacePrice'];
else
    $spacePrice = 0;

$aggreeNone = false;
if (array_key_exists('agreeNone', $_POST))
    $aggreeNone = $_POST['agreeNone'] == 'on';

$curLocale = locale_get_default();
$dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);
// get the specific information allowed
$regionYearQ = <<<EOS
SELECT er.id, name, description, ownerName, ownerEmail, includedMemId, additionalMemId, mi.price AS includedPrice, ma.price AS additionalPrice,
       mi.glNum AS includedGLNum, ma.glNum AS additionalGLNum, mi.label AS includedLabel, ma.label AS additionalLabel,
       ery.mailinFee, ery.atconIdBase, ery.mailinIdBase, ery.id as yearId, ery.mailinGLNum, ery.mailinGLLabel
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON er.id = ery.exhibitsRegion
LEFT OUTER JOIN memList mi ON ery.includedMemId = mi.id
LEFT OUTER JOIN memList ma ON ery.additionalMemId = ma.id
WHERE ery.id = ?;
EOS;
$regionYearR = dbSafeQuery($regionYearQ, 'i', array($regionYearId));
if ($regionYearR === false || $regionYearR->num_rows != 1) {
    $response['error'] = 'Unable to find region record, get help';
    ajaxSuccess($response);
    return;
}
$region = $regionYearR->fetch_assoc();
$regionYearR->free();

//$response['region'] = $region;

// get current exhibitor information
$exhibitorQ = <<<EOS
SELECT exhibitorId, artistName, exhibitorName, exhibitorEmail, website, description, addr, addr2, city, state, zip, perid, newperid, salesTaxId,
       contactEmail, contactName, ey.mailin, exhibitorPhone
FROM exhibitors e
JOIN exhibitorYears ey ON e.id = ey.exhibitorId
WHERE e.id=? AND ey.conid = ?;
EOS;
$exhibitorR = dbSafeQuery($exhibitorQ, 'ii', array($exhId, $conid));
if ($exhibitorR === false || $exhibitorR->num_rows != 1) {
    $response['error'] = 'Unable to find your exhibitor record';
    ajaxSuccess($response);
    return;
}
$exhibitor = $exhibitorR->fetch_assoc();
$exhibitorR->free();

//$response['exhibitor'] = $exhibitor;

$exhibitorRegionYearQ = <<<EOS
SELECT * FROM exhibitorRegionYears WHERE exhibitorYearId = ? AND exhibitsRegionYearId = ?;
EOS;
$exhibitorRegionYearR = dbSafeQuery($exhibitorRegionYearQ, 'ii', array($eyID, $regionYearId));
$exhibitorRegionYear = $exhibitorRegionYearR->fetch_assoc();
$exhibitorRegionYearR->free();
$eryID = $exhibitorRegionYear['id'];
$response['exhibitorRegionYear'] = $eryID;

// now the space information for this regionYearId
$spaceQ = <<<EOS
SELECT e.*, esp.price as approved_price, esp.includedMemberships, esp.additionalMemberships, s.name, esp.description, ry.exhibitorNumber,
       y.exhibitorId, ex.exhibitorName, ex.artistName, er.name AS regionName, esp.glNum, esp.glLabel, ery.includedMemId, ery.additionalMemId
FROM exhibitorRegionYears ry
JOIN exhibitorSpaces e ON (e.exhibitorRegionYear = ry.id)
JOIN exhibitorYears y ON (y.id = ry.exhibitorYearId)
JOIN exhibitors ex ON (ex.id = y.exhibitorId)
JOIN exhibitsSpaces s ON (s.id = e.spaceId)
JOIN exhibitsSpacePrices esp ON (s.id = esp.spaceId AND e.item_approved = esp.id)
JOIN exhibitsRegionYears ery ON (ery.id = s.exhibitsRegionYear)
JOIN exhibitsRegions er ON (ery.exhibitsRegion = er.id)
WHERE e.exhibitorRegionYear = ?
ORDER BY id ASC;
EOS;
$spaceR = dbSafeQuery($spaceQ, 'i', array($eryID));
if ($spaceR === false || $spaceR->num_rows == 0) {
    $response['error'] = 'Unable to find any space to invoice';
    ajaxSuccess($response);
    return;
}
$spacePriceComputed = 0;
$includedMembershipsComputed = 0;
$additionalMembershipsComputed = 0;
$spaces = [];
$mailIn = [];
while ($space =  $spaceR->fetch_assoc()) {
    labeled_error_log("spaceOrder-space", $space);
    $spaces[$space['spaceId']] = $space;
    $spacePriceComputed += $space['approved_price'];
    $includedMembershipsComputed = max($includedMembershipsComputed, $space['includedMemberships']);
    $additionalMembershipsComputed = max($additionalMembershipsComputed, $space['additionalMemberships']);
}
$spaceR->free();
// add in mail-in fee if this exhibitor is using mail-in this year and the fee exist
if ($region['mailinFee'] > 0 && $exhibitor['mailin'] == 'Y') {
    $mailIn['amount'] = $region['mailinFee'];
    $mailIn['name'] = $region['name'];
    $mailIn['glNum'] = $region['mailinGLNum'];
    $mailIn['desc'] = $region['name'] . ' Mail-in Fee';
    $spacePriceComputed += $region['mailinFee'];
}

if ($spacePrice != $spacePriceComputed || $includedMembershipsComputed != $includedMembershipsMax || $additionalMembershipsComputed != $additionalMembershipsMax) {
    error_log("Price: $spacePrice != $spacePriceComputed");
    error_log("Price: $includedMembershipsComputed != $includedMembershipsMax");
    error_log("Price: $additionalMembershipsComputed != $additionalMembershipsMax");
    $response['error'] = 'Computed values does not match passed values, get help.';
    ajaxSuccess($response);
    return;
}

$region['includedMemberships'] = $includedMembershipsComputed;
$region['additionalMemberships'] = $additionalMembershipsComputed;

$membership_fields = array('fname' => 1, 'mname' => 0, 'lname' => 1, 'suffix' => 0, 'legalName' => 0,
    'addr' => 1, 'addr2' => 0, 'city' => 1, 'state' => 1, 'zip' => 1, 'country' => 1,
    'email1' => 1, 'phone' => 0, 'badge_name' => 0, 'badgeNameL2' =>  0, 'age' => 1);
$membership_names = array('fname' => 'First Name', 'mname' => 'Middle Name', 'lname' => 'Last Name', 'legalName' => 'Legal Name', 'suffix' => 'Suffix',
    'addr' => 'Address Line 1', 'addr2' => 'Company/Address Line 2', 'city' => 'City', 'state' => 'State/Province', 'zip' => 'Zip Code/Postal Code',
    'country' => 'Country', 'email1' => 'Email Address', 'phone' => 'Phone Number', 'badge_name' => 'Badge Name', 'badgeNameL2' => 'Badge Line 2',
    'age' => 'Age');

if ($required == 'addr') {
    $membership_fields['lname'] = 0;
}
if ($required == 'first') {
    $membership_fields['lname'] = 0;
    $membership_fields['addr'] = 0;
    $membership_fields['city'] = 0;
    $membership_fields['state'] = 0;
    $membership_fields['zip'] = 0;
}

$missing_msg = '';
$valid = true;
$allrequired = true;
$notfound = array();
$email_addresses = array();
// validate the form, returning any errors on missing data
$includedMembershipStatus = array();
$includedMemberships = 0;
for ($num = 0; $num < $includedMembershipsMax; $num++) {
    $notfound = array();
    $allrequired = true;
    $nonefound = true;
    foreach($membership_fields as $field => $required) {
        if ($field == 'country')
            continue; // it's a pulldown, so it's always found and messes up required checks.

        $postfield = 'i_' . $num . '_' . $field;
        if (array_key_exists($postfield, $_POST)) {
            $val = trim($_POST[$postfield]);
        } else {
            $val = '';
        }
        if ($val != '' && ($field == 'fname' || $field == 'lname')) {
            $nonefound = false;
        } else {
            if ($required && $val == '') {
                $notfound[] = $membership_names[$field];
                $allrequired = false;
            }
        }
        if ($field == 'email1') {
            // add to email addresses
            if ($nonefound == false && $val != '')
                $email_addresses[$postfield] = "Included Membership $num Email";
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
    $missing_msg .= "Included Membership " . $num + 1 . " is missing " . implode(',', $notfound) . "<br/>\n";
    $valid = false;
}

$totprice = $spacePrice;
$additionalMembershipStatus = array();
$additionalMemberships = 0;
for ($num = 0; $num < $additionalMembershipsMax; $num++) {
    $notfound = array();
    $allrequired = true;
    $nonefound = true;
    foreach ($membership_fields as $field => $required) {
        if ($field == 'country')
            continue; // it's a pulldown, so it's always found and messes up required checks.

        $postfield = 'a_' . $num . '_' . $field;
        if (array_key_exists($postfield, $_POST)) {
            $val = trim($_POST[$postfield]);
        } else {
            $val = '';
        }
        if ($val != '' && ($field == 'fname' || $field == 'lname')) {
            $nonefound = false;
        } else {
            if ($required && $val == '') {
                $notfound[] = $membership_names[$field];
                $allrequired = false;
            }
        }
    }

    // for this included membership, must be either all or none found
    $additionalMembershipStatus[$num] = $allrequired && !$nonefound;
    if ($nonefound || $allrequired) {  // both of these are valid cases
        if ($allrequired) {
            $totprice += $region['additionalPrice'];
            $additionalMemberships++;
        }
        continue;
    }
    // some required data is missing
    $missing_msg .= 'Additional Membership ' . $num + 1 . ' is missing ' . implode(',', $notfound) . "<br/>\n";
    $valid = false;
}

// check email addresses
$invalidEmail_msg = '';
foreach ($email_addresses AS $email => $where) {
    if (array_key_exists($email, $_POST)) {
        $val = trim($_POST[$email]);
        if ($val != '') {
            if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                $invalidEmail_msg .= $where . " is not in the format of a valid email address<br/>\n";
                $valid = false;
            }
        }
    }
}

if ($additionalMembershipsMax > 0 || $includedMembershipsMax > 0) {
    if ($additionalMemberships > 0 && $includedMemberships < $includedMembershipsMax) {
        $missing_msg .= "You must use all included memberships before using additional ones\n";
        $valid = false;
    }

    if (($additionalMemberships + $includedMemberships == 0) && !$aggreeNone) {
        $missing_msg .= "You must buy at least one membership for your space or check the box at the top of the invoice noting that you are not purchasing any memberships at this time and acknowledge the need for memberships for all working in your space.";
        $valid = false;
    }
}

if (!$valid) {
    $response['error'] = "There were some issues with the data on the form.<br/>Please correct and re-submit.<br/><br/>$missing_msg\n$invalidEmail_msg\n";
    ajaxSuccess($response);
    return;
}


// ok, it's valid, process the updates to the database and the payments
$region['totprice'] = $totprice;
$region['price'] = $spacePrice;
$status_msg = '';
// the form passes validation, lets try running it.
// first does the exhibitor profile need updating
if ($_POST['name'] != $exhibitor['exhibitorName'] || $_POST['email'] != $exhibitor['exhibitorEmail'] || $_POST['addr'] != $exhibitor['addr']
    || $_POST['addr2'] != $exhibitor['addr2'] || $_POST['city'] != $exhibitor['city'] ||  $_POST['state'] != $exhibitor['state']
    || $_POST['zip'] != $exhibitor['zip'] || $salesTaxId != $exhibitor['salesTaxId']) {
    // something doesn't match update these fields
    $updateV = <<<EOS
UPDATE exhibitors
SET exhibitorName=?, exhibitorEmail=?, addr=?, addr2=?, city=?, state=?, zip=?, salesTaxId = ?
WHERE id=?;
EOS;
    $exhibitorA = array(trim(ifnull($_POST['name'],'')), trim(ifnull($_POST['email'],'')), trim(ifnull($_POST['addr'],'')),
        trim(ifnull($_POST['addr2'],'')), trim(ifnull($_POST['city'],'')), trim(ifnull($_POST['state'],'')),
        trim(ifnull($_POST['zip'],'')), trim(ifnull($salesTaxId,'')), $exhId);
    $num_rows = dbSafeCmd($updateV, 'ssssssssi',$exhibitorA);
    if ($num_rows == 1)
        $status_msg = "$portalName Profile Updated<br/>\n";
    else
        $status_msg = "Nothing to update in $portalName Profile<br/>\n";
}

// build the badges to insert into newperson and create the transaction
// track the badges built to remove them if the payment fails
//
$error_msg = '';
$badges = array();
$transId = null;
$managedByNew = null;
for ($i = 0; $i < count($includedMembershipStatus); $i++) {
    if ($includedMembershipStatus[$i]) {
        $badge = buildBadge($membership_fields, 'i', $i, $region, $conid, $transId, $portalName, $managedByNew);
        if ($managedByNew == null)
            $managedByNew = $badge['newperid'];
        $transId = $badge['transid'];
        $status_msg .= $badge['status'];
        $error_msg .= $badge['error'];
        $badges[] = $badge;
    }
}
for ($i = 0; $i < count($additionalMembershipStatus); $i++) {
    if ($additionalMembershipStatus[$i]) {
        $badge = buildBadge($membership_fields, 'a', $i, $region, $conid, $transId, $portalName, $managedByNew);
        if ($managedByNew == null)
            $managedByNew = $badge['newperid'];
            $transId = $badge['transid'];
        $badges[] = $badge;
        $status_msg .= $badge['status'];
        $error_msg .= $badge['error'];
    }
}
if ($error_msg != '') {
    $error_msg = "There were some issues with your request.<br/>Please seek assistance.<br/><br/>$error_msg\n";
    ajaxSuccess(array ('status' => 'error', 'error' => $error_msg));
}

// ok, all the data built correctly, now build the order
if ($transId === null) {
    // no tranasction yet, because no badges
    $transQ = <<<EOS
INSERT INTO transaction(price, type, conid, notes)
    VALUES(?, ?, ?, ?);
EOS;

    $notes = "exhibitorId: $exhId, exhibitorYearId: $eyID, exhibitsRegionYearId: $regionYearId, portal: $portalName, exhibitorName: " . $exhibitor['exhibitorName'];
    $transId = dbSafeInsert($transQ, 'dsis', array($totprice, $portalName, $conid, $notes));
    if ($transId === false) {
        $status_msg .= "Add of transaction for $portalName " . $_POST['name'] . " failed.<br/>\n";
    }
}
// now build the order to get the possible taxes, build the result structure to log the item and build the order
// first the badges
$all_badgeQ = <<<EOS
SELECT R.id AS badge,
    NP.first_name AS fname, NP.middle_name AS mname, NP.last_name AS lname, NP.suffix AS suffix,
    NP.email_addr AS email,
    NP.address AS street, NP.city AS city, NP.state AS state, NP.zip AS zip, NP.country AS country,
    NP.id as id, R.price AS price, M.memAge AS age, NP.badge_name, NP.badgeNameL2, NP.legalName, R.memId, M.memAge,
    M.shortname, M.ageShortName AS ageshortname, M.taxable, M.memCategory, M.memType, M.glNum, R.perid, R.newperid, R.id AS regId
FROM newperson NP
JOIN reg R ON (R.newperid=NP.id)
JOIN memLabel M ON (M.id = R.memID)
WHERE NP.transid=?;
EOS;

$all_badgeR = dbSafeQuery($all_badgeQ, 'i', array($transId));

$badgeResults = array();
while ($row = $all_badgeR->fetch_assoc()) {
    $badgeResults[] = $row;
}
$custId = "spacePayment-$transId";
// prepare the credit card order request
$results = array(
    'custid' => $custId,
    'source' => $portalType,
    'transid' => $transId,
    'counts' => null,
    'spaces' => $spaces,
    'mailInFee' => [$mailIn],
    'price' => $totprice,
    'badges' => $badgeResults,
    'formbadges' => $badges,
    'pretax' => $totprice,
    'total' => $totprice,
    'vendorId' => $exhId,
    'salesTaxId' => $salesTaxId,
    'specialrequests' => $specialRequests,
    'region' => $region,
    'vendor' => $exhibitor,
    'exhibits' => $portalType,
    'regionYearId' => $regionYearId,
    'eryID' => $eryID,
);

$response['orderResults'] = $results;

//log requested badges
labeled_logWrite('spaceOrder-build order request',
    array('con' => $conid, $portalName => $exhibitor, 'region' => $region, 'spaces' => $spaces, 'trans' => $transId, 'results' => $results,
        'request' => $badges));

// end compute, create the order if there is something to pay
if ($totprice > 0) {
    $rtn = cc_buildOrder($results, true, $ccLocation);
    if ($rtn == null) {
        // note there is no reason cc_buildOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
        labeled_logWrite('spaceOrder/cc_buildOrder returned null',
            array ('con' => $condata['name'], 'trans' => $transId, 'error' => 'Order unable to be created'));

        // because this will retry once the issue is corrected, the newperson records and memberships need to be deleted.  it's all in $badgeResults
        cleanupRegs($badgeResults);
        ajaxSuccess(array ('status' => 'error', 'error' => 'Order not built, seek assistance'));
        exit();
    }
    $order = $rtn['order'];
    labeled_logWrite('spaceOrder-order',
        array('status'=> 'order create', 'con' => $condata['name'], 'trans' => $transId, 'ccrtn' => $rtn));
    $referenceId = $transId . '-' . 'pay-' . time();
    $results = array(
        'source' => $portalType,
        'totalAmt' => $rtn['totalAmt'],
        'orderId' => $rtn['orderId'],
        'customerId' => $custId,
        'locationId' => $ccLocation,
        'referenceId' => $referenceId,
        'transid' => $transId,
        'preTaxAmt' => $rtn['preTaxAmt'],
        'taxAmt' => $rtn['taxAmt'],
        'taxes' => $rtn['taxes'],
        'vendorId' => $exhId,
        'salesTaxId' => $salesTaxId,
        'specialrequests' => $specialRequests,
        'region' => $region,
        'vendor' => $exhibitor,
        'exhibits' => $portalType,
        'counts' => null,
        'spaces' => $spaces,
        'mailInFee' => [$mailIn],
        'price' => $totprice,
        'badges' => $badgeResults,
        'formbadges' => $badges,
        'total' => $totprice,
    );


    $response['results'] = $results;
    $response['rtn'] = $rtn;
    $response['orderId'] = $rtn['orderId'];
    $response['status'] = 'success';
    $response['message'] = 'Order created, now please enter your payment information';
} else {
    $response['results'] = $results;
    $response['rtn'] = [];
    $response['order'] = 'none';
    $response['status'] = 'success';
    $response['message'] = 'No payment required';
}
ajaxSuccess($response);
return;

// build the badge structure and insert the person into newperson, trans, reg after checking for exact match
function buildBadge($fields, $type, $index, $region, $conid, $transId, $portalName, $managedByNew) {
    $badge = array();
    $prefix = $type . '_' . $index . '_';
    if ($type == 'i') {
        $memid = $region['includedMemId'];
        $memprice = $region['includedPrice'];
        $glNum = $region['includedGLNum'];
        $label = $region['includedLabel'];
    } else {
        $memid = $region['additionalMemId'];
        $memprice = $region['additionalPrice'];
        $glNum = $region['additionalGLNum'];
        $label = $region['additionalLabel'];
    }

    foreach ($fields as $field => $required) {
        $badge[$field] = trim($_POST[$prefix . $field]);
    }
    $badge['price'] = $memprice;
    $badge['memId'] = $memid;
    $badge['label'] = $label;
    $badge['contact'] = 'Y';
    $badge['share'] = 'Y';
    $badge['type'] = $type;
    $badge['glNum'] = $glNum;
    $badge['index'] = $index + 1;

    $legalName = $badge['legalName'];
    if ($legalName == null || $legalName == '') {
        $legalName = trim($badge['fname']  . ($badge['mname'] == '' ? ' ' : ' ' . $badge['mname'] . ' ' ) . $badge['lname'] . ' ' . $badge['suffix']);
    }

    $value_arr = array($badge['lname'], $badge['mname'], $badge['fname'], $badge['suffix'], $legalName, $badge['email1'], $badge['phone'],
        $badge['badge_name'], $badge['badgeNameL2'],
        $badge['addr'], $badge['addr2'], $badge['city'], $badge['state'], $badge['zip'], $badge['country'],
        $badge['contact'], $badge['share'], $badge['age'], $conid, $managedByNew);

    $insertQ = <<<EOS
INSERT INTO newperson(last_name, middle_name, first_name, suffix, legalName, email_addr, phone, badge_name, badgeNameL2,
                      address, addr_2, city, state, zip, country, contact_ok, share_reg_ok, currentAgeType, currentAgeConId, managedByNew)
    VALUES(IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''),
           IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), IFNULL(?, ''), ?, ?, ?, ?, ?);
EOS;

    $newid = dbSafeInsert($insertQ, 'ssssssssssssssssssii', $value_arr);
    $badge['error'] = '';
    if ($newid === false) {
        $badge['error'] .= 'Add of person of badge for ' . $badge['fname'] . ' ' . $badge['lname'] . " failed.\n";
    }

    $badge['newperid'] = $newid;
    // if no tranasction yet, insert one
    if ($transId == null) {
        $transQ = <<<EOS
INSERT INTO transaction(newperid, price, type, conid)
    VALUES(?, ?, ?, ?);
EOS;

        $transId = dbSafeInsert($transQ, 'idsi', array($newid, $region['price'], $portalName, $conid));
        if ($transId === false) {
            $badge['error'] .= 'Add of transaction for ' . $badge['fname'] . ' ' . $badge['lname'] . " failed.\n";
        }
    }
    $badge['transid'] = $transId;
    dbSafeCmd("UPDATE newperson SET transid=? WHERE id = ?;", 'ii', array($badge['transid'], $badge['newperid']));

    $badgeQ = <<<EOS
INSERT INTO reg(conid, newperid, create_trans, price, status, memID)
VALUES(?, ?, ?, ?, ?, ?);
EOS;
    $badgeId = dbSafeInsert($badgeQ,  'iiidsi', array(
            $conid,
            $badge['newperid'],
            $transId,
            $badge['price'],
            $badge['price'] > 0 ? 'unpaid' : 'paid',
            $badge['memId'])
        );

    if ($badgeId === false) {
        $badge['error'] .= 'Add of registration for ' . $badge['fname'] . ' ' . $badge['lname'] . " failed.\n";
    }
    $badge['badgeId'] = $badgeId;
    if ($badge['error'] == '') {
        $badge['status'] = 'Badge Created: ' . $badge['fname'] . ' ' . $badge['lname'] . "<br/>\n";
    }

    return $badge;
}

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

    // now the newperid
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
