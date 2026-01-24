<?php
// library AJAX Processor: pos_processPayment.php
// ConTroll Registration System
// Author: Syd Weinstein
// reprint a credit card receipt for a payment id

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/term__load_methods.php');

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
if ($ajax_request_action != 'printReceipt') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid) || check_atcon('artsales', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$user_id = $_POST['user_id'];
if ($user_id != getSessionVar('user')) {
    ajaxError('Invalid credentials passed');
}

load_term_procs();
$terminal = getSessionVar('terminal');
if (!is_array($terminal)) {
    ajaxError('Invalid Calling Sequence');
}
$name = $terminal['name'];

$user_perid = $user_id;
logInit(getConfValue('log', 'term'));


$paymentId = $_POST['paymentId'];
if ($paymentId == NULL || $paymentId == '') {
    ajaxError('Invalid Calling Sequence');
}

term_printReceipt($name, $paymentId, true);

$response['status'] = 'success';
$response['message'] = "Payment id $paymentId sent to terminal $name";
ajaxSuccess($response);
