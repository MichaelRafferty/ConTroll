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


$conf = get_conf('con');
if(!isset($_GET['perid'])) { ajaxError("No Data"); }

$perid = $_GET['perid'];
$user = $check_auth['email'];
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];

$con = get_con();
$conid = $con['id'];

$response['con'] = $con['name'];
$response['id'] = $perid;

// do not allow duplicate entries in badgeList
$linkQ = <<<EOS
INSERT IGNORE INTO badgeList (perid, conid, userid)
SELECT perid, conid, userid FROM (
    SELECT ? AS perid, ? AS conid, ? AS userid) AS tmp
    WHERE NOT EXISTS (
        SELECT perid FROM badgeList 
        WHERE perid=? AND conid =? AND userid = ?
) LIMIT 1;
EOS;

$linID = dbSafeInsert($linkQ, 'iiiiii', array($perid, $conid, $userid, $perid, $conid, $userid));
$response['link']=$linID;

$perQ = "SELECT id, CONCAT_WS(' ', first_name, middle_name, last_name, suffix) as name, badge_name from perinfo where id=?;";
$perR = dbSafeQuery($perQ, 'i', array($perid));
$response['per'] = fetch_safe_assoc($perR);

$badgeQ = <<<EOS
SELECT R.id, R.memId, M.label 
FROM reg  R,
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
