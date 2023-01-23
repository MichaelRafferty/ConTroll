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

$updateQ = "UPDATE vendors SET website='"
    . sql_safe($_POST['website']) . "'"
    . ", description='"
    . sql_safe($_POST['description']) . "'"
    . " WHERE id=$vendor";
dbQuery($updateQ);

ajaxSuccess($response);
?>
