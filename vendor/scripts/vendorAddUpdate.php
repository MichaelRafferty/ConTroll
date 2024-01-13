<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$vconf = get_conf('vendor');
$vemail = $vconf['vendor'];

$response = array('post' => $_POST, 'get' => $_GET);

if (!(array_key_exists('vendorEmail', $_POST) && array_key_exists('vendorName', $_POST) && array_key_exists('profileMode', $_POST))) {
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
FROM vendors
WHERE vendorEmail=? OR vendorName=?
EOS;
        $vendorTest = dbSafeQuery($vendorTestQ, 'ss', array(trim($_POST['vendorEmail']), trim($_POST['vendorName'])));
        if ($vendorTest->num_rows != 0) {
            $response['status'] = 'error';
            $response['message'] = "Another account already exists with that name or email, please login or contact $vemail for assistance";
            ajaxSuccess($response);
            exit();
        }

        // create the vendor
        // email address validated on the source side
        $vendorInsertQ = <<<EOS
INSERT INTO vendors (vendorName, vendorEmail, vendorPhone, website, description, contactName, contactEmail, contactPhone, password, need_new, confirm, 
                     addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, publicity) 
values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);
EOS;
        $typestr = 'sssssssssiisssssssssssssi';
        $paramarr = array(
            trim($_POST['vendorName']),
            trim($_POST['vendorEmail']),
            trim($_POST['vendorPhone']),
            trim($_POST['website']),
            trim($_POST['description']),
            trim($_POST['contactName']),
            trim($_POST['contactEmail']),
            trim($_POST['contactPhone']),
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
            $publicity
        );
        $newVendor = dbSafeInsert($vendorInsertQ, $typestr, $paramarr);

        $response['newVendor'] = $newVendor;
        $response['status'] = 'success';
        $response['messsage'] = "$profileType " . trim($_POST['vendorName']) . " created";
        break;

        case 'update':
        $vendor = 0;

        if (isset($_SESSION['id'])) {
            $vendor = $_SESSION['id'];
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Authentication Failure';
            ajaxSuccess($response);
            exit();
        }

        $updateQ = <<<EOS
UPDATE vendors
SET vendorName=?, vendorEmail=?, vendorPhone=?, website=?, description=?, contactName=?, contactEmail=?, contactPhone=?,
    addr=?, addr2=?, city=?, state=?, zip=?, country=?, shipCompany=?, shipAddr=?, shipAddr2=?, shipCity=?, shipState=?, shipZip=?, shipCountry=?, publicity=?
WHERE id=?
EOS;
        $updateArr = array(
            trim($_POST['vendorName']),
            trim($_POST['vendorEmail']),
            trim($_POST['vendorPhone']),
            trim($_POST['website']),
            trim($_POST['description']),
            trim($_POST['contactName']),
            trim($_POST['contactEmail']),
            trim($_POST['contactPhone']),
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
        $numrows = dbSafeCmd($updateQ, 'sssssssssssssssssssssii', $updateArr);
        if ($numrows == 1) {
            $response['status'] = 'success';
            $response['message'] = 'Profile Updated';
            // get the update info
            $vendorQ = <<<EOS
SELECT vendorName, vendorEmail, vendorPhone, website, description, contactName, contactEmail, contactPhone, need_new, confirm, 
       addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, publicity
FROM vendors
WHERE id=?;
EOS;
            $info = dbSafeQuery($vendorQ, 'i', array($vendor))->fetch_assoc();
            $response['info'] = $info;

        } else if ($numrows == 0) {
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
