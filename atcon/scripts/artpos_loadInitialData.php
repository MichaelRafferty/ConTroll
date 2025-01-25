<?php

// library AJAX Processor: artpos_loadInitialData.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve load the mapping tables and session information into the javascript side

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$atcon = get_conf('atcon');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'loadInitialData') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// loadInitialData:
// Load all the mapping tables for the POS function

$response['label'] = $con['label'];
$response['conid'] = $conid;
$response['discount'] = $atcon['discount'];
$response['badgePrinter'] = getSessionVar('badgePrinter')['name'] != 'None';
$response['receiptPrinter'] = getSessionVar('receiptPrinter')['name'] != 'None';
$response['user_id'] = getSessionVar('user');
$response['hasManager'] = check_atcon('manager', $conid);

ajaxSuccess($response);
