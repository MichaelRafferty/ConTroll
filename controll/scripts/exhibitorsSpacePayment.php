<?php
global $db_ini;

require_once '../lib/base.php';
require_once '../../lib/log.php';
$check_auth = google_init('ajax');
$perm = 'exhibitor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}


// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$reg_conf = get_conf('reg');
$vendor_conf = get_conf('vendor');

$required = $reg_conf['required'];
$response['conid'] = $conid;

$log = get_conf('log');
logInit($log['vendors']);

// which space purchased
if (!array_key_exists('regionYearId', $_POST)) {
    ajaxError("invalid calling sequence");
    exit();
}

if (array_key_exists('portalType', $_POST))
    $portalType = $_POST['portalType'];
else
    $portalType = 'exhibits';

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

$exhId = $_POST['exhibitorId'];
$eyID = $_POST['exhibitorYearId'];

$curLocale = locale_get_default();
$dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);
// get the specific information allowed
$regionYearQ = <<<EOS
SELECT er.id, name, description, ownerName, ownerEmail, includedMemId, additionalMemId, mi.price AS includedPrice, ma.price AS additionalPrice,
       mi.glNum AS includedGLNum, ma.glNum AS additionalGLNum, ery.mailinFee, ery.atconIdBase, ery.mailinIdBase
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
SELECT exhibitorId, artistName, exhibitorName, exhibitorEmail, website, description, addr, addr2, city, state, zip, perid, newperid,
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
SELECT e.*, esp.price as approved_price, esp.includedMemberships, esp.additionalMemberships, s.name
FROM exhibitorSpaces e
JOIN exhibitsSpaces s ON (s.id = e.spaceId)
JOIN exhibitsSpacePrices esp ON (s.id = esp.spaceId AND e.item_approved = esp.id)
JOIN exhibitsRegionYears ery ON (ery.id = s.exhibitsRegionYear)
JOIN exhibitsRegions er ON (ery.exhibitsRegion = er.id)
WHERE e.exhibitorRegionYear = ?;
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
// add in mail in fee if this exhibitor is using mail in this year and the fee exist
if ($region['mailinFee'] > 0 && $exhibitor['mailin'] == 'Y') {
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

$membership_fields = array('fname' => $required != '', 'mname' => false, 'lname' => $required == 'all', 'suffix' => false, 'legalname' => false,
                           'addr' => $required == 'addr' || $required == 'all', 'addr2' => false,
                           'city' => $required == 'addr' || $required == 'all', 'state' => $required == 'addr' || $required == 'all',
                           'zip' => $required == 'addr' || $required == 'all', 'country' => $required == 'addr' || $required == 'all',
                           'email' => true, 'phone' => false, 'badgename' => false);
$membership_names = array('fname' => 'First Name', 'mname' => 'Middle Name', 'lname' => 'Last Name', 'suffix' => 'Suffix', 'legalname' => 'Legal Name',
                          'addr' => 'Address Line 1', 'addr2' => 'Company/Address Line 2', 'city' => 'City', 'state' => 'State',
                          'zip' => 'Zip Code/Postal Code', 'country' => 'Country',
                          'email' => 'Email Address', 'phone' => 'Phone Number', 'badgename' => 'Badge Name');

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
        if ($val != '') {
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
        $fname = $_POST['fname_i_' . $num];

    if (array_key_exists('lname_a_' . $num, $_POST))
        $lname = $_POST['lname_i_' . $num];

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
for ($i = 0; $i < count($includedMembershipStatus); $i++) {
    if ($includedMembershipStatus[$i]) {
        $badge = buildBadge($membership_fields, 'i', $i, $region, $conid, $transid, $portalName);
        $transid = $badge['transid'];
        $status_msg .= $badge['status'];
        $error_msg .= $badge['error'];
        $badges[] = $badge;
    }
}
for ($i = 0; $i < count($additionalMembershipStatus); $i++) {
    if ($additionalMembershipStatus[$i]) {
        $badge = buildBadge($membership_fields, 'a', $i, $region, $conid, $transid, $portalName);
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
    VALUES(?, ?, ?, ?);
EOS;

    $notes = "exhibitorId: $exhId, exhibitorYearId: $eyID, exhibitsRegionYearId: $regionYearId, portal: $portalName, exhibitorName: " . $exhibitor['exhibitorName'];
    $transid = dbSafeInsert($transQ, 'dsis', array($totprice, $portalName, $conid, $notes));
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
    NP.id as id, R.price AS price, M.memAge AS age, NP.badge_name AS badgename, NP.legalName AS legalname, R.memId
FROM newperson NP
JOIN reg R ON (R.newperid=NP.id)
JOIN memList M ON (M.id = R.memID)
WHERE NP.transid=?;
EOS;

$all_badgeR = dbSafeQuery($all_badgeQ, 'i', array($transid));

$badgeResults = array();
while ($row = $all_badgeR->fetch_assoc()) {
    $badgeResults[] = $row;
}

// prepare the credit card request
$results = array(
    'transid' => $transid,
    'counts' => null,
    'spaceName' => $region['name'],
    'spaceDescription' => $region['description'],
    'spacePrice' => $spacePrice,
    'price' => $totprice,
    'badges' => $badgeResults,
    'formbadges' => $badges,
    'tax' => 0,
    'pretax' => $totprice,
    'total' => $totprice,
    'nonce' => $_POST['nonce'],
    'vendorId' => $exhId,
    'region' => $region,
    'vendor' => $exhibitor,
    'exhibits' => $portalType,
);

//log requested badges
logWrite(array('con' => $conid, $portalName => $exhibitor, 'region' => $region, 'spaces' => $spaces, 'trans' => $transid, 'results' => $results, 'request' => $badges));


$txnQ = <<<EOS
INSERT INTO payments (transid, type, category, description, source, pretax, tax, amount, time, nonce, cc_approval_code, txn_time, userPerid)
VALUES (?,?,?,?,?,?,?,?,NOW(),?,?,NOW(),?);
EOS;
$typestr = 'issssdddssi';
if ($_POST['payment_type'] == 'check') {
    $desc = 'Check No: ' . $_POST['pay-checkno']  . ', ' . $_POST['pay-desc'];
} else {
    $desc = $_POST['pay-desc'];
}
$values = array($transid, $_POST['payment_type'], 'vendor', $desc, 'controll', $totprice, 0, $totprice, 'admin', $_POST['pay-ccauth'],
         $_SESSION['user_perid']);

$txnid = dbSafeInsert($txnQ, $typestr, $values);
if ($txnid == false) {
    $error_msg .= "Insert of payment failed\n";
} else {
    $status_msg .= "Payment for " . $dolfmt->formatCurrency($totprice, 'USD') . " processed<br/>\n";
}
$approved_amt = $totprice;
$results['approved_amt'] = $approved_amt;

// update the other records with the payment information
// Transaction
$txnUpdate = 'UPDATE transaction SET ';
if ($approved_amt == $totprice) {
    $txnUpdate .= 'complete_date=current_timestamp(), ';
}

$txnUpdate .= 'paid=?, withtax=price WHERE id=?;';
$txnU = dbSafeCmd($txnUpdate, 'di', array($approved_amt, $transid));
if ($txnU != 1) {
    $error_msg .= "Unable to mark transaction completed\n";
}
// reg (badge)
$regQ = "UPDATE reg SET paid=price, status='paid', complete_trans=? WHERE create_trans=?;";
$numrows = dbSafeCmd($regQ, 'ii', array($transid, $transid));

// vendor_space
$vendorUQ = <<<EOS
UPDATE exhibitorSpaces
SET item_purchased = ?, price=?, paid=?, transid = ?, membershipCredits = 0, time_purchased = now()
WHERE id = ?
EOS;
foreach ($spaces as $id => $space) {
    $num_rows = dbSafeCmd($vendorUQ, 'iddii', array($space['item_approved'], $space['approved_price'], $space['approved_price'], $transid, $space['id']));
    if ($num_rows == 0) {
        $error_msg .= "Unable to mark " . $space['name']  . " space purchased\n";
    } else {
        $status_msg .= "Space " . $space['name'] . " marked purchased<br/>\n";
    }
}

// assign exhibitor id and the agent if its null
// rule: if exhibitor is mailin, use largest exhibitor number + 1 that is greater than mailin base.
//      if exhibitor is not mailin, use largest exhibitor number = 1 that is greater than atcon base and less that mailin base (if mailin base is != atconbase)
$exNumQ = <<<EOS
SELECT IFNULL(exRY.exhibitorNumber, 0) AS exhibitorNumber, exRY.id, agentPerid, agentNewperson, mailin
FROM exhibitorRegionYears exRY
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
WHERE conid = ? and exhibitorId = ? and exRY.exhibitsRegionYearId = ?
EOS;
$exNumR = dbSafeQuery($exNumQ, 'iii', array($conid, $exhibitor['exhibitorId'], $regionYearId));
if ($exNumR == false || $exNumR->num_rows == 0) {
    $error_msg .= "Unable to retrieve existing exhibitor number<br/>\n";
}
$exNumL = $exNumR->fetch_assoc();
$exRYid = $exNumL['id'];
$exhNum = $exNumL['exhibitorNumber'];
$exPerid = $exNumL['agentPerid'];
$exNewPerson = $exNumL['agentNewperson'];
$exMailin = $exNumL['mailin'];
$exNumR->free();

// first the agent
if ($exMailin == 'N') {
    if (array_key_exists('agent', $_POST))
        $agent = $_POST['agent'];
    else
        $agent = 'first';

    $perid = null;
    $newperid = null;
    $agentRequest = null;
    if ($agent == 'first') {
        if (count($badges) > 0) {
            $perid = $badges[0]['perid'];
            $newperid = $badges[0]['newperid'];
        } else {
            $perid = $exhibitor['perid'];
            $newperid = $exhibitor['newperid'];
        }
    } else if ($agent == 'self') {
        $agentRequest = 'Assign me as my own agent please.';
    } else if ($agent = 'request') {
        $agentRequest = $_POST['agent_request'];
    } else {
        if (str_starts_with($agent, 'p'))
            $perid = substr($agent, 1);
        else
            $newperid = substr($agent, 1);
    }

    if ($perid == null && $newperid == null && $agentRequest == null) {
        $perid = $exhibitor['perid'];
        $newperid = $exhibitor['newperid'];
    }
    $updAgent = <<<EOS
UPDATE exhibitorRegionYears
SET agentPerid = ?, agentNewperson = ?, agentRequest = ?
WHERE id = ?;
EOS;
    $num_rows = dbSafeCmd($updAgent, 'iisi', array($perid, $newperid, $agentRequest, $exRYid));

    // update the master agents if needed
    if ($exhibitor['perid'] == null && $exhibitor['newperid'] == null) {
        $updMaster = <<<EOS
UPDATE exhibitors
SET perid = ?, newperid = ?
WHERE id = ?;
EOS;
        $num_rows = dbSafeCmd($updMaster, 'iii', array($perid, $newperid, $exhibitor['exhibitorId']));
    }
}

if ($exhNum == 0) {
    $nextID = -1;
    if ($exhibitor['mailin'] == 'N') {
        if ($region['atconIdBase'] < $region['mailinIdBase']) {
            $nextIdQ = <<<EOS
SELECT MAX(exhibitorNumber)
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
WHERE exhibitorNumber is NOT NULL AND exhibitorNumber >= ? AND exhibitorNumber < ? AND conid = ? and exRY.exhibitsRegionYearId = ?;
EOS;
            $nextIDR = dbSafeQuery($nextIdQ, 'iiii', array($region['atconIdBase'], $region['mailinIdBase'], $conid, $regionYearId));
            if ($nextIDR == false || $nextIDR->num_rows == 0) {
                $nextID = $region['atconIdBase'] + 1;
            } else {
                $nextL = $nextIDR->fetch_row();
                $nextID = $nextL[0] == NULL ? $region['atconIdBase'] + 1 : $nextL[0] + 1;
            }
        } else if ($region['atconIdBase']) {
            $nextIdQ = <<<EOS
SELECT MAX(exhibitorNumber)
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
WHERE exhibitorNumber is NOT NULL AND exhibitorNumber >= ? AND conid = ? and exRY.exhibitsRegionYearId = ?;
EOS;
            $nextIDR = dbSafeQuery($nextIdQ, 'iii', array($region['atconIdBase'], $conid, $regionYearId));
            if ($nextIDR == false || $nextIDR->num_rows == 0) {
                $nextID = $region['atconIdBase'] + 1;
            } else {
                $nextL = $nextIDR->fetch_row();
                $nextID = $nextL[0] == NULL ? $region['atconIdBase'] + 1 : $nextL[0] + 1;
            }
        }
    }
    if ($nextID < 0) {
        $nextIdQ = <<<EOS
SELECT MAX(exhibitorNumber)
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
WHERE exhibitorNumber is NOT NULL AND exhibitorNumber >= ? AND conid = ? and exRY.exhibitsRegionYearId = ?;
EOS;
        $nextIDR = dbSafeQuery($nextIdQ, 'iii', array($region['mailinIdBase'], $conid, $regionYearId));
        if ($nextIDR == false || $nextIDR->num_rows == 0) {
            $nextID = $region['mailinIdBase'] + 1;
        } else {
            $nextL = $nextIDR->fetch_row();
            $nextID = $nextL[0] == NULL ? $region['mailinIdBase'] + 1 : $nextL[0] + 1;
        }
    }
    $updNum = <<<EOS
UPDATE exhibitorRegionYears
SET exhibitorNumber = ?
WHERE id = ?;
EOS;
    $numrows = dbSafeCmd($updNum, 'ii', array($nextID, $exRYid));
    if ($numrows != 1) {
        $error_msg .= "Unable to assign exhibitor number<br/>\n";
    } else {
        $status_msg .= "You have been assigned Exhibitor Number $nextID for this space.<br/>\n";
    }
}

ajaxSuccess(array(
    'status' => $error_msg != '' ? 'error' : 'success',
    'error' => $error_msg,
    'trans' => $transid,
    'message' => $status_msg,
));
return;

// build the badge structure and insert the person into newperson, trans, reg after checking for exact match
function buildBadge($fields, $type, $index, $region, $conid, $transid, $portalName) {
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
    $legalName = $badge['legalname'];
    if ($legalName == null || $legalName == '') {
        $legalName = trim($badge['fname']  . ($badge['mname'] == '' ? ' ' : ' ' . $badge['mname'] . ' ' ) . $badge['lname'] . ' ' . $badge['suffix']);
    }

    $value_arr = array($badge['lname'], $badge['mname'], $badge['fname'], $badge['suffix'], $legalName, $badge['email'], $badge['phone'], $badge['badgename'],
        $badge['addr'], $badge['addr2'], $badge['city'], $badge['state'], $badge['zip'], $badge['country'], $badge['contact'], $badge['share'], $id);

    $insertQ = <<<EOS
INSERT INTO newperson(last_name, middle_name, first_name, suffix, legalName, email_addr, phone, badge_name,
                      address, addr_2, city, state, zip, country, contact_ok, share_reg_ok, perid)
    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

    $newid = dbSafeInsert($insertQ, 'ssssssssssssssssi', $value_arr);
    $badge['error'] = '';
    if ($newid === false) {
        $badge['error'] .= 'Add of person of badge for ' . $badge['fname'] . ' ' . $badge['lname'] . " failed.\n";
    }

    $badge['newperid'] = $newid;
    // if no tranasction yet, insert one
    if ($transid == null) {
        $transQ = <<<EOS
INSERT INTO transaction(newperid, perid, price, tax, withtax, type, conid)
    VALUES(?, ?, ?, ?, ?, ?, ?);
EOS;

        $transid = dbSafeInsert($transQ, 'iidddsi', array($newid, $id, $region['price'], 0, $region['price'], $portalName, $conid));
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
INSERT INTO reg(conid, newperid, perid, create_trans, price, status, memID)
VALUES(?, ?, ?, ?, ?, ?, ?);
EOS;
    $badgeId = dbSafeInsert($badgeQ,  'iiiidi', array(
            $conid,
            $badge['newperid'],
            $badge['perid'],
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
