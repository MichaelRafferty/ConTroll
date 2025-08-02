<?php
require_once '../lib/base.php';
require_once('../../lib/exhibitorYears.php');
// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'exhibitor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];
$vconf = get_conf('vendor');
$vemail = $vconf['vendor'];

if (!(array_key_exists('exhibitorEmail', $_POST) && array_key_exists('exhibitorName', $_POST) && array_key_exists('profileMode', $_POST))) {
    $response['status'] = 'error';
    $response['message'] = "Calling sequence error, contact $vemail for assistance";
    ajaxSuccess($response);
    exit();
}

$profileMode = $_POST['profileMode'];
$profileType = $_POST['profileType'];

$publicity = 0;
if (array_key_exists('publicity', $_POST)) {
    $publicity = trim($_POST['publicity']) == 'on' ? 1 : 0;
}

// default mailin
if (array_key_exists('mailin', $_POST)) {
    $mailin = $_POST['mailin'];
} else {
    $mailin = 'N';
}

if (array_key_exists('exhNotes', $_POST)) {
    $exhNotes = $_POST['exhNotes'] == null ? '' : trim($_POST['exhNotes']);
} else {
    $exhNotes = '';
}

if (array_key_exists('contactNotes', $_POST)) {
    $contactNotes = $_POST['contactNotes'] == null ? '' : trim($_POST['contactNotes']);
} else {
    $contactNotes = '';
}

// now for the optional fields
$shipCompany = null;
if (array_key_exists('shipCompany', $_POST) && $_POST['shipCompany'] != null) {
    $shipCompany = trim($_POST['shipCompany']);
}
$shipAddr = null;
if (array_key_exists('shipAddr', $_POST) && $_POST['shipAddr'] != null) {
    $shipAddr = trim($_POST['shipAddr']);
}
$shipAddr2 = null;
if (array_key_exists('shipAddr2', $_POST) && $_POST['shipAddr2'] != null) {
    $shipAddr2 = trim($_POST['shipAddr2']);
}
$shipCity = null;
if (array_key_exists('shipCity', $_POST) && $_POST['shipCity'] != null) {
    $shipCity = trim($_POST['shipCity']);
}
$shipState = null;
if (array_key_exists('shipState', $_POST) && $_POST['shipState'] != null) {
    $shipState = trim($_POST['shipState']);
}
$shipZip = null;
if (array_key_exists('shipZip', $_POST) && $_POST['shipZip'] != null) {
    $shipZip = trim($_POST['shipZip']);
}
$shipCountry = null;
if (array_key_exists('shipCountry', $_POST) && $_POST['shipCountry'] != null) {
    $shipCountry = trim($_POST['shipCountry']);
}

// artist name is only in the Artist version of the form, it should be NULL for dealers
$artistName = null;
if (array_key_exists('artistName', $_POST)) {
    $artistName = trim($_POST['artistName']);
}

