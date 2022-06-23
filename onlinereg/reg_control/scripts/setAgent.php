<?php
global $ini;
if (!$ini)
    $ini = parse_ini_file(__DIR__ . "/../../../config/reg_conf.ini", true);
if ($ini['reg']['https'] <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

$con = get_con();
$conid=$con['id'];

$check_auth = google_init("ajax");
$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST) || !isset($_POST['artid']) || !isset($_POST['agent'])) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$agentQ = "UPDATE artshow SET agent='".sql_safe($_POST['agent'])."'"
    . " WHERE conid=$conid AND artid='" . sql_safe($_POST['artid'])."';";
$response['query'] = $agentQ;
dbQuery($agentQ);

ajaxSuccess($response);
?>
