<?php
// library AJAX Processor: exhibitorsSpacePayment.php
// ConTroll Registration System
// Author: Syd Weinstein
// process the payment of space and memberships from the exhibitor tab of controll
//     if payment type != offline credit card - create order and payment information in credit card syste,
//     if payment succeeds, create/update all the elements in the database.
require_once '../lib/base.php';
require_once '../../lib/tax.php';
require_once '../../lib/log.php';
require_once('../../lib/cc__load_methods.php');
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'exhibitor';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid=$con['id'];
$cc = get_conf('cc');

$required = getConfValue('reg', 'required', 'addr');
$response['conid'] = $conid;

$log = get_conf('log');
logInit($log['vendors']);

// which space to generate the order
if (!(array_key_exists('regionYearId', $_POST))) {
    ajaxError("invalid calling sequence");
    exit();
}

if (array_key_exists('portalType', $_POST))
    $portalType = $_POST['portalType'];
else
    $portalType = 'exhibits';

if (array_key_exists('location_controllexhibits', $cc)) {
    $ccLocation = $cc['location_controllexhibits'];
} else if (array_key_exists('location_' . $portalType, $cc)) {
    $ccLocation = $cc['location_' . $portalType];
} else if (array_key_exists('location', $cc)) {
    $ccLocation = $cc['location'];
} else {
    $ccLocation = 'Unknown';
}

$regionYearId = $_POST['regionYearId'];
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

if (array_key_exists('cancelOrderId', $_POST))
    $cancelOrderId = $_POST['cancelOrderId'];
else
    $cancelOrderId = null;

if (array_key_exists('requests', $_POST)) {
    $specialRequests = trim($_POST['requests']);
    if ($specialRequests == '')
        $specialRequests = null;
} else
    $specialRequests = null;

if (array_key_exists('salesTaxId', $_POST)) {
    $salesTaxId = trim($_POST['salesTaxId']);
    if ($salesTaxId == '')
        $salesTaxId = null;
} else
    $salesTaxId = null;

$exhId = $_POST['exhibitorId'];
$eyID = $_POST['exhibitorYearId'];
$source = 'controll-exhibitor';

$curLocale = locale_get_default();
$dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);
// get the specific information allowed
$regionYearQ = <<<EOS
SELECT er.id, name, description, ownerName, ownerEmail, includedMemId, additionalMemId, mi.price AS includedPrice, ma.price AS additionalPrice,
       mi.glNum AS includedGLNum, ma.glNum AS additionalGLNum, ery.mailinFee, ery.atconIdBase, ery.mailinIdBase, ery.mailinGLNum, ery.mailinGLLabel
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON er.id = ery.exhibitsRegion
LEFT OUTER JOIN memList mi ON ery.includedMemId = mi.id
LEFT OUTER JOIN memList ma ON ery.additionalMemId = ma.id
WHERE ery.id = ?;
EOS;
$regionYearR = dbSafeQuery($regionYearQ, 'i', array($regionYearId));
if ($regionYearR == false || $regionYearR->num_rows != 1) {
    $response['error'] = 'Unable to find region record, get help';
    ajaxSuccess($response);
    return;
}
$region = $regionYearR->fetch_assoc();
$regionYearR->free();

//$response['region'] = $region;

// get current exhibitor information
$exhibitorQ = <<<EOS
SELECT exhibitorId, artistName, exhibitorName, exhibitorEmail, exhibitorPhone, website, description, addr, addr2, city, state, zip, country, perid, newperid,
       contactEmail, contactName, ey.mailin
