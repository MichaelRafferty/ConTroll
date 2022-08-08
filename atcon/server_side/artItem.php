<?php
require_once "lib/base.php";

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

$item= $_POST['item'];
$artist = $_POST['artist'];

$artistQ = "SELECT id FROM artshow WHERE conid=? AND art_key=?";
$artistR = dbSafeQuery($artistQ, 'ii', array($conid, $artist));
$art = fetch_safe_assoc($artistR);
$artid = $art['id'];

$itemQ = <<<EOS
SELECT S.art_key, I.item_key, I.title, I.type, I.status, I.min_price, I.sale_price
    , I.final_price, I.quantity, V.name, concat_ws(' ', P.first_name, P.last_name) as name
FROM artItems I
JOIN artshow S ON (S.id=I.artshow
JOIN artist A ON (A.id=S.artid)
JOIN perinfo P ON (P.id=S.perid
JOIN vendors V ON (V.id=A.vendor)
WHERE I.conid=? AND I.item_key=? AND I.artshow=?;
EOS;

$itemR = dbSafeQuery($itemQ, 'iii', array($conid, $item, $artid));
if($itemR->num_rows > 0) {
    $item = fetch_safe_assoc($itemR);

    $response['item'] = $item;
} else { 
    $response['noitem'] = "TRUE";
}

ajaxSuccess($response);
?>