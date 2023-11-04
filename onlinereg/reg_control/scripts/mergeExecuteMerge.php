<?php
global $db_ini;

require_once "../lib/base.php";
require_once('../../../lib/log.php');

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('remain', $_POST) && array_key_exists('merge',$_POST))) {
    $response['error'] = 'Invalid Calling Sequence';
    ajaxSuccess($response);
    exit();
}

$remain = $_POST['remain'];
$merge = $_POST['merge'];
$user = $check_auth['email'];
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email=?;";
$userR = dbSafeQuery($userQ, 's', array($user))->fetch_assoc();
$userid = $userR['id'];

$mergeSQL = <<<EOS
    CALL mergePerid($userid, $merge, $remain, @status, @rollback); select @status AS status, @rollback AS rollback;
EOS;

$mqr = dbMultiQuery($mergeSQL);
$result = dbNextResult();
$row = $result->fetch_assoc();
$status = $row['status'];
$rollback = $row['rollback'];

$log = get_conf('log');
logInit($log['db']);
logWrite(array('type' => 'merge', 'merge' => $merge, 'remain' => $remain, 'status' => $status, 'rollback' => $rollback));

$response = [];
$response['success'] = 'merged';
$response['status'] = $status;
$response['rollback'] = $rollback;

ajaxSuccess($response);
?>
