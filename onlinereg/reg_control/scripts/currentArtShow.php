<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conf = get_conf('con');
$artistQ = "SELECT S.perid, S.artid, S.art_key as art_key, V.name as trade"
    . ", concat_ws(' ', P.first_name, P.last_name, P.suffix) as name"
    . ", S.agent_request as new_agent, S.description as description"
    . ", V.website as website, V.description as vendor_description"
    . ", concat_ws(' ', G.first_name, G.middle_name, G.last_name) as old_agent"
    . " FROM artshow as S"
    . " JOIN artist as A on A.id = S.artid"
    . " JOIN perinfo as P on P.id= S.perid"
    . " JOIN vendors as V on V.id=A.vendor"
    . " LEFT JOIN perinfo as G on G.id = S.agent"
    . " WHERE S.conid=".$con['id']." ORDER BY art_key;";

$artistR = dbQuery($artistQ);

$artistlist = array();
while($artist = fetch_safe_assoc($artistR)) {
  array_push($artistlist, $artist);
}

$response['query']=$artistQ;
$response['artistList']=$artistlist;

ajaxSuccess($response);
?>
