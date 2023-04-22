<?php
require_once "../lib/base.php";
#require_once "../lib/ajax_functions.php";

$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(!check_atcon('artshow', $conid)) {
    $message_error = 'No Permission.';
    RenderErrorAjax($message_error);
    exit();
}

if(!isset($_GET) || !isset($_GET['perid'])) { 
    $response['error'] = "Need Perid";
    ajaxSuccess($response);
    exit();
}

$perid = $_GET['perid'];

$userQ = "SELECT id, concat_ws(' ', first_name, last_name, suffix) as name, badge_name as badge FROM perinfo WHERE id=?;";
$userR = dbSafeQuery($userQ, 'i', array($perid));
$user = fetch_safe_assoc($userR);
$response['id']= $user['id'];
$response['name'] = $user['name'];
$response['badge'] = $user['badge'];

ajaxSuccess($response);
?>
