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
$perm = "badge";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$perid = $_GET['perid'];
$response['id'] = $perid;
$con = get_conf('con');
$conid=$con['id'];


$query = "SELECT R.id, R.price, R.paid, (R.price-R.paid) as cost, concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type, M.memAge as age, R.locked, M.label FROM reg as R, memList as M WHERE M.id=R.memId AND R.perid=".sql_safe($perid)." AND R.conid>=$conid AND R.conid<=$conid+1";
if(isset($_GET['badgeId'])) {
    $query .= " AND R.id='" . sql_safe($_GET['badgeId']) . "'";
}

$query .= " ORDER BY R.locked;";
$badgeInfoRes=dbQuery($query);
$badgeInfo=null;
if(isset($badgeInfoRes)) { $badgeInfo=fetch_safe_assoc($badgeInfoRes); }
$response["badgeInfo"]=$badgeInfo;

$badge_resQ="SELECT concat_ws('-', id, memCategory, memType, memAge) as type, price, label FROM memList WHERE ";
$badge_resQ .= "conid=". $con['id'] . " ORDER BY sort_order, memType, memAge ASC;";

$badge_res=dbQuery($badge_resQ);
$badges=array();
while($row = fetch_safe_assoc($badge_res)) {
    $badges[count($badges)] = $row;
}

$response['badgeTypes']=$badges;

ajaxSuccess($response);
?>
