<?php

// library AJAX Processor: base_showPrinterSelect.php
// Balticon Registration System
// Author: Syd Weinstein
// retrieve the select list for printers

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];

if (!check_atcon('any', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'printerSelectList') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$response['selectList'] = Draw_Printer_Select(4);
ajaxSuccess($response);
