<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "art_sales";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET) || !isset($_GET['perid'])) {
    $response['error'] = "Need Perid";
    ajaxSuccess($response);
    exit();
}

$perid = sql_safe($_GET['perid']);

$userQ = "SELECT id, concat_ws(' ', first_name, last_name, suffix) as name"
    . ", badge_name as badge"
    . " FROM perinfo WHERE id=$perid";
$userR = dbQuery($userQ);
$user = fetch_safe_assoc($userR);
$response['id']= $user['id'];
$response['name'] = $user['name'];
$response['badge'] = $user['badge'];


ajaxSuccess($response);
?>
