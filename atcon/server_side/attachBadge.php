<?php
require_once "lib/base.php";

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

$user = 'regadmin@bsfs.org'; // hardcoded, do we need a different common hardcode?
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];

$transid = $_POST['transid'];
$badgeId = $_POST['id'];

$atconQ = "SELECT id from atcon where transid=?;";
$atconR = dbSafeQuery($atconQ, 'i', array($transid));
if($atconR->num_rows > 0) {
    $atcon=fetch_safe_assoc($atconR);
    $atconid = $atcon['id'];
    $attachQ = "INSERT IGNORE INTO atcon_badge (atconId, badgeId, action) VALUES (?, ?, 'attach');";
    dbSafeInsert($attachQ, 'ii', array($atconid, $badgeId));

    $response['atconid'] = $atconid;
}

$actionQ = "SELECT * from atcon_badge WHERE badgeId=? AND action !='attach';";
$actionR = dbSafeQuery($actionQ, 'i', array($badgeId));

$actions = array();
while($action = fetch_safe_assoc($actionR)) {
    array_push($actions, $action);
}
$response['actions'] = $actions;

ajaxSuccess($response);
?>
