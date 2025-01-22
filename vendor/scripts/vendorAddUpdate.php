<?php
require_once('../lib/base.php');
require_once('../../lib/exhibitorYears.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$vconf = get_conf('vendor');
$vemail = $vconf['vendor'];
$con = get_conf('con');
$conid = $con['id'];

$response = array('post' => $_POST, 'get' => $_GET);

if (array_key_exists('eyID', $_SESSION)) {
    $exyID = $_SESSION['eyID'];
} else {
    $exyID = null;
}

if (!(array_key_exists('exhibitorEmail', $_POST) && array_key_exists('exhibitorName', $_POST) && array_key_exists('profileMode', $_POST))) {
    $response['status'] = 'error';
    $response['message'] = "Calling sequence error, contact $vemail for assistance";
    ajaxSuccess($response);
    exit();
}

$profileMode = $_POST['profileMode'];
$profileType = $_POST['profileType'];

$publicity = $_POST['publicity'];

// default mailin
if (array_key_exists('mailin', $_POST)) {
    $mailin = $_POST['mailin'];
} else {
    $mailin = 'N';
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

$description = trim($_POST['description']);
if (str_contains(strtolower($description), '<script')) {
    $response['status'] = 'error';
    $response['message'] = 'The Description may not include &lt;script&gt; tags';
    ajaxSuccess($response);
    exit();
}
$salesTaxId = null;
if (array_key_exists('salesTaxId', $_POST)) {
    $salesTaxId = trim($_POST['salesTaxId']);
    if ($salesTaxId == '')
        $salesTaxId = null;
}
// if register check for existence of vendor
switch ($profileMode) {
    case 'register':
        $vendorTestQ = <<<EOS
SELECT id
FROM exhibitors
WHERE exhibitorEmail=? OR exhibitorName=?
EOS;
        $vendorTest = dbSafeQuery($vendorTestQ, 'ss', array(trim($_POST['exhibitorEmail']), trim($_POST['exhibitorName'])));
        if ($vendorTest->num_rows != 0) {
            $response['status'] = 'error';
            $response['message'] = "Another account already exists with that name (" . trim($_POST['exhibitorName']). ") or email (" . trim($_POST['exhibitorEmail']) . "), please login or contact $vemail for assistance";
            ajaxSuccess($response);
            exit();
        }

        // create the vendor
        // email address validated on the source side
        // check for script tag embedded in description

        if ($artistName != null) {
            $exhibitorInsertQ = <<<EOS
INSERT INTO exhibitors (artistName, exhibitorName, exhibitorEmail, exhibitorPhone, salesTaxId, website, description, password, need_new, confirm, 
                     addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, publicity) 
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);
EOS;
            $typestr = 'ssssssssiisssssssssssssi';
            $paramarr = array (
                trim($_POST['artistName']),
                trim($_POST['exhibitorName']),
                trim($_POST['exhibitorEmail']),
                trim($_POST['exhibitorPhone']),
                $salesTaxId,
                trim($_POST['website']),
                $description,
                password_hash(trim($_POST['password']), PASSWORD_DEFAULT),
                0, // need_new_passwd
                0, // confirm
                trim($_POST['addr']),
                trim($_POST['addr2']),
                trim($_POST['city']),
                trim($_POST['state']),
                trim($_POST['zip']),
                trim($_POST['country']),
                $shipCompany,
                $shipAddr,
                $shipAddr2,
                $shipCity,
                $shipState,
                $shipZip,
                $shipCountry,
                $publicity
            );
        } else {
            $exhibitorInsertQ = <<<EOS
INSERT INTO exhibitors (exhibitorName, exhibitorEmail, exhibitorPhone, salesTaxId, website, description, password, need_new, confirm, 
                     addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, publicity) 
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);
EOS;
            $typestr = 'sssssssiisssssssssssssi';
            $paramarr = array (
                trim($_POST['exhibitorName']),
                trim($_POST['exhibitorEmail']),
                trim($_POST['exhibitorPhone']),
                $salesTaxId,
                trim($_POST['website']),
                $description,
                password_hash(trim($_POST['password']), PASSWORD_DEFAULT),
                0, // need_new_passwd
                0, // confirm
                trim($_POST['addr']),
                trim($_POST['addr2']),
                trim($_POST['city']),
                trim($_POST['state']),
                trim($_POST['zip']),
                trim($_POST['country']),
                $shipCompany,
                $shipAddr,
                $shipAddr2,
                $shipCity,
                $shipState,
                $shipZip,
                $shipCountry,
                $publicity
            );
        }
        $newExhibitor = dbSafeInsert($exhibitorInsertQ, $typestr, $paramarr);

        // create the year related functions
        $newyId = exhibitorBuildYears($newExhibitor, $_POST['contactName'], $_POST['contactEmail'], $_POST['contactPhone'], $_POST['cpassword'], $mailin);
        exhibitorCheckMissingSpaces($newExhibitor, $newyId);
        break;

    case 'update':
    case 'review':
        $vendor = 0;

        if (isset($_SESSION['id'])) {
            $vendor = $_SESSION['id'];
            $vendorYear = $_SESSION['eyID'];
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Authentication Failure';
            ajaxSuccess($response);
            exit();
        }

        // check to see if mailin becomes yes and there are mailin-no spaces
        if ($mailin == 'Y') {
            $checkQ = <<<EOS
SELECT count(*) numno
FROM exhibitorSpaces exS
JOIN exhibitorRegionYears exRY ON exS.exhibitorRegionYear = exRY.id
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
JOIN exhibitsSpaces es ON exS.spaceId = es.id
JOIN exhibitsRegionYears ery ON exRY.exhibitsRegionYearId = ery.id
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
JOIN exhibitsRegionTypes ert ON er.regionType = ert.regionType
WHERE ert.mailinAllowed = 'N' AND exY.id=? AND
      (exS.item_requested IS NOT NULL OR exS.item_approved IS NOT NULL OR exS.item_purchased IS NOT NULL)
EOS;
            $checkR = dbSafeQuery($checkQ, 'i', array($exyID));
            if ($checkR == false || $checkR->num_rows != 1) {
                $response['error'] = 'error';
                $response['message'] = 'Error checkin mail in restrictions';
                break;
            }
            $conflicts = $checkR->fetch_row()[0];
            if ($conflicts > 0) {
                $response['status'] = 'warn';
                $response['message'] = 'You have space in areas that do not allow mail-in.  You cannot select mail-in.  Please switch back to on-site.';
                break;
            }
        }

        if ($artistName != null) {
            $updateQ = <<<EOS
UPDATE exhibitors
SET artistName = ?, exhibitorName=?, exhibitorEmail=?, exhibitorPhone=?, salesTaxId = ?, website=?, description=?,
    addr=?, addr2=?, city=?, state=?, zip=?, country=?, shipCompany=?, shipAddr=?, shipAddr2=?, shipCity=?, shipState=?, shipZip=?, shipCountry=?, publicity=?
WHERE id=?
EOS;
            $updateArr = array (
                $artistName,
                trim($_POST['exhibitorName']),
                trim($_POST['exhibitorEmail']),
                trim($_POST['exhibitorPhone']),
                $salesTaxId,
                trim($_POST['website']),
                $description,
                trim($_POST['addr']),
                trim($_POST['addr2']),
                trim($_POST['city']),
                trim($_POST['state']),
                trim($_POST['zip']),
                trim($_POST['country']),
                $shipCompany,
                $shipAddr,
                $shipAddr2,
                $shipCity,
                $shipState,
                $shipZip,
                $shipCountry,
                $publicity,
                $vendor
            );
            $dt = 'ssssssssssssssssssssii';
        } else {
            $updateQ = <<<EOS
UPDATE exhibitors
SET exhibitorName=?, exhibitorEmail=?, exhibitorPhone=?, salesTaxId=?, website=?, description=?,
    addr=?, addr2=?, city=?, state=?, zip=?, country=?, shipCompany=?, shipAddr=?, shipAddr2=?, shipCity=?, shipState=?, shipZip=?, shipCountry=?, publicity=?
WHERE id=?
EOS;
            $updateArr = array (
                trim($_POST['exhibitorName']),
                trim($_POST['exhibitorEmail']),
                trim($_POST['exhibitorPhone']),
                $salesTaxId,
                trim($_POST['website']),
                $description,
                trim($_POST['addr']),
                trim($_POST['addr2']),
                trim($_POST['city']),
                trim($_POST['state']),
                trim($_POST['zip']),
                trim($_POST['country']),
                $shipCompany,
                $shipAddr,
                $shipAddr2,
                $shipCity,
                $shipState,
                $shipZip,
                $shipCountry,
                $publicity,
                $vendor
            );
            $dt = 'sssssssssssssssssssii';
        }
        // now perform the update based on artist vs dealers form.
        $numrows = dbSafeCmd($updateQ, $dt, $updateArr);

        $updateQ = <<<EOS
UPDATE exhibitorYears
SET contactName=?, contactEmail=?, contactPhone=?, mailin = ?, needReview = 0
WHERE id=?
EOS;
            $updateArr = array(
                trim($_POST['contactName']),
                trim($_POST['contactEmail']),
                trim($_POST['contactPhone']),
                $mailin,
                $vendorYear
            );
            $numrows1 = dbSafeCmd($updateQ, 'ssssi', $updateArr);
        if ($numrows == 1 || $numrows1 == 1) {
            $response['status'] = 'success';
            $response['message'] = 'Profile Updated';
            // get the update info
            $vendorQ = <<<EOS
SELECT artistName, exhibitorName, exhibitorEmail, exhibitorPhone, salesTaxId, website, description, e.need_new AS eNeedNew, e.confirm AS eConfirm, ey.mailin,
       ey.contactName, ey.contactEmail, ey.contactPhone, ey.need_new AS cNeedNew, ey.confirm AS cConfirm, ey.needReview as needReview,
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
            $response['error'] = 'error';
            $response['message'] = 'Error encountered updating profile';
        }
        break;

    default:
        $response['status'] = 'error';
        $response['message'] = "Invalid mode, contact $vemail for assistance";
}

ajaxSuccess($response);
?>
