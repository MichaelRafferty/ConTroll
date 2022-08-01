<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$response = array("post" => $_POST, "get" => $_GET);

$perm="data_entry";
$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}


$perid = $_POST['perid'];
$response['id'] = $perid;
$con = get_conf('con');
$conid=$con['id'];


$query = "SELECT R.id, R.perid, R.conid, R.price, R.paid, (R.price-R.paid) as cost, concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type, M.memAge as age, R.locked, M.label FROM reg as R, memList as M WHERE M.id=R.memId AND R.perid=".sql_safe($perid)." AND R.conid=$conid";
if(isset($_POST['badgeId'])) {
    $query .= " AND R.id='" . sql_safe($_POST['badgeId']) . "'";
}

$query .= " ORDER BY R.locked;";
$badgeInfoRes=dbQuery($query);
$badgeInfo=null;
if(isset($badgeInfoRes)) { $badgeInfo=fetch_safe_assoc($badgeInfoRes); }
$response["badgeInfo"]=$badgeInfo;

$badge_resQ="SELECT concat_ws('-', id, memCategory, memType, memAge) as type, price, label FROM memList WHERE ";
$badge_resQ .= "conid=". $con['id'] . " and atcon='Y' and current_timestamp() < enddate and current_timestamp() > startdate"
    . " ORDER BY sort_order, memType, memAge ASC;";

$badge_res=dbQuery($badge_resQ);
$badges=array();
while($row = fetch_safe_assoc($badge_res)) {
    $badges[count($badges)] = $row;
}

$response['badgeTypes']=$badges;

ajaxSuccess($response);
?>
