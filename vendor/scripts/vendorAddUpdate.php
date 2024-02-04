<?php
require_once('../lib/base.php');
require_once('../lib/vendorYears.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$vconf = get_conf('vendor');
$vemail = $vconf['vendor'];
$con = get_conf('con');
$conid = $con['id'];

$response = array('post' => $_POST, 'get' => $_GET);

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
            $response['message'] = "Another account already exists with that name or email, please login or contact $vemail for assistance";
            ajaxSuccess($response);
            exit();
        }

        // create the vendor
        // email address validated on the source side
        $exhibitorInsertQ = <<<EOS
INSERT INTO exhibitors (exhibitorName, exhibitorEmail, exhibitorPhone, website, description, password, need_new, confirm, 
                     addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, publicity) 
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);
EOS;
        $typestr = 'ssssssiisssssssssssssi';
        $paramarr = array(
            trim($_POST['exhibitorName']),
            trim($_POST['exhibitorEmail']),
            trim($_POST['exhibitorPhone']),
            trim($_POST['website']),
            trim($_POST['description']),
            password_hash(trim($_POST['password']), PASSWORD_DEFAULT),
            0, // need_new_passwd
            0, // confirm
            trim($_POST['addr']),
            trim($_POST['addr2']),
            trim($_POST['city']),
            trim($_POST['state']),
            trim($_POST['zip']),
            trim($_POST['country']),
            trim($_POST['shipCompany']),
            trim($_POST['shipAddr']),
            trim($_POST['shipAddr2']),
            trim($_POST['shipCity']),
            trim($_POST['shipState']),
            trim($_POST['shipZip']),
            trim($_POST['shipCountry']),
            $_POST['publicity']
        );
        $newExhibitor = dbSafeInsert($exhibitorInsertQ, $typestr, $paramarr);

        // create the year related functions
        vendorBuildYears($newExhibitor, $_POST['contactName'], $_POST['contactEmail'], $_POST['contactPhone'], $_POST['password']);
        break;

    case 'update':
    case 'review':
        $vendor = 0;

        if (isset($_SESSION['id'])) {
            $vendor = $_SESSION['id'];
            $vendorYear = $_SESSION['cID'];
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Authentication Failure';
            ajaxSuccess($response);
            exit();
        }

        $updateQ = <<<EOS
UPDATE exhibitors
SET exhibitorName=?, exhibitorEmail=?, exhibitorPhone=?, website=?, description=?,
    addr=?, addr2=?, city=?, state=?, zip=?, country=?, shipCompany=?, shipAddr=?, shipAddr2=?, shipCity=?, shipState=?, shipZip=?, shipCountry=?, publicity=?
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
            trim($_POST['shipCompany']),
            trim($_POST['shipAddr']),
            trim($_POST['shipAddr2']),
            trim($_POST['shipCity']),
            trim($_POST['shipState']),
            trim($_POST['shipZip']),
            trim($_POST['shipCountry']),
            $publicity,
            $vendor
        );
        $numrows = dbSafeCmd($updateQ, 'ssssssssssssssssssii', $updateArr);

        if (array_key_exists('mailin', $_POST)) {
            $mailin = $_POST['mailin'];
        } else {
            $mailin = 'N';
        }

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
SELECT exhibitorName, exhibitorEmail, exhibitorPhone, website, description, e.need_new AS eNeedNew, e.confirm AS eConfirm, 
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
            $response['error'] = 'success';
            $response['message'] = 'Error encountered updating profile';
        }
        break;

    default:
        $response['status'] = 'error';
        $response['message'] = "Invalid mode, contact $vemail for assistance";
}

ajaxSuccess($response);
?>
