<?php
// library AJAX Processor: pos_findRecord.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve perinfo and reg records for the Find and Add tabs

require_once '../lib/base.php';
require_once '../../lib/posFindRecord.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$ajax_request_action = '';

if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'findRecord') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$conid = getConfValue('con','id');
if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$response = posFindRecord();
ajaxSuccess($response);
