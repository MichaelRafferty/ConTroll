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
$perm = "art_control";

$con = get_conf('con');
$conid= $con['id'];

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET) || !isset($_GET['art_key']) || !isset($_GET['item_key'])) {
    $response['error'] = "Invalid Query";
    ajaxSuccess($response);
    exit();
}

$artist = sql_safe($_GET['art_key']);
$item = sql_safe($_GET['item_key']);
$query = "SELECT I.type, I.id, I.title, V.name, min_price, sale_price, quantity FROM artshow as S JOIN artItems as I ON I.artshow=S.id JOIN artist as A on A.id=S.artid JOIN vendors as V on V.id=A.vendor WHERE S.art_key = '$artist' and I.item_key = '$item' and I.conid=$conid;";


$result = dbQuery($query);
if($result->num_rows != 1) {
    $response['error'] = "Invalid Query: " . $result->num_rows . " possible matches.";
    ajaxSuccess($response);
    exit();
}

$response['result'] = fetch_safe_assoc($result);

ajaxSuccess($response);
?>
