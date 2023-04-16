<?php

// library AJAX Processor: regpos_updatePerinfoNote.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve update open notes field in perinfo record

require_once('../lib/base.php');

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

// at present ony a manager can update a perinfo note
if (!check_atcon('manager', $conid)) {
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

$user_id = $_POST['user_id'];
if ($user_id != $_SESSION['user']) {
    ajaxError('Invalid credentials passed');
}
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
