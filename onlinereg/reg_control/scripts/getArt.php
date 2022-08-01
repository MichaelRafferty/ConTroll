<?php
global $db_ini;

require_once "../lib/base.php";

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
