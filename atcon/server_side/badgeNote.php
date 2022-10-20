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

$user = 'regadmin@bsfs.org'; // hardcoded - do we need a different hardcode for this?
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];

$atconIdQ = "SELECT id FROM atcon WHERE conid=? AND transid=?;";
$atconId = fetch_safe_assoc(dbSafeQuery($atconIdQ, 'ii', array($conid,$_POST['transid'])));

$attachQ = "INSERT IGNORE INTO atcon_badge (atconId, badgeId, action, comment)  VALUES (?, ?, ?, ?);";
$attachR = dbSafeInsert($attachQ, 'iiss', array($atconId['id'], $_POST['badgeId'], $_POST['type'], $user . ": " . $_POST['content']));

$atconQ = <<<EOS
SELECT B.date, A.atcon_key, B.action, B.comment
FROM atcon_badge B
JOIN atcon A ON (A.id=B.atconId)
WHERE badgeId=?;
EOS;
$atconR = dbSafeQuery($atconQ, 'i', array($_POST['badgeId']));
$actions = array();
if($atconR->num_rows > 0) {
    while($act = fetch_safe_assoc($atconR)) {
        array_push($actions, $act);
    }
}
$response['actions'] = $actions;

ajaxSuccess($response);
?>
