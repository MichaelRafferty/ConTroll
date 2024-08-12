<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "registration";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || (!checkAuth($check_auth['sub'], $perm) &&
                            !checkAuth($check_auth['sub'], 'atcon'))) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$user = $check_auth['email'];
$response['user'] = $user;
$userQ = "SELECT id, perid FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];
$user_perid = $userR['perid'];
$con = get_conf('con');
$conid=$con['id'];

$attachQ = "INSERT IGNORE INTO regActions(userid, tid, regid, action, notes)
VALUES (?, ?, ?, ?, ?);";
$attachR = dbSafeInsert($attachQ, 'iiiss', array($user_perid, $_POST['transid'], $_POST['badgeId'], 'notes', $user . ": " . $_POST['content']));

$atconQ = <<<EOS
SELECT logdate, action, notes
FROM regActions
WHERE regid=? AND action != 'attach';
EOS;
$atconR = dbSafeQuery($atconQ, 'i', array($_POST['badgeId']));
$actions = array();
if($atconR->num_rows > 0) while($act = fetch_safe_assoc($atconR)) {
    array_push($actions, $act);
}
$response['actions'] = $actions;

ajaxSuccess($response);
?>
