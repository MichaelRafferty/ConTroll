<?php
require_once('../lib/base.php');
require_once(__DIR__ . '/../../lib/ajax_functions.php');
$ini = redirect_https();

$response = array("post" => $_POST, "get" => $_GET);

$vendorTestQ = <<<EOS
SELECT id
FROM vendors
WHERE email=?;
EOS;
$vendorTest = dbSafeQuery($vendorTestQ, 's', array(trim($_POST['email'])));
if ($vendorTest->num_rows != 0) {
    $response['status'] = 'error';
    $response['message'] = "Another account already exists with that email, please login or contact regadmin@bsfs.org for assistance";
    ajaxSuccess($response);
    exit();
}
// email address validated on the source side
$vendorInsertQ = <<<EOS
INSERT INTO vendors (name, website, description, email, password, need_new, confirm, addr, addr2, city, state, zip, publicity) 
values (?,?,?,?,?,?,?,?,?,?,?,?,?);
EOS;
$typestr = 'ssssssssssssi';
$paramarr = array(
    trim($_POST['name']),
    trim($_POST['website']),
    trim($_POST['description']),
    trim($_POST['email']),
    password_hash(trim($_POST['password']), PASSWORD_DEFAULT),
    0, // need_new_passwd
    0, // confirm
    trim($_POST['addr']),
    trim($_POST['addr2']),
    trim($_POST['city']),
    trim($_POST['state']),
    trim($_POST['zip']),
    trim($_POST['publicity']) == 'on' ? 1 : 0
);
$newVendor = dbSafeInsert($vendorInsertQ, $typestr, $paramarr);

$response['newVendor'] = $newVendor;
$response['status'] = 'success';

//insert code to do login here

ajaxSuccess($response);
?>
