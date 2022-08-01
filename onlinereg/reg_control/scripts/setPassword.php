<?php
global $db_ini;

require_once "../lib/base.php";

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
