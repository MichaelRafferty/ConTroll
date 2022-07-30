<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

$check_auth = google_init("ajax");
$perm = "badge";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$perid = $_GET['perid'];
$response['id'] = $perid;
$con = get_conf('con');
$conid=$con['id'];


$query = <<<EOS
SELECT R.id, R.price, R.paid, (R.price-R.paid) AS cost, CONCAT_WS('-', M.id, M.memCategory, M.memType, M.memAge) AS type, M.memAge AS age, R.locked, AMlabel
FROM reg R
JOIN memLabel M ON(M.id = R.memId)
WHERE R.perid=? AND R.conid BETWEEN ? AND ?
EOS;

$types = 'iii';
$values = array($perid, $conid, $conid+1);

if(isset($_GET['badgeId'])) {
    $query .= " AND R.id=?";
    $types .= 'i';
    $values[] = $_GET['badgeId'];
}

$query .= " ORDER BY R.locked;";
$badgeInfoRes=dbSafeQuery($query, $types, $values);
$badgeInfo=null;
if(isset($badgeInfoRes)) { $badgeInfo=fetch_safe_assoc($badgeInfoRes); }
$response["badgeInfo"]=$badgeInfo;

$badge_resQ= <<<EOS
SELECT CONCAT_WS('-', M.id, memCategory, memType, memAge) AS type, price, M.label
FROM memLabel M
WHERE M.conid=?
ORDER BY sort_order, memType, memAge ASC;
EOS;

$badge_res=dbSafeQuery($badge_resQ, 'i', array($con['id']);
$badges=array();
while($row = fetch_safe_assoc($badge_res)) {
    $badges[count($badges)] = $row;
}

$response['badgeTypes']=$badges;

ajaxSuccess($response);
?>
