<?php
// library AJAX Processor: pos_cancelPayment.php
// ConTroll Registration System
// Author: Syd Weinstein
// cancel an open order due to start over

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'registration';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    RenderErrorAjax('Authentication Failed');
    exit();
}

$con = get_conf('con');
$cc = get_conf('cc');
$conid = $con['id'];
$response['conid'] = $conid;
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'cancelOrder') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$log = get_conf('log');
logInit($log['reg']);

if (!array_key_exists('orderId', $_POST) || empty($_POST['orderId'])) {
    RenderErrorAjax('Invalid calling sequence.');
}

$orderId = $_POST['orderId'];

load_cc_procs();

 $locationId = getSessionVar('terminal');
 if ($locationId) {
     $locationId = $locationId['locationId'];
 } else {
     $locationId = $cc['location'];
 }

 cc_cancelOrder('atcon', $orderId, true, $locationId);
 ajaxSuccess(array ('status' => 'success', 'message' => "Order $orderId cancelled."));
