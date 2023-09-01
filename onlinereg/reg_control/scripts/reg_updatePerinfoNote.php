<?php

// library AJAX Processor: reg_updatePerinfoNote.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve update open notes field in perinfo record

require_once '../lib/base.php';

$check_auth = google_init('ajax');
$perm = 'registration';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    RenderErrorAjax('Authentication Failed');
    exit();
}

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;


$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'updatePerinfoNote') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (array_key_exists('user_id', $_SESSION)) {
    $user_id = $_SESSION['user_id'];
} else {
    ajaxError('Invalid credentials passed');
    return;
}

// at present ony a manager can update a perinfo note
if (!checkAuth($check_auth['sub'], 'reg_admin')) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// updatePerinfoNote:
// update the open_notes for a specific perid
//  inputs:
//      notes: new note string
//      perid: which person to update
//      cart_perinfo_map: map of perid to rows in cart_perinfo
//  Outputs:
//      message/error/warn: appropriate diagnostics

$notes = $_POST['notes'];
if ($notes === '')
    $notes = null;
$perid = $_POST['perid'];

if ($perid <= 0) {
    ajaxError('Invalid person to update');
    return;
}
$response = [];

$updSQL = <<<EOS
UPDATE perinfo
SET open_notes = ?
WHERE id = ?;
EOS;
$num_upd = dbSafeCmd($updSQL, 'si', array($notes, $perid));
if ($num_upd === false) {
    $response['error'] = "Unable to update notes";
} else {
    $response['message'] ="Notes Updated";
}
ajaxSuccess($response);
