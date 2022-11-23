<?php
require_once "lib/base.php";

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


$user = 'regadmin@bsfs.org';  // this is hardcoded for now, should we use a different hardcode name?
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];
$transid=$_POST['transaction'];

$response['iden'] = $_POST['iden'];

$memListQuery = "SELECT id, price, label FROM memList WHERE ";
$datatypes = '';
$values = array();

if(isset($_POST['memId'])) {
  $memListQuery .= "id=? AND ";
  $datatypes .= 'i';
  $values[] = $_POST['memId'];
}
$memListQuery .= "conid=? ORDER by price DESC;";
$datatypes .= 'i';
$values[] = $conid;
$memInfo = fetch_safe_assoc(dbSafeQuery($memListQuery, $datatypes, $values));

if (!isset($memInfo)) {
    ajaxSuccess(array("error"=>"Invalid MembershipType"));
    exit();
}

if (isset($_POST['id'])) {
    $perid = $_POST['id'];
} else {
    $perid = null;
}

if ($perid !== null) {
    $regCheckR = dbSafeQuery("SELECT id FROM reg WHERE conid=? and perid=?;", 'ii', array($conid, $perid));
    if($regCheckR->num_rows > 0) {
        $response['error'] = "Duplicate Membership";
        ajaxSuccess($response);
        exit();
    }
}

$query = "INSERT INTO reg (conid, create_user, create_trans, perid, newperid, memId, price, locked) VALUES (?, ?, ?, ?, ?, ?, ?, 'N');";
$datatypes = 'iiiiiid';
if(isset($_POST['newid'])) {
    $newid = $_POST['newid'];
} else {
    $newid = null;
}

$values = array($conid, $userid, $transid, $perid, $newid, $memInfo['id'], $memInfo['price']);
$response['badgeQuery'] = $query;

$badgeid = dbSafeInsert($query, $datatypes, $values);

$atconR = dbSafeQuery("SELECT id FROM atcon WHERE transid=?;", 'i', array($transid));
$atconL = fetch_safe_array($atconR);
$atconid = $atconL[0];

$query = <<<EOS
SELECT R.id, R.price, R.paid, (R.price-R.paid) as cost, M.id as memId, M.memCategory, M.memType, M.memAge, M.label, R.locked
FROM reg R
JOIN memList M  ON (M.id=R.memId)
WHERE M.id=R.memId AND R.id=?;
EOS;

$createEventQ = "INSERT INTO atcon_badge (atconId, badgeId, action) VALUES(?, ?, 'create');";
dbSafeInsert($createEventQ, 'ii', array($atconid, $badgeid));

$badgeInfo=fetch_safe_assoc(dbSafeQuery($query, 'i', array($badgeid)));

$response['badgeInfo'] = $badgeInfo;

ajaxSuccess($response);
?>
