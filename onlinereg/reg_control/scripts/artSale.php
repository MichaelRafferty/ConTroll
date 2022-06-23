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

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}
if(!isset($_POST) || !isset($_POST['art_key']) || !isset($_POST['item_key'])) {
    $response['error'] = "Invalid Query";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST['perid']) || !isset($_POST['price'])) {
    $response['error'] = "Invalid Request";
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid=$con['id'];

$artist = sql_safe($_POST['art_key']);
$item = sql_safe($_POST['item_key']);
$query = "SELECT I.type, I.id, I.title, V.name, min_price, sale_price, quantity FROM artshow as S JOIN artItems as I ON I.artshow=S.id JOIN artist as A on A.id=S.artid JOIN vendors as V on V.id=A.vendor WHERE S.art_key = '$artist' and I.item_key = '$item' and I.conid=$conid;";


$result = dbQuery($query);
if($result->num_rows != 1) {
    $response['error'] = "Invalid Query: " . $result->num_rows . " possible matches.";
    ajaxSuccess($response);
    exit();
}

$response['item'] = fetch_safe_assoc($result);
$itemId = $response['item']['id'];

if($response['item']['type'] != "art") {
    $response['error'] = "Only works for art items";
    ajaxSuccess($response);
    exit();
}

$update = "UPDATE artItems SET bidder='" . sql_safe($_POST['perid']) . "'"
                         . ", final_price='". sql_safe($_POST['price'])."'"
          . " WHERE id = '$itemId';";
$response['query'] = $update;
dbQuery($update);



ajaxSuccess($response);
?>
