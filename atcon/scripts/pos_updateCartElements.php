<?php
// library AJAX Processor: pos_updateCartElements.php
// ConTroll Registration System
// Author: Syd Weinstein
// Store the cart into the system using add/update/delete and create appropriate transaction records
// Used both by mail-in registration (controll/registration.php) and atcon (atcon/regpos.php)

require_once '../lib/base.php';
require_once('../../lib/policies.php');
require_once('../../lib/posUpdateCartElements.php');

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
if ($ajax_request_action != 'updateCartElements') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$user_id = $_POST['user_id'];
if ($user_id != getSessionVar('user')) {
    ajaxError('Invalid credentials passed');
}

$user_perid = $user_id;

if (!array_key_exists('source', $_POST)) {
    $message_error = 'Source Missing';
    RenderErrorAjax($message_error);
    exit();
}
$source = $_POST['source'];

posUpdateCartElements($conid, $user_perid, $source);
