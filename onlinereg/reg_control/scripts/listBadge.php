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
$userQ = "SELECT id FROM user WHERE email='$user';";
$userR = fetch_safe_assoc(dbQuery($userQ));
$userid = $userR['id'];

$con = get_con();
$conid = $con['id'];

$response['con'] = $con['name'];
$response['id'] = $perid;

// do not allow duplicate entries in badgeList
$linkQ = "INSERT IGNORE INTO badgeList (perid, conid, userid)
SELECT perid, conid, userid FROM (
    SELECT '" . sql_safe($perid) . "' AS perid, '" . sql_safe($conid) . "' AS conid, '" . sql_safe($userid) . "' AS userid) AS tmp
    WHERE NOT EXISTS (
        SELECT perid FROM badgeList WHERE perid='" . sql_safe($perid) . "' AND conid ='" . sql_safe($conid) . "' AND userid = '" . sql_safe($userid) . "'
) LIMIT 1;";

$linID = dbInsert($linkQ);
$response['link']=$linID;

$perQ = "SELECT id, concat_ws(' ', first_name, middle_name, last_name, suffix) as name, badge_name from perinfo where id=". sql_safe($perid);
$perR = dbQuery($perQ);
$response['per'] = fetch_safe_assoc($perR);

$badgeQ = "SELECT R.id, R.memId, M.label FROM reg as R, memList as M WHERE M.id=R.memId and R.perid=".sql_safe($perid).";";

$badgeR = dbQuery($badgeQ);

$response['badge'] = fetch_safe_assoc($badgeR);
if($badgeR->num_rows>0) {
  $badgeId = $response['badge']['id'];
} else { $badgeId = ''; }

ajaxSuccess($response);
?>