// if register check for existence of vendor
switch ($profileMode) {
    case 'register':
    case 'add':
        $vendorTestQ = <<<EOS
SELECT id
FROM exhibitors
WHERE exhibitorEmail=? OR exhibitorName=?
EOS;
        $vendorTest = dbSafeQuery($vendorTestQ, 'ss', array(trim($_POST['exhibitorEmail']), trim($_POST['exhibitorName'])));
        if ($vendorTest->num_rows != 0) {
            $response['status'] = 'error';
            $response['message'] = "Another account already exists with that name or email, please login or contact $vemail for assistance";
            ajaxSuccess($response);
            exit();
        }

        // create the vendor
        // email address validated on the source side
        $exhibitorInsertQ = <<<EOS
INSERT INTO exhibitors (artistName, exhibitorName, exhibitorEmail, exhibitorPhone, website, description, password, need_new, 
    addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, 
    publicity, notes) 
VALUES (?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);
EOS;
        $typestr = 'ssssssssssssssssssssis';
        $paramarr = array(
            trim($artistName),
            trim($_POST['exhibitorName']),
            trim($_POST['exhibitorEmail']),
            trim($_POST['exhibitorPhone']),
            trim($_POST['website']),
            trim($_POST['description']),
            password_hash(trim($_POST['password']), PASSWORD_DEFAULT),
            trim($_POST['addr']),
            trim($_POST['addr2']),
            trim($_POST['city']),
            trim($_POST['state']),
            trim($_POST['zip']),
            trim($_POST['country']),
            trim($shipCompany),
            trim($shipAddr),
            trim($shipAddr2),
            trim($shipCity),
            trim($shipState),
            trim($shipZip),
            trim($shipCountry),
            $_POST['publicity'],
            $exhNotes = '' ? null : $exhNotes
        );
        $newExhibitor = dbSafeInsert($exhibitorInsertQ, $typestr, $paramarr);

        // create the year related functions
        $yearId = exhibitorBuildYears($newExhibitor, $_POST['contactName'], $_POST['contactEmail'], $_POST['contactPhone'], $_POST['password'], $mailin,
            $contactNotes);
        exhibitorCheckMissingSpaces($newExhibitor, $yearId);
        break;

    case 'update':
    case 'review':
    case 'admin':
        $vendor = $_POST['exhibitorId'];
        $vendorYear = $_POST['exhibitorYearId'];

        $updateQ = <<<EOS
UPDATE exhibitors
SET exhibitorName=?, exhibitorEmail=?, exhibitorPhone=?, website=?, description=?,
    addr=?, addr2=?, city=?, state=?, zip=?, country=?, shipCompany=?, shipAddr=?, shipAddr2=?, shipCity=?, shipState=?, shipZip=?, shipCountry=?, 
    publicity=?, notes = ?
WHERE id=?
EOS;
        $updateArr = array(
            trim($_POST['exhibitorName']),
            trim($_POST['exhibitorEmail']),
            trim($_POST['exhibitorPhone']),
            trim($_POST['website']),
            trim($_POST['description']),
            trim($_POST['addr']),
            trim($_POST['addr2']),
            trim($_POST['city']),
            trim($_POST['state']),
            trim($_POST['zip']),
            trim($_POST['country']),
            trim($shipCompany),
            trim($shipAddr),
            trim($shipAddr2),
            trim($shipCity),
            trim($shipState),
            trim($shipZip),
            trim($shipCountry),
            $publicity,
            $exhNotes = '' ? null : $exhNotes,
            $vendor
        );
        $numrows = dbSafeCmd($updateQ, 'ssssssssssssssssssisi', $updateArr);

        $updateQ = <<<EOS
UPDATE exhibitorYears
SET contactName=?, contactEmail=?, contactPhone=?, mailin = ?, lastVerified = NOW(), notes = ?
WHERE id=?
EOS;
            $updateArr = array(
                trim($_POST['contactName']),
                trim($_POST['contactEmail']),
                trim($_POST['contactPhone']),
                $mailin,
                $contactNotes == '' ? null : $contactNotes,
                $vendorYear
            );
            $numrows1 = dbSafeCmd($updateQ, 'sssssi', $updateArr);
        if ($numrows == 1 || $numrows1 == 1) {
            $response['status'] = 'success';
            $response['message'] = 'Profile Updated';
            // get the update info
            $vendorQ = <<<EOS
SELECT exhibitorName, exhibitorEmail, exhibitorPhone, website, description, e.need_new AS eNeedNew,
       IFNULL(e.notes, '') AS exhNotes, IFNULL(ey.notes, '') AS contactNotes, ey.mailin, ey.contactName, ey.contactEmail, ey.contactPhone, 
       ey.need_new AS cNeedNew, DATEDIFF(now(), ey.lastVerified) AS DaysSinceLastVerified, ey.lastVerified,
       addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, publicity
FROM exhibitors e
LEFT OUTER JOIN exhibitorYears ey ON e.id = ey.exhibitorId
WHERE e.id=? AND ey.conid = ?;
EOS;
            $info = dbSafeQuery($vendorQ, 'ii', array($vendor, $conid))->fetch_assoc();
            $response['info'] = $info;
            $response['status'] = 'success';
            $response['message'] = 'Profile Updated';
        } else if ($numrows == 0 && $numrows1 == 0) {
            $response['status'] = 'success';
            $response['message'] = 'Nothing to update';
        } else {
            $response['error'] = 'success';
            $response['message'] = 'Error encountered updating profile';
        }
        break;

    default:
        $response['status'] = 'error';
        $response['message'] = "Invalid mode, contact $vemail for assistance";
}

ajaxSuccess($response);
