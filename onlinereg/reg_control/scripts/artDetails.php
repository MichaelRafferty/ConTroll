<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);
$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');



if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != "GET") { ajaxError("No Data"); }
if(!isset($_GET['artid'])) { ajaxError("No Data"); }

$artid=sql_safe($_GET['artid']);
$artQ="SELECT id, art_key, a_panels, p_panels, a_tables, p_tables, a_panel_list, a_table_list, p_panel_list, p_table_list, total, chknum, chkdate, description FROM artshow WHERE conid='".$con['id']."' and artid=$artid;";
$artR = dbQuery($artQ);


if($artR) {
    $response['details']=fetch_safe_assoc($artR);
    if($response['details']==null) { $response['inShow']='no'; }
    else { $response['inShow']='yes'; }
    $itemQ = "SELECT count(I.id) as c FROM artshow as S JOIN artItems as I ON I.artshow=S.id WHERE S.conid=$conid and S.artid='$artid';";
    $itemR = dbQuery($itemQ);
    $itemcount = fetch_safe_assoc($itemR);
    $response['itemcount']=$itemcount['c'];
} else {
    $response['inShow']='no';
}

ajaxSuccess($response);
?>
