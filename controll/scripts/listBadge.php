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


$conf = get_conf('con');
if(!isset($_GET['perid'])) { ajaxError("No Data"); }

$perid = $_GET['perid'];
$user = $check_auth['email'];
$response['user'] = $user;
$userQ = "SELECT id, perid FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];
$user_perid = $userR['perid'];

$con = get_con();
$conid = $con['id'];

$response['con'] = $con['name'];
$response['id'] = $perid;

// do not allow duplicate entries in badgeList
$linkQ = <<<EOS
INSERT IGNORE INTO badgeList (perid, conid, user_perid)
SELECT perid, conid, user_perid FROM (
    SELECT ? AS perid, ? AS conid, ? AS user_perid) AS tmp
    WHERE NOT EXISTS (
        SELECT perid FROM badgeList 
        WHERE perid=? AND conid =? AND user_perid = ?
) LIMIT 1;
EOS;

$linID = dbSafeInsert($linkQ, 'iiiiii', array($perid, $conid, $user_perid, $perid, $conid, $user_perid));
$response['link']=$linID;

$perQ = "SELECT id, CONCAT_WS(' ', first_name, middle_name, last_name, suffix) as name, badge_name from perinfo where id=?;";
$perR = dbSafeQuery($perQ, 'i', array($perid));
$response['per'] = fetch_safe_assoc($perR);

$badgeQ = <<<EOS
SELECT R.id, R.memId, M.label 
FROM reg  R
JOIN memList M ON (M.id=R.memId)
WHERE R.perid=?;
EOS;

$badgeR = dbSafeQuery($badgeQ, 'i', array($perid));

$response['badge'] = fetch_safe_assoc($badgeR);
if($badgeR->num_rows>0) {
  $badgeId = $response['badge']['id'];
} else { $badgeId = ''; }

ajaxSuccess($response);
?>
