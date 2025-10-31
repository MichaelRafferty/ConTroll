<?php
// library AJAX Processor: controll: pos_findRecord.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve perinfo, reg, note records for the Find and Add tabs

require_once '../lib/base.php';
require_once '../../lib/posFindRecord.php';

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

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'findRecord') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$response = posFindRecord();
ajaxSuccess($response);
