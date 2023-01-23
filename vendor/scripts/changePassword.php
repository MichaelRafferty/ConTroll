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

session_start();
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
