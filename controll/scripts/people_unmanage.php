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

if (!(array_key_exists('action', $_POST)) && array_key_exists('who', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$action = $_POST['action'];
$who = $_POST['who'];

if ($action != 'unmanage') {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

if (substr($who, 0, 1) == 'n') {
    $table = 'newperson';
    $id = substr($who, 1);
} else {
    $table = 'perinfo';
    $id = $who;
}

if (!is_numeric($id)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
}
$response['who'] = $who;
$response['id'] = $id;
$response['table'] = $table;
$updatedBy = $_SESSION['user_perid'];

$uQ = <<<EOS
UPDATE $table
SET managedByNew = NULL, managedBy = NULL, updatedBy = ?
WHERE id = ?;
EOS;

$num_upd = dbSafeCmd($uQ, 'ii', array($updatedBy, $id));
if ($num_upd === false) {
    $response['success'] = "<br/>Error trying to update the $table record $id to clear the manager";
} else if ($num_upd != 1) {
    $response['success'] = "<br/>Error record $id not found when trying to clear the manager";
} else {
    $response['success'] = "<br/>Manager cleared for $id";
}

ajaxSuccess($response);
