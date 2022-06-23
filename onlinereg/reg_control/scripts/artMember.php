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

$check_auth = google_init("ajax");
$perm = "art_sales";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET) || !isset($_GET['perid'])) {
    $response['error'] = "Need Perid";
    ajaxSuccess($response);
    exit();
}

$perid = sql_safe($_GET['perid']);

$userQ = "SELECT id, concat_ws(' ', first_name, last_name, suffix) as name"
    . ", badge_name as badge"
    . " FROM perinfo WHERE id=$perid";
$userR = dbQuery($userQ);
$user = fetch_safe_assoc($userR);
$response['id']= $user['id'];
$response['name'] = $user['name'];
$response['badge'] = $user['badge'];


ajaxSuccess($response);
?>