FROM exhibitors e
JOIN exhibitorYears ey ON e.id = ey.exhibitorId
WHERE e.id=? AND ey.conid = ?;
EOS;
$exhibitorR = dbSafeQuery($exhibitorQ, 'ii', array($exhId, $conid));
if ($exhibitorR == false || $exhibitorR->num_rows != 1) {
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
if ($spaceR == false || $spaceR->num_rows == 0) {
    $response['error'] = 'Unable to find any space to invoice';
    ajaxSuccess($response);
    return;
}
$spacePriceComputed = 0;
$includedMembershipsComputed = 0;
$additionalMembershipsComputed = 0;
$spaces = [];
while ($space =  $spaceR->fetch_assoc()) {
    var_error_log($space);
    $spaces[$space['spaceId']] = $space;
    $spacePriceComputed += $space['approved_price'];
    $includedMembershipsComputed = max($includedMembershipsComputed, $space['includedMemberships']);
    $additionalMembershipsComputed = max($additionalMembershipsComputed, $space['additionalMemberships']);
}
$spaceR->free();
$mailIn = [];
// add in mail-in fee if this exhibitor is using mail-in this year and the fee exist
if ($region['mailinFee'] > 0 && $exhibitor['mailin'] == 'Y') {
    $mailIn['amount'] = $region['mailinFee'];
    $mailIn['name'] = $region['name'];
    $mailIn['glNum'] = $region['mailinGLNum'];
    $mailIn['desc'] = $region['name'] . " Mail-in Fee";
    $spacePriceComputed +=  $region['mailinFee'];
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
$buyer['email'] = $exhibitor['exhibitorEmail'];
$buyer['phone'] = $exhibitor['exhibitorPhone'];
$buyer['country'] = $exhibitor['country'];
$buyer['name'] = $exhibitor['exhibitorName'];

$membership_fields = array('fname' => $required != '', 'mname' => false, 'lname' => $required == 'all', 'suffix' => false, 'legalName' => false,
                           'addr' => $required == 'addr' || $required == 'all', 'addr2' => false,
                           'city' => $required == 'addr' || $required == 'all', 'state' => $required == 'addr' || $required == 'all',
                           'zip' => $required == 'addr' || $required == 'all', 'country' => $required == 'addr' || $required == 'all',
                           'email' => true, 'phone' => false, 'badge_name' => false, 'badgeNameL2' =>  false);
$membership_names = array('fname' => 'First Name', 'mname' => 'Middle Name', 'lname' => 'Last Name', 'suffix' => 'Suffix', 'legalName' => 'Legal Name',
                          'addr' => 'Address Line 1', 'addr2' => 'Company/Address Line 2', 'city' => 'City', 'state' => 'State/Province',
                          'zip' => 'Zip Code/Postal Code', 'country' => 'Country',
                          'email' => 'Email Address', 'phone' => 'Phone Number', 'badge_name' => 'Badge Name', 'badgeNameL2' => 'Badge Line 2');

$missing_msg = '';
$valid = true;
$allrequired = true;
$notfound = array();
$email_addresses = [];

// validate the form, returning any errors on missing data
$includedMembershipStatus = array();
$includedMemberships = 0;
for ($num = 0; $num < $includedMembershipsMax; $num++) {
    $fname = '';
    $lname = '';
    if (array_key_exists('fname_i_' . $num, $_POST))
        $fname = $_POST['fname_i_' . $num];

    if (array_key_exists('lname_i_' . $num, $_POST))
        $lname = $_POST['lname_i_' . $num];

    if ($fname == '' && $lname == '')
        continue;

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
        if ($val != '' && ($field == 'fname' || $field == 'lname')) {
            $nonefound = false;
        } else {
            if ($required) {
                $notfound[] = $membership_names[$field];
                $allrequired = false;
            }
        }
        if ($field == 'email') {
            // add to email addresses
            if ($nonefound == false && $val != '' && $val != '/r')
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
    $fname = '';
    $lname = '';
    if (array_key_exists('fname_a_' . $num, $_POST))
        $fname = $_POST['fname_a_' . $num];

    if (array_key_exists('lname_a_' . $num, $_POST))
        $lname = $_POST['lname_a_' . $num];

    if ($fname == '' && $lname == '')
        continue;

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
        if ($val != '' && ($field == 'fnme' || $field == 'lname')) {
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
            $totprice += $region['additionalPrice'];
            $additionalMemberships++;
        } else {
            // some required data is missing
            $missing_msg .= 'Additional Membership ' . $num + 1 . ' is missing ' . implode(',', $notfound) . "<br/>\n";
            $valid = false;
        }
    }
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
}

if (!$valid) {
    $response['error'] = "There were some issues with the data on the form.<br/>Please correct and re-submit.<br/><br/>$missing_msg\n$invalidEmail_msg\n";
    ajaxSuccess($response);
    return;
}
$region['totprice'] = $totprice;
$region['price'] = $spacePrice;
$status_msg = '';
// the form passes validation, lets try running it.
//
// build the badges to insert into newperson and create the transaction
$error_msg = '';
$badges = array();
$transid = null;
$managedByNew = null;
for ($i = 0; $i < count($includedMembershipStatus); $i++) {
    if ($includedMembershipStatus[$i]) {
        $badge = buildBadge($authToken, $membership_fields, 'i', $i, $region, $conid, $transid, $portalName, $managedByNew);
        if ($managedByNew == null)
            $managedByNew = $badge['newperid'];
        $transid = $badge['transid'];
        $status_msg .= $badge['status'];
        $error_msg .= $badge['error'];
        $badges[] = $badge;
    }
}
for ($i = 0; $i < count($additionalMembershipStatus); $i++) {
    if ($additionalMembershipStatus[$i]) {
        $badge = buildBadge($authToken, $membership_fields, 'a', $i, $region, $conid, $transid, $portalName, $managedByNew);
        if ($managedByNew == null)
            $managedByNew = $badge['newperid'];
        $transid = $badge['transid'];
        $badges[] = $badge;
        $status_msg .= $badge['status'];
        $error_msg .= $badge['error'];
    }
}
if ($transid === null) {
    // no tranasction yet, because no badges
    $transQ = <<<EOS
INSERT INTO transaction(price, type, conid, notes, userid)
    VALUES(?, ?, ?, ?, ?);
EOS;

    $notes = "exhibitorId: $exhId, exhibitorYearId: $eyID, exhibitsRegionYearId: $regionYearId, portal: $portalName, exhibitorName: " . $exhibitor['exhibitorName'];
    $transid = dbSafeInsert($transQ, 'dsisi', array($totprice, $portalName, $conid, $notes, $authToken->getPerid()));
    if ($transid === false) {
        $status_msg .= "Add of transaction for $portalName " . $_POST['name'] . " failed.<br/>\n";
    }
}
// built the result structure to log the item and build the order
// first the badges
$all_badgeQ = <<<EOS
SELECT R.id AS badge,
    NP.first_name AS fname, NP.middle_name AS mname, NP.last_name AS lname, NP.suffix AS suffix,
    NP.email_addr AS email,
    NP.address AS street, NP.city AS city, NP.state AS state, NP.zip AS zip, NP.country AS country,
    NP.id as id, R.price AS price, M.memAge AS age, NP.badge_name, NP.badgeNameL2, NP.legalName, R.memId,
    M.label, M.label AS shortname, A.shortname AS ageshortname, M.memCategory, M.memType, M.glNum, R.perid, R.newperid, R.id AS regId
FROM newperson NP
JOIN reg R ON (R.newperid=NP.id)
JOIN memList M ON (M.id = R.memID)
JOIN ageList A ON (A.ageType = M.memAge AND M.conid = A.conid)
WHERE NP.transid=?;
EOS;

$all_badgeR = dbSafeQuery($all_badgeQ, 'i', array($transid));

$badgeResults = array();
while ($row = $all_badgeR->fetch_assoc()) {
    $badgeResults[] = $row;
}
$orderId = null;
$custId = "spacePayment-$transid";
// prepare the credit card request
$results = array(
    'transid' => $transid,
    'counts' => null,
    'spaceName' => $region['name'],
    'spaceDescription' => $region['description'],
    'spacePrice' => $spacePrice,
    'mailInFee' => [$mailIn],
    'price' => $totprice,
    'badges' => $badgeResults,
    'formbadges' => $badges,
    'tax' => 0,
    'pretax' => $totprice,
    'total' => $totprice,
    'vendorId' => $exhId,
    'region' => $region,
    'vendor' => $exhibitor,
    'exhibits' => $portalType,
    'source' => $source,
    'custid' => $custId,
    'spaces' => $spaces,
    'regionYearId' => $regionYearId,
    'eryID' => $eryID,
    'buyer' => $buyer,
);
$response['orderResults'] = $results;

//log requested badges
logWrite(array('con' => $conid, $portalName => $exhibitor, 'region' => $region, 'spaces' => $spaces, 'trans' => $transid, 'results' => $results, 'request' => $badges));

if ($cancelOrderId) // cancel the old order if it exists
    cc_cancelOrder($results['source'], $cancelOrderId, true, $ccLocation);
if ($totprice > 0) {
    load_cc_procs();
// for cash/check/etc build the order so it can be recorded
    $orderRtn = cc_buildOrder($results, true, $ccLocation);
    if ($orderRtn == null) {
// note there is no reason cc_buildOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
        // because this will retry once the issue is corrected, the newperson records and memberships need to be deleted.  it's all in $badgeResults
        cleanupRegs($badgeResults);
        logWrite(array ('con' => $con['label'], 'trans' => $transid, 'error' => 'Order unable to be created'));
        ajaxSuccess(array ('status' => 'error', 'error' => 'Order not built'));
        exit();
    }
    $order = $orderRtn['order'];
    $orderRtn['totalPaid'] = 0;
    $response['orderRtn'] = $orderRtn;
    $orderId = $orderRtn['orderId'];
    $order = $orderRtn['order'];
    $referenceId = $transid . '-' . 'pay-' . time();
    $results = array(
        'source' => $portalType,
        'totalAmt' => $orderRtn['totalAmt'],
        'orderId' => $orderRtn['orderId'],
        'customerId' => $custId,
        'locationId' => $ccLocation,
        'referenceId' => $referenceId,
        'transid' => $transid,
        'preTaxAmt' => $orderRtn['preTaxAmt'],
        'taxAmt' => $orderRtn['taxAmt'],
        'taxes' => $orderRtn['taxes'],
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
    $response['orderRtn'] = $orderRtn;
    $response['orderId'] = $orderRtn['orderId'];
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
function buildBadge($authToken, $fields, $type, $index, $region, $conid, $transid, $portalName, $managedByNew) {
    $badge = array();
    $suffix = '_' . $type . '_' . $index;
    if ($type == 'i') {
        $memid = $region['includedMemId'];
        $memprice = $region['includedPrice'];
        $glNum = $region['includedGLNum'];
    } else {
        $memid = $region['additionalMemId'];
        $memprice = $region['additionalPrice'];
        $glNum = $region['additionalGLNum'];
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
    $badge['glNum'] = $glNum;
    $badge['index'] = $index + 1;

    $legalName = $badge['legalName'];
    if ($legalName == null || $legalName == '') {
        $legalName = trim($badge['fname']  . ($badge['mname'] == '' ? ' ' : ' ' . $badge['mname'] . ' ' ) . $badge['lname'] . ' ' . $badge['suffix']);
    }

    if ($badge['currentAgeType'] == null || $badge['currentAgeType'] == '') {
        $currentAgeType = $badge['currentAgeType'];
        $currentAgeConId = $conid;
    } else {
        $currentAgeType = null;
        $currentAgeConId = null;
    }

    $value_arr = array($badge['lname'], $badge['mname'], $badge['fname'], $badge['suffix'], $legalName, $badge['email'], $badge['phone'],
        $badge['badge_name'], $badge['badgeNameL2'],
        $badge['addr'], $badge['addr2'], $badge['city'], $badge['state'], $badge['zip'], $badge['country'], $badge['contact'], $badge['share'],
        $currentAgeType, $currentAgeConId, $managedByNew);

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
    if ($transid == null) {
        $transQ = <<<EOS
INSERT INTO transaction(newperid,  price, tax, withtax, type, conid, userid)
    VALUES(?, ?, ?, ?, ?, ?, ?);
EOS;

        $transid = dbSafeInsert($transQ, 'idddsii', array($newid, $region['price'], 0, $region['price'], $portalName,
            $conid, $authToken->getPerid()));
        if ($transid === false) {
            $badge['error'] .= 'Add of transaction for ' . $badge['fname'] . ' ' . $badge['lname'] . " failed.\n";
        }
    } else {
        $transQ = <<<EOS
UPDATE transaction
SET price=price + ?, withtax = withtax + ?
WHERE id = ?;
EOS;
        $numrows = dbSafeCmd($transQ, 'ddi', array($region['price'], $region['price'], $transid));
    }
    $badge['transid'] = $transid;
    dbSafeCmd("UPDATE newperson SET transid=? WHERE id = ?;", 'ii', array($badge['transid'], $badge['newperid']));

    $badgeQ = <<<EOS
INSERT INTO reg(conid, newperid, create_trans, price, status, memID)
VALUES(?, ?, ?, ?, ?, ?);
EOS;
    $badgeId = dbSafeInsert($badgeQ,  'iiidsi', array(
            $conid,
            $badge['newperid'],
            $transid,
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
