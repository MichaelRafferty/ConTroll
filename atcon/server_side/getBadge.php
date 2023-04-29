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

$perid = $_POST['perid'];
$response['id'] = $perid;
$con = get_conf('con');
$conid=$con['id'];

$query = <<<EOS
SELECT R.id, R.perid, R.conid, R.price, R.paid, (R.price-R.paid) as cost, concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type, M.memAge as age, R.locked, M.label
FROM reg R
JOIN memList M ON (M.id=R.memId)
WHERE R.perid=? AND R.conid=?
EOS;

$datatypes = 'ii';
$values = array($perid, $conid);

if(isset($_POST['badgeId'])) {
    $query .= " AND R.id=?";
    $datatypes .= 's';
    $values[] = $_POST['badgeId'];
}

$query .= " ORDER BY R.locked;";
$badgeInfoRes=dbSafeQuery($query, $datatypes, $values);
$badgeInfo=null;
if(isset($badgeInfoRes)) { $badgeInfo=fetch_safe_assoc($badgeInfoRes); }
$response["badgeInfo"]=$badgeInfo;

$badge_resQ = <<<EOS
SELECT concat_ws('-', id, memCategory, memType, memAge) as type, price, label
FROM memList
WHERE conid=? and atcon='Y' and current_timestamp() < enddate and current_timestamp() >= startdate
ORDER BY sort_order, memType, memAge ASC;
EOS;

$badge_res=dbSafeQuery($badge_resQ, 'i', array( $con['id']));
$badges=array();
while($row = fetch_safe_assoc($badge_res)) {
    $badges[count($badges)] = $row;
}

$response['badgeTypes']=$badges;

ajaxSuccess($response);
?>
