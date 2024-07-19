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

$user_perid = $_SESSION['user_perid'];

$user = $check_auth['email'];
$response['user'] = $user;
$con = get_conf('con');
$conid=$con['id'];

$transid = $_POST['transid'];
$badgeId = $_POST['id'];

$attachQ = <<<EOQ
INSERT INTO reg_history(userid, tid, regid, action)
VALUES(?, ?, ?, 'attach');
EOQ;
$rowid = dbSafeInsert($attachQ, 'iii', array($user_perid, $transid, $badgeId));
// debug output only
$response['history_id'] = $rowid;
$actionQ = <<<EOQ
SELECT * 
FROM reg_history
WHERE regid=? AND action !='attach';
EOQ;
$actionR = dbSafeQuery($actionQ, 'i', array($badgeId));

$actions = array();
while($action = fetch_safe_assoc($actionR)) {
    array_push($actions, $action);
}
$response['actions'] = $actions;

ajaxSuccess($response);
?>
