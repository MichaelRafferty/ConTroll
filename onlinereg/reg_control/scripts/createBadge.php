<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "badge";

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
$transid=sql_safe($_POST['transaction']);

$response['iden'] = $_POST['iden'];

$memListQuery = <<<EOS
SELECT id, price, label
FROM memLabel
WHERE  
EOS;

$types = '';
$values=array();

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
  $types .= 's';
  $values[] = $_POST['type'];
}
if(isset($_POST['age'])) {
  $memListQuery .= "memAge=? AND ";
  $types .= 's';
  $values[] = $_POST['age'];
}
$memListQuery .= "conid=? ORDER by price DESC;";
$types .= 'i';
$values[] = $conid;
$memInfo = fetch_safe_assoc(dbSafeQuery($memListQuery, $types, $values));

$query = "INSERT INTO reg (conid, create_user, create_trans, perid, newperid, memId, price, paid, locked) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?);";
$types = 'iiiiiids';
$values = array($conid, $user_perid, $transid);
if(isset($_POST['id'])) {
    $values[] = $_POST['id'];
} else {
    $values[] = null;
}

if(isset($_POST['newid'])) {
    $values[] = $_POST['newid'];
} else {
    $values[] = null;
}

if(isset($memInfo)) {
    $values[] = $memInfo['id'];
    $values[] = $memInfo['price'] ;
} else {
    ajaxSuccess(array("error"=>"Invalid MembershipType")); exit();
}
$values[] = 'N';

$response['badgeQuery'] = $query;

$badgeid = dbSafeInsert($query, $types, $values);

$query = <<<EOS
SELECT R.id, R.price, R.paid, (R.price-R.paid) as cost, M.id as memId, M.memCategory, M.memType, M.memAge, M.label, R.locked
FROM reg R
JOIN memLabel M ON (R.memId = M.id)
WHERE R.id=?;
EOS;

$badgeInfo=fetch_safe_assoc(dbSafeQuery($query, 'i', array($badgeid)));

$response['badgeInfo'] = $badgeInfo;

ajaxSuccess($response);
?>
