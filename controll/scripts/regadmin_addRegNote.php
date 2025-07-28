<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "reg_staff";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('user_perid', $_SESSION)) {
    $user_perid = $_SESSION['user_perid'];
} else {
    ajaxError('Invalid credentials passed');
    return;
}

if (!isset($_POST) || !isset($_POST['regid']) || !isset($_POST['note'])|| !isset($_POST['ajax_request_action'])
    || $_POST['ajax_request_action'] != 'addRegNote') {
    $response['error'] = "Invalid Parameters";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('source', $_POST)) {
    $message_error = 'Source Missing';
    RenderErrorAjax($message_error);
    exit();
}
$source = $_POST['source'];

$con = get_conf('con');
$conid = $con['id'];

$note = $_POST['note'];
$regId = $_POST['regid'];

    // insert a reg note for the successful action
$insQ = <<<EOS
INSERT INTO regActions(userid, source, regid, action, notes)
VALUES (?, ?, ?, ?, ?);
EOS;
$typestr = 'isiss';
$paramarray = array($user_perid, $source, $regId, 'notes', $note);
$new_history = dbSafeInsert($insQ, $typestr, $paramarray);
if ($new_history === false) {
    $response['error'] = 'error adding note';
    ajaxSuccess($response);
    exit();
}

$response['success'] = 'Note added successfully';
ajaxSuccess($response);
