<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array("post" => $_POST, "get" => $_GET);
$vendor = 0;

if(isset($_SESSION['id'])) {
    $vendor = $_SESSION['id'];
} else {
    $response['status']='error';
    $response['message']='Authentication Failure';
    ajaxSuccess($response);
    exit();
}

if($_POST['oldPassword'] == $_POST['password']) {
    $response['status']='error';
    $response['message']='Please select a new password';
    ajaxSuccess($response);
    exit();
}

$testQ = "SELECT password FROM vendors WHERE id=$vendor;";
$testPw = fetch_safe_assoc(dbQuery($testQ));

if(!password_verify($_POST['oldPassword'], $testPw['password'])) {
    $response['status']='error';
    $response['message']='Authentication Failure';
    ajaxSuccess($response);
    exit();
} else {
    $response['pwcheck'] = 'passwd';
}

$updateQ = "UPDATE vendors SET password='"
    . password_hash($_POST['password'], PASSWORD_DEFAULT) . "'"
    . ", need_new=0"
    . " WHERE id=$vendor";
dbQuery($updateQ);

$response['status']='success';

ajaxSuccess($response);
?>
