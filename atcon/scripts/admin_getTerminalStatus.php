<?php
// library AJAX Processor: admin_deleteTerminsl.php
// Balticon Registration System
// Author: Syd Weinstein
// delete and re-get the list of terminals

require_once('../lib/base.php');
require_once('../../lib/term__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'manager';
$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'refreshStatus') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if (!array_key_exists('terminal', $_POST)) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$terminal = $_POST['terminal'];
load_term_procs();
// get the status from the terminal
$response = term_getStatus($terminal);
$response['message'] = "$terminal status updated, " . $response['updCnt'] . " row(s) updated";
ajaxSuccess($response);
