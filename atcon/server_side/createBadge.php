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


$user = 'regadmin@bsfs.org';
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email='$user';";
$userR = fetch_safe_assoc(dbQuery($userQ));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];
$transid=sql_safe($_POST['transaction']);

$response['iden'] = $_POST['iden'];

$memListQuery = "SELECT id, price, label FROM memList WHERE ";
if(isset($_POST['memId'])) {
  $memListQuery .= "id='" . sql_safe($_POST['memId']) . "' AND ";
}
$memListQuery .= "conid=$conid ORDER by price DESC";
$memInfo = fetch_safe_assoc(dbQuery($memListQuery));

$perid = sql_safe($_POST['id']);
$regCheckR = dbQuery("SELECT id FROM reg WHERE conid=$conid and perid=$perid;");
if($regCheckR->num_rows > 0) {
    $response['error'] = "Duplicate Membership";
    ajaxSuccess($response);
    exit();
}

$query = "INSERT INTO reg (conid, create_user, create_trans, perid, newperid, memId, price, locked) VALUES ($conid, $userid, $transid, ";
if(isset($_POST['id'])) { $query .= "'" . sql_safe($_POST['id']) . "', "; }
  else { $query .= "NULL, "; }
if(isset($_POST['newid'])) { $query .= "'" . sql_safe($_POST['newid']) . "', "; }
  else { $query .= "NULL, "; }
if(isset($memInfo)) {
  $query .= "'" . sql_safe($memInfo['id']) . "', '" .  $memInfo['price'] . "', ";
} else { ajaxSuccess(array("error"=>"Invalid MembershipType")); exit(); }
$query .= "'N');";

$response['badgeQuery'] = $query;

$badgeid = dbInsert($query);

$atconR = dbQuery("SELECT id FROM atcon WHERE transid=$transid");
$atconL = fetch_safe_array($atconR);
$atconid = $atconL[0];

$query = "SELECT R.id, R.price, R.paid, (R.price-R.paid) as cost, M.id as memId, M.memCategory, M.memType, M.memAge, M.label, R.locked FROM reg as R, memList as M WHERE M.id=R.memId AND R.id=$badgeid;";

$createEventQ = "INSERT INTO atcon_badge (atconId, badgeId, action) VALUES"
    . " ($atconid , $badgeid, 'create');";
dbInsert($createEventQ);

$badgeInfo=fetch_safe_assoc(dbQuery($query));

$response['badgeInfo'] = $badgeInfo;

ajaxSuccess($response);
?>
