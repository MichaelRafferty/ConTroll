<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "registration";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || (!checkAuth($check_auth['sub'], $perm) &&
                            !checkAuth($checK_auth['sub'], 'atcon'))) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$user = $check_auth['email'];
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
    $attachQ = <<<EOQ
INSERT IGNORE INTO atcon_badge(atconId, badgeId, action) 
VALUES (?, ?, 'attach');
EOQ;
    $rowid = dbSafeInsert($attachQ, 'ii', array($atconid, $badgeId));

    $response['atconid'] = $atconid;
}

$actionQ = <<<EOQ
SELECT * 
FROM atcon_badge
WHERE badgeId=? AND action !='attach';
EOQ;

$actionR = dbSafeQuery($actionQ, 'i', array($badgeId));

$actions = array();
while($action = fetch_safe_assoc($actionR)) {
    array_push($actions, $action);
}
$response['actions'] = $actions;

ajaxSuccess($response);
?>
