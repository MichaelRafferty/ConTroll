<?php
global $db_ini;

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = "people";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('action', $_POST) && array_key_exists('who', $_POST) && array_key_exists('manager', $_POST))) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$action = $_POST['action'];
$who = $_POST['who'];
$manager = $_POST['manager'];

if ($action != 'manage' || $who == null || $who == '' || !is_numeric($who) ||
    $manager == null || $manager == '' || !is_numeric($manager)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

if ($who == $manager) {
    $response['error'] = 'You cannot manage yourself';
    ajaxSuccess($response);
    exit();
}

$response['who'] = $who;
$response['manager'] = $manager;
$updatedBy = $_SESSION['user_perid'];

$uQ = <<<EOS
UPDATE perinfo
SET managedByNew = NULL, managedBy = ?, updatedBy = ?, managedReason = 'people assign'
WHERE id = ?;
EOS;

$num_upd = dbSafeCmd($uQ, 'iii', array($manager, $updatedBy, $who));
if ($num_upd === false) {
    $response['success'] = "<br/>Error trying to update the perinfo record $who to set the manager";
} else if ($num_upd != 1) {
    $response['success'] = "<br/>Error record $who not found when trying to set the manager";
} else {
    $response['success'] = "<br/>Manager set to $manager for $who";
}

ajaxSuccess($response);
