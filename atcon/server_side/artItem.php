<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

$con = get_con();
$conid=$con['id'];
$check_auth=false;

if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
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
        . ", I.final_price, I.quantity, V.name"
        . ", concat_ws(' ', P.first_name, P.last_name) as name"
    . " FROM artItems as I"
        . " JOIN artshow as S on S.id=I.artshow"
        . " JOIN artist as A on A.id=S.artid"
        . " JOIN perinfo as P on P.id=S.perid"
        . " JOIN vendors as V on V.id=A.vendor"
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
