<?php
require_once "../lib/base.php";


$response = array("post" => $_POST, "get" => $_GET);

$con = get_con();
$conid=$con['id'];
$check_auth=false;

if(!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if(!isset($_POST) || !isset($_POST['artist']) || !isset($_POST['item'])) { 
    $response['error'] = "Need Item Info";
    ajaxSuccess($response);
    exit();
}

$con = get_conf("con");
$conid=$con['id'];

$item= $_POST['item'];
$artist = $_POST['artist'];

$artistQ = "SELECT id FROM artshow WHERE conid=? AND art_key=?";
$artistR = dbSafeQuery($artistQ, 'ii', array($conid, $artist));
$art = $artistR->fetch_assoc();
$artid = $art['id'];

$itemQ = <<<EOS
SELECT S.art_key, I.item_key, I.title, I.type, I.status, I.min_price, I.sale_price
    , I.final_price, I.quantity, V.name, TRIM(CONCAT_WS(' ', P.first_name, P.last_name)) as name
FROM artItems I
JOIN artshow S ON (S.id=I.artshow)
JOIN artist A ON (A.id=S.artid)
JOIN perinfo P ON (P.id=S.perid)
JOIN vendors V ON (V.id=A.vendor)
WHERE I.conid=? AND I.item_key=? AND I.artshow=?;
EOS;

$itemR = dbSafeQuery($itemQ, 'iii', array($conid, $item, $artid));
if($itemR->num_rows > 0) {
    $item = $itemR->fetch_assoc();

    $response['item'] = $item;
} else { 
    $response['noitem'] = "TRUE";
}

ajaxSuccess($response);
?>
