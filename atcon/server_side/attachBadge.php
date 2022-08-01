<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$perm="data_entry";
$con = get_con();
$conid=$con['id'];
$response = array("post" => $_POST, "get" => $_GET);
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$user = 'regadmin@bsfs.org';
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email='$user';";
$userR = fetch_safe_assoc(dbQuery($userQ));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];

$transid = sql_safe($_POST['transid']);
$badgeId = sql_safe($_POST['id']);

$atconQ = "SELECT id from atcon where transid=$transid;";
$atconR = dbQuery($atconQ);
if($atconR->num_rows > 0) {
    $atcon=fetch_safe_assoc($atconR);
    $atconid = $atcon['id'];
    $attachQ = "INSERT IGNORE INTO atcon_badge (atconId, badgeId, action)"
        . " VALUES ($atconid, $badgeId, 'attach');";
    dbInsert($attachQ);

    $response['atconid'] = $atconid;
}

$actionQ = "SELECT * from atcon_badge WHERE badgeId=$badgeId" 
    . " AND action !='attach'";
$actionR = dbQuery($actionQ);

$actions = array();
while($action = fetch_safe_assoc($actionR)) {
    array_push($actions, $action);
}
$response['actions'] = $actions;

ajaxSuccess($response);
?>
