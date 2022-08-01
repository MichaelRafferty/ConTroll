<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}


if(!isset($_POST) || !isset($_POST['perid'])) { 
    $response['error'] = "Need Perid";
    ajaxSuccess($response);
    exit();
}

$perid = sql_safe($_POST['perid']);

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
