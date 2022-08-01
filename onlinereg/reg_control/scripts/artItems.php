<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "art_control";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$conf = get_conf('con');

$con=get_con();
$conid=$con['id'];

$artQ = "SELECT I.item_key, I.title, I.type, I.status, I.location"
        . ", I.quantity, I.original_qty, I.min_price, I.sale_price"
        . ", I.final_price, S.art_key"
        . ", concat_ws(',', S.a_panel_list, S.a_table_list, S.p_panel_list, S.p_table_list) as loc_list"
        . ", V.name as artist"
        . ", TRIM(concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix)) as artist_name"
        . ", concat_ws(' ', B.first_name, B.middle_name, B.last_name, B.suffix, B.id) as bidder"
    . " FROM artItems as I JOIN artshow as S on S.id=I.artshow"
        . " JOIN artist as A ON A.id=S.artid"
        . " JOIN vendors as V on V.id=A.vendor"
        . " JOIN perinfo as P on P.id=A.artist"
        . " LEFT JOIN perinfo as B on B.id=I.bidder"
    . " WHERE I.conid=$conid"
        . " ORDER BY S.art_key, I.item_key;";

$items=array();

    $artR = dbQuery($artQ);
    while($artItem = fetch_safe_assoc($artR)) {
        array_push($items, $artItem);
    }

$artistQ = "SELECT S.id, S.artid, S.art_key"
        . ", concat_ws(' ', P.first_name, P.last_name) as art_name"
        . ", V.name as name"
    . " FROM artshow as S"
        . " JOIN artist as A ON A.id=S.artid"
        . " JOIN perinfo as P ON P.id=A.artist"
        . " JOIN vendors as V ON V.id=A.vendor"
    . " WHERE S.conid=$conid"
        . " ORDER BY name;";
$artistR = dbQuery($artistQ);

$artists = array();
while($artist = fetch_safe_assoc($artistR)) {
  array_push($artists, $artist);
}


$response['art'] = $items;
$response['artists'] = $artists;


ajaxSuccess($response);
?>
