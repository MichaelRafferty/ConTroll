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
$perm = "search";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET) || !isset($_GET['id'])) {
    $response['error'] = "Invalid Query";
    ajaxSuccess($response);
    exit();
}

$query = "SELECT concat_ws(' ', first_name, middle_name, last_name) as full_name, badge_name FROM perinfo where id='".sql_safe($_GET['id'])."';";
$result = dbQuery($query);

if($result->num_rows == 1) {
    $response['result'] = fetch_safe_assoc($result);
} else {
    $response['error'] = "Query found " . $result->num_rows . " matches";
}

ajaxSuccess($response);
?>
