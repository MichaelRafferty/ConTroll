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
$perm = "artist";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != "GET") { ajaxError("No Data"); }
if(!isset($_GET['vendor'])) { ajaxError("No Data"); }

$vendor = sql_safe($_GET['vendor']);

$str = str_shuffle(
"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#"
.
"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#"
);

$len = rand(7,12);
$start = rand(0,strlen($str)-$len);


$newpasswd = substr($str, $start, $len);

$hash = password_hash($newpasswd, PASSWORD_DEFAULT);
$pwQ = "UPDATE vendors SET password='$hash', need_new=true where id='$vendor';";
dbQuery($pwQ);

$response['password'] = $newpasswd;

ajaxSuccess($response);
?>
