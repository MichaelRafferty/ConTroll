<?php
require_once "lib/base.php";

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

$user = 'regadmin@bsfs.org'; // note this is the current hardcoded email in the user table.  Should we use something more universal?
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];
$transid=$_POST['transaction'];
$badgeid=$_POST['badgeId'];

$response['iden'] = $_POST['iden'];
$datatypes = '';
$values = array();

$memListQuery = "SELECT id, price, label FROM memList WHERE ";
if (isset($_POST['memId'])) {
    $memListQuery .= "id=? AND ";
    $datatypes .= 'i';
    $values[] = $_POST['memId'];
}
if (isset($_POST['category'])) {
    $memListQuery .= "memCategory=? AND ";
    $datatypes .= 's';
    $values[] = $_POST['category'];
}
if (isset($_POST['type'])) {
    $memListQuery .= "memType=? AND ";
    $datatypes .= 's';
    $values[] = $_POST['type'];
}
if (isset($_POST['age'])) {
    $memListQuery .= "memAge=? AND ";
    $datatypes .= 's';
    $values[] = $_POST['age'];
}
$memListQuery .= "conid=? ORDER by price DESC";
$datatypes .= 'i';
$values[] = $conid;
$memInfo = fetch_safe_assoc(dbSafeQuery($memListQuery, $datatypes, $values));

$updateQ = <<<EOS
UPDATE reg SET memId=?, price=price+?
WHERE id=?;
EOS;

$rows = dbSafeCmd($updateQ, 'idi', array( $memInfo['id'], $memInfo['price'], $badgeid));
$query = <<<EOS
SELECT R.id, R.price, R.paid, (R.price-R.paid) as cost, M.id as memId, M.memCategory, M.memType, M.memAge, M.label, R.locked
FROM reg  R
JOIN memList M ON (R.memId = M.id)
WHERE R.id=?;
EOS;

$badgeInfo=fetch_safe_assoc(dbSafeQuery($query, 'i', array($badgeid)));
$response['badgeInfo'] = $badgeInfo;

ajaxSuccess($response);
?>
