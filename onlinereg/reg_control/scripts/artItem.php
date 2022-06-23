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

if(!isset($_POST) || !isset($_POST['artist']) || !isset($_POST['item'])) {
    $response['error'] = "Need Item Info";
    ajaxSuccess($response);
    exit();
}

$con = get_conf("con");
$conid=$con['id'];

$item= sql_safe($_POST['item']);
$artist = sql_safe($_POST['artist']);

$artistQ = "SELECT id FROM artshow WHERE conid=$conid AND art_key=$artist";
$artistR = dbQuery($artistQ);
$art = fetch_safe_assoc($artistR);
$artid = $art['id'];

$itemQ = "SELECT S.art_key, I.item_key"
        . ", I.title, I.type, I.status, I.min_price, I.sale_price"
        . ", I.final_price, I.quantity, A.art_name"
        . ", concat_ws(' ', P.first_name, P.last_name) as name"
    . " FROM artItems as I"
        . " JOIN artshow as S on S.id=I.artshow"
        . " JOIN artist as A on A.id=S.artid"
        . " JOIN perinfo as P on P.id=S.perid"
    . " WHERE I.conid=$conid AND I.item_key=$item AND I.artshow=$artid;";
$itemR = dbQuery($itemQ);
if($itemR->num_rows > 0) {
    $item = fetch_safe_assoc($itemR);

    $response['item'] = $item;
} else {
    $response['noitem'] = "TRUE";
}

ajaxSuccess($response);
?>
