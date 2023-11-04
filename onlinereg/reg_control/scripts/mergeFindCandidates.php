<?php
global $db_ini;

require_once "../lib/base.php";
require_once(__DIR__ . '/../../../lib/checkmerge.php');

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('remain', $_POST)) {
    $response['error'] = 'Invalid Calling Sequence';
    ajaxSuccess($response);
    exit();
}

$remain = $_POST['remain'];

if (array_key_exists('matchCount', $_POST)) {
    $matchCount = $_POST['matchCount'];
} else {
    $matchCount = 9;
}

$response = checkmerge($remain, $matchCount);

ajaxSuccess($response);
?>
