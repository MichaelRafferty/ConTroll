<?php

// library AJAX Processor: pos_updatePrintcount.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve perinfo and reg records for the Find and Add tabs

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
if ($ajax_request_action != 'updatePrintcount') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}


if (!array_key_exists('source', $_POST)) {
    $message_error = 'Source Missing';
    RenderErrorAjax($message_error);
    exit();
}
$source = $_POST['source'];
// updatePrintcount
//      passed array of regid and print count
//      updates database

if (array_key_exists('regs', $_POST)) {
    $regs = $_POST['regs'];
    $user_id = $_POST['user_id'];
    if ($user_id != getSessionVar('user')) {
        ajaxError('Invalid credentials passed');
    }
    $tid = $_POST['tid'];
    // mark transaction complete
    $updCompleteSQL = <<<EOS
UPDATE transaction
SET complete_date = NOW()
WHERE id = ?;
EOS;
    $completed = dbSafeCmd($updCompleteSQL, 'i', array($tid));

    $insertSQL = <<<EOS
INSERT INTO regActions(userid, source, tid, regid, action)
VALUES (?,?,?,?,'print');
EOS;
    $typestr = 'isii';

    foreach ($regs as $reg) {
        $paramarray = array($user_id, $source, $tid, $reg['regid']);
        dbSafeInsert($insertSQL, $typestr,$paramarray);
    }
}
