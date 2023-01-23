<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "../lib/ajax_functions.php";
require_once "../lib/db_functions.php";
db_connect();


$response = array("post" => $_POST, "get" => $_GET);

$vendorTestQ = "SELECT id FROM vendors WHERE email='".sql_safe($_POST['email'])."';";
$vendorTest = dbQuery($vendorTestQ);
if ($vendorTest->num_rows != 0) {
    $response['status'] = 'error';
    $response['message'] = "Another account already exists with that email, please login or contact regadmin@bsfs.org for assistance";
    ajaxSuccess($response);
    exit();
}
//insert code to create email validation here.

$vendorInsertQ = "INSERT INTO vendors (name, website, description, email, password, need_new, confirm) values ('"
    . sql_safe($_POST['name']) . "'"
    . ", '" . sql_safe($_POST['website']) . "'"
    . ", '" . sql_safe($_POST['description']) . "'"
    . ", '" . strtolower(sql_safe($_POST['email'])) . "'"
    . ", '" . password_hash($_POST['password'], PASSWORD_DEFAULT) . "'"
    . ", 'N', 'N');";
$newVendor = dbInsert($vendorInsertQ);

$response['newVendor'] = $newVendor;
$response['status'] = 'success';

//insert code to do login here

ajaxSuccess($response);
?>
