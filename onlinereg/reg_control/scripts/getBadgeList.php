<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "badge";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != "GET") { ajaxError("No Data"); }

$user = $check_auth['email'];
$userQ = "SELECT id, perid FROM user WHERE email=?;";
$userR = dbSafeQuery($userQ, 's', array($user))->fetch_assoc();
$userid = $userR['id'];
$user_perid = $userR['perid'];

$con = get_con();
$conid = $con['id'];

$response['con'] = $con['name'];
$response['id'] = $userid;
$response['user_perid'] = $user_perid;

$entryQ = <<<EOS
SELECT CONCAT_WS(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name, P.badge_name, R.id as regid, R.memId, M.label, B.id, P.id as perid
FROM badgeList B
LEFT OUTER JOIN perinfo P ON (P.id=B.perid)
LEFT OUTER JOIN reg R ON (R.perid=P.id AND R.conid=B.conid)
LEFT OUTER JOIN memList M ON (M.id=R.memId)
WHERE B.conid=? AND B.user_perid=?
ORDER BY B.id;
EOS;

$response['query']=$entryQ;
$response['badges']=array();

$entryR = dbSafeQuery($entryQ, 'ii', array($con['id'], $user_perid));
while($badge = $entryR->fetch_assoc()) {
  array_push($response['badges'], $badge);
}

ajaxSuccess($response);
?>
