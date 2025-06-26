<?php
global $db_ini;

require_once "../lib/base.php";
require_once('../../lib/log.php');

$check_auth = google_init("ajax");
$perm = "reg_staff";

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

if ($merge == $remain) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Merge cannot be the sane as Survive'));
    exit();
}

$mQ = <<<EOS
SELECT first_name, middle_name, last_name, email_addr
FROM perinfo
WHERE id IN (?,?);
EOS;
$mR = dbSafeQuery($mQ, 'ii', array($merge, $remain));
if ($mR === false) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Database error retrieving perinfo rows'));
    exit();
}
while ($mL = $mR->fetch_assoc()) {
    if (($mL['first_name'] == 'Merged' && $mL['middle_name'] == 'into') || str_starts_with($mL['email_addr'], 'merged into')) {
        ajaxSuccess(array('status'=>'error', 'error'=>'One of the candidiates is already a merged record, not allowed to merge a merged record'));
        exit();
    }
}
$mR->free();

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
