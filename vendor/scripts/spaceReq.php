<?php
require_once('../lib/base.php');
require_once('../../lib/email__load_methods.php');
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
$curLocale = locale_get_default();
$dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

$response['conid'] = $conid;

// validate that the items passed are the VendorSpace id and the VendorSpacePrice id
if (!array_key_exists('regionYearId', $_POST)) {
    error_log("no regionYear");
    ajaxError('Invalid arguments');
    return;
}
if (!array_key_exists('requests', $_POST)) {
    error_log("no requests");
    ajaxError('Invalid arguments');
    return;
}

$regionYearId = $_POST['regionYearId'];
parse_str($_POST['requests'], $requests);
$portalType = $_POST['type'];
$portalName = $_POST['name'];

// get exhibitor info
$exhibitorQ = <<<EOS
SELECT e.id AS exhibitorId, ey.id AS exhibitorYearId, e.exhibitorName, e.exhibitorEmail, ey.contactName, ey.contactEmail, e.exhibitorName, e.website, e.description, exRY.id AS exhibitorRegionYearId
FROM exhibitors e
JOIN exhibitorYears ey ON e.id = ey.exhibitorId
JOIN exhibitsRegionYears eRY
JOIN exhibitorRegionYears exRY ON ey.id = exRY.exhibitorYearId AND exRY.exhibitsRegionYearId = eRY.id
WHERE ey.conid = ? AND e.id = ? AND eRY.id = ?
EOS;
$exhibitorR = dbSafeQuery($exhibitorQ, 'iii', array($conid, $vendor, $regionYearId));
if ($exhibitorR == false || $exhibitorR->num_rows != 1) {
    ajaxError('Invalid Session');
    return;
}
$exhibitorInfo = $exhibitorR->fetch_assoc();
$eyId = $exhibitorInfo['exhibitorYearId'];
$exRYId = $exhibitorInfo['exhibitorRegionYearId'];
$exhibitorR->free();

// get region info
$regionQ = <<<EOS
SELECT ery.*, er.shortname, er.name
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
WHERE ery.id = ?;
EOS;
$regionR = dbSafeQuery($regionQ, 'i', array($regionYearId));
if ($regionR == false || $regionR->num_rows != 1) {
    error_log("regionQ: $regionQ with $regionYearId failed");
    ajaxError('Invalid arguments');
    return;
}
$regionInfo = $regionR->fetch_assoc();
$regionR->free();
$response['div'] = $regionInfo['shortname'] . '_div';

$spaces = '';

foreach ($requests as $key => $priceId) {
     $spaceid = str_replace('exhbibitor_req_price_id_', '', $key); //TODO fix spelling here and wherever else it's wrong...

    if ($priceId > 0) {
        // get the details of the item requested
        $priceQ = <<<EOS
SELECT esp.code, esp.description, esp.units, esp.price, esp.requestable, es.shortname, es.name
FROM exhibitsSpacePrices esp
JOIN exhibitsSpaces es ON esp.spaceId = es.id
WHERE esp.spaceId = ? and esp.id = ?;
EOS;
        $priceR = dbSafeQuery($priceQ, 'ii', array($spaceid, $priceId));
    } else {
        $priceQ = <<<EOS
SELECT shortname, name, description
FROM exhibitsSpaces
WHERE id = ?;
EOS;
        $priceR = dbSafeQuery($priceQ, 'i', array($spaceid));
    }

    $price = $priceR->fetch_assoc();
    if ($price === null || $price === false)  {
        error_log("PriceQ: $priceQ with $spaceId failed");
        ajaxError('Invalid arguments');
        return;
    }

    if (array_key_exists('price', $price)) {
        $spaces .= $price['description'] . ' in the ' . $regionInfo['name'] . ' for ' . $dolfmt->formatCurrency($price['price'], 'USD') . PHP_EOL;
    }
    // see if there already is an entry for this space for this vendor
    $vendorQ = <<<EOS
SELECT es.id, item_requested, item_approved, item_purchased, price, paid, transid, membershipCredits
FROM exhibitorYears ey
JOIN exhibitorRegionYears exRY ON ey.id = exRY.exhibitorYearId
JOIN exhibitorSpaces es ON exRY.id = es.exhibitorRegionYear
WHERE spaceId = ? and exhibitorYearId = ?;
EOS;
    $vendorR = dbSafeQuery($vendorQ, 'ii', array($spaceid, $eyId));
    if ($vendorR == false || $vendorR->num_rows == 1)
        $exhibitorSpace = $vendorR->fetch_assoc();
    else {
        $exhibitorSpace = array();
        $exhibitorSpace['id'] = -1;
        $exhibitorSpace['item_requested'] = 0;
    }

    // now add/update the vendor_space record for the number requested
    if ($exhibitorSpace['id'] < 0) {
        if ($priceId > 0) {
            // insert a new record because its a new request
            $insertQ = 'INSERT INTO exhibitorSpaces(exhibitorRegionYear, spaceId, item_requested, time_requested) VALUES(?, ?, ?, now());';
            $rowid = dbSafeInsert($insertQ, 'iii', array($exRYId, $spaceid, $priceId));
        }
    } else if ($priceId > 0) {
        // update for new/changed item
        $updateQ = 'UPDATE exhibitorSpaces SET item_requested = ?, time_requested = now() WHERE id = ?;';
        $numrows = dbSafeCmd($updateQ, 'ii', array($priceId, $exhibitorSpace['id']));
    } else {
        // clear cancelled item
        $updateQ = 'UPDATE exhibitorSpaces SET item_requested = NULL, time_requested = NULL WHERE id = ?;';
        $numrows = dbSafeCmd($updateQ, 'i', array($exhibitorSpace['id']));
    }
}

load_email_procs();
$emails = request($exhibitorInfo, $regionInfo, $portalName, $spaces);
if ($exhibitorInfo['exhibitorEmail'] == $exhibitorInfo['contactEmail'] || $exhibitorInfo['contactEmail'] == '')
    $cc = $exhibitorInfo['exhibitorEmail'];
else
    $cc = array($exhibitorInfo['exhibitorEmail'], $exhibitorInfo['contactEmail']);

    $return_arr = send_email($conf['regadminemail'], $regionInfo['ownerEmail'], $cc, $regionInfo['name'] . " Request", $emails[0] , $emails[1]);

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}
if (array_key_exists('email_error', $return_arr)) {
    $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
} else {
    $response['success'] = "Request sent";
}

$exhibitorSQ = <<<EOS
SELECT *
FROM vw_ExhibitorSpace
WHERE exhibitorId = ? and conid = ? and portalType = ?;
EOS;

$exhibitorSR = dbSafeQuery($exhibitorSQ, 'iis', array($vendor, $conid, $portalType));
$exhibitorSpaceList = array();
while ($space = $exhibitorSR->fetch_assoc()) {
    $exhibitorSpaceList[$space['spaceId']] = $space;
}
$exhibitorSR->free();
$response['exhibitor_spacelist'] = $exhibitorSpaceList;

ajaxSuccess($response);
?>
