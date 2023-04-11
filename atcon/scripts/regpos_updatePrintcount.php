<?php

// library AJAX Processor: regpos_updatePrintcount.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve perinfo and reg records for the Find and Add tabs

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'cashier';
if ($_POST && array_key_exists('nopay', $_POST)) {
    if ($_POST['nopay'] == 'true') {
        $method = 'data_entry';
    }
}

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
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}
// updatePrintcount
//      passed array of regid and print count
//      updates database

if (array_key_exists('regs', $_POST)) {
    $regs = $_POST['regs'];
    $user_id = $_POST['user_id'];
    if ($user_id != $_SESSION['user']) {
        ajaxError('Invalid credentials passed');
    }
    $tid = $_POST['tid'];
    $insertSQL = <<<EOS
INSERT INTO atcon_history(userid, tid, regid, action)
VALUES (?,?,?,'print');
EOS;
    $typestr = 'iii';

    foreach ($regs as $reg) {
        $paramarray = array($user_id, $tid, $reg['regid']);
        dbSafeInsert($insertSQL, $typestr,$paramarray);
    }
}
