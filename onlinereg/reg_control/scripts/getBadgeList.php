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

if($_SERVER['REQUEST_METHOD'] != "GET") { ajaxError("No Data"); }

$user = $check_auth['email'];
$userQ = "SELECT id FROM user WHERE email='$user';";
$userR = fetch_safe_assoc(dbQuery($userQ));
$userid = $userR['id'];

$con = get_con();
$conid = $con['id'];

$response['con'] = $con['name'];
$response['id'] = $userid;

$entryQ_fields = "SELECT concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name, P.badge_name, R.id as regid, R.staff, R.memId, M.label, B.id, P.id as perid ";
$entryQ_tables = "FROM badgeList as B " .
  "LEFT JOIN perinfo as P on P.id=B.perid ".
  "LEFT JOIN reg as R on R.perid=P.id AND R.conid=B.conid ".
  "LEFT JOIN memList as M on M.id=R.memId ";
$entryQ_where = "WHERE B.conid='".$con['id']."' AND B.userid=$userid ";
$entryQ_order = "ORDER BY B.id;";

$entryQ = $entryQ_fields . $entryQ_tables . $entryQ_where . $entryQ_order;

$response['query']=$entryQ;
$response['badges']=array();

$entryR = dbQuery($entryQ);
while($badge = fetch_safe_assoc($entryR)) {
  array_push($response['badges'], $badge);
}

ajaxSuccess($response);
?>
