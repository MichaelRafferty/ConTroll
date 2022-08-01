<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);
$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');



if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != "GET") { ajaxError("No Data"); }
if(!isset($_GET['artid'])) { ajaxError("No Data"); }

$artid=sql_safe($_GET['artid']);
$artQ="SELECT conid, total FROM artshow WHERE artid=$artid;";
$artR = dbQuery($artQ);

$history=array();
while ($res = fetch_safe_assoc($artR)) {
  array_push($history, $res);
}

$response['history'] = $history;

ajaxSuccess($response);
?>
