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
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];
$transid=sql_safe($_POST['transaction']);
$badgeid=sql_safe($_POST['badgeId']);

$response['iden'] = $_POST['iden'];

$types = '';
$values = array();

$memListQuery = "SELECT id, price, label FROM memList WHERE ";
if(isset($_POST['memId'])) {
  $memListQuery .= "id=? AND ";
  $types .= 'i';
  $values[] = $_POST['memId'];
}

if(isset($_POST['category'])) {
  $memListQuery .= "memCategory=? AND ";
  $types .= 's';
  $values[] = $_POST['category'];
}

if(isset($_POST['type'])) {
  $memListQuery .= "memType=? AND ";
  $types .= "s";
  $values[] = $_POST['type'];
}

if(isset($_POST['age'])) {
  $memListQuery .= "memAge=? AND ";
  $types .= 's';
  $values[] = $_POST['age'];
}

$memListQuery .= "conid=? ORDER by price DESC";
$types .= 'i';
$values[] = $conid;
$memInfo = fetch_safe_assoc(dbSafeQuery($memListQuery, $types, $values));

$updateQ = "UPDATE reg SET memId=?,  price=? WHERE id=?;";
dbSafeCmd($updateQ), 'idi', array($memInfo['id'],  $memInfo['price'],$badgeid));

$query = <<<EOS
SELECT R.id, R.price, R.paid, (R.price-R.paid) as cost, M.id as memId, M.memCategory, M.memType, M.memAge, A.label, R.locked
FROM reg R
JOIN memList M ON (M.id = R.memId)
JOIN ageList A ON (M.conid = A.conid AND M.memAge = A.ageType)
WHERE M.id=R.memId AND R.id=?;
EOS;

$badgeInfo=fetch_safe_assoc(dbSafeQuery($query, 'i', array($badgeid)));

$response['badgeInfo'] = $badgeInfo;

ajaxSuccess($response);
?>
