<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

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
$badgeid=sql_safe($_POST['badgeId']);

$response['iden'] = $_POST['iden'];

$memListQuery = "SELECT id, price, label FROM memList WHERE ";
if(isset($_POST['memId'])) {
  $memListQuery .= "id='" . sql_safe($_POST['memId']) . "' AND ";
}
if(isset($_POST['category'])) {
  $memListQuery .= "memCategory='" . sql_safe($_POST['category']) . "' AND ";
}
if(isset($_POST['type'])) {
  $memListQuery .= "memType='" . sql_safe($_POST['type']) . "' AND ";
}
if(isset($_POST['age'])) {
  $memListQuery .= "memAge='" . sql_safe($_POST['age']) . "' AND ";
}
$memListQuery .= "conid=$conid ORDER by price DESC";
$memInfo = fetch_safe_assoc(dbQuery($memListQuery));

$updateQ = "UPDATE reg SET memId=" . $memInfo['id'] 
    . ", price=price+" . $memInfo['price']
    . " WHERE id=$badgeid;";
dbQuery($updateQ);

$query = "SELECT R.id, R.price, R.paid, (R.price-R.paid) as cost, M.id as memId, M.memCategory, M.memType, M.memAge, M.label, R.locked FROM reg as R, memList as M WHERE M.id=R.memId AND R.id=$badgeid;";

$badgeInfo=fetch_safe_assoc(dbQuery($query));

$response['badgeInfo'] = $badgeInfo;

ajaxSuccess($response);
?>
