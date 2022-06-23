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


if($check_auth == false || (!checkAuth($check_auth['sub'], $perm) &&
                            !checkAuth($check_auth['sub'], 'atcon'))) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$user = $check_auth['email'];
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

$query = "SELECT R.id, R.price, R.paid, (R.price-R.paid) as cost, M.id as memId, M.memCategory, M.memType, M.memAge, M.label, R.locked FROM reg as R, memList as M WHERE M.id=R.memId AND R.id=$badgeid;";

$badgeInfo=fetch_safe_assoc(dbQuery($query));

$response['badgeInfo'] = $badgeInfo;

ajaxSuccess($response);
?>
