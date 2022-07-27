<?php
global $db_ini;


require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

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
$userQ = "SELECT id FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', $user));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];

$atconIdQ = "SELECT id FROM atcon WHERE conid=? AND transid=?;";
$atconId = fetch_safe_assoc(dbSafeQuery($atconIdQ, 'ii', array($conid, $_POST['transid']));

$attachQ = "INSERT IGNORE INTO atcon_badge (atconId, badgeId, action, comment)  VALUES (?, ?, ?, ?);";
$attachR = dbSafeInsert($attachQ, 'iiss', array($atconId['id'], $_POST['badgeId'], $_POST['type'], $user . ": " . $_POST['content']));

$atconQ = "SELECT B.date, A.atcon_key, B.action, B.comment FROM atcon_badge as B, atcon as A WHERE A.id=B.atconId AND badgeId=? AND action != 'attach';";
$atconR = dbSafeQuery($atconQ, 'i', $_POST['badgeId']);
$actions = array();
if($atconR->num_rows > 0) while($act = fetch_safe_assoc($atconR)) {
    array_push($actions, $act);
}
$response['actions'] = $actions;

ajaxSuccess($response);
?>
