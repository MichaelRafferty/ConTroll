<?php
// library AJAX Processor: artpos_cancelPayment.php
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

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'cancelOrder') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$user_id = $_POST['user_id'];
if ($user_id != getSessionVar('user')) {
    ajaxError('Invalid credentials passed');
}

$user_perid = $user_id;

$log = get_conf('log');
$cc = get_conf('cc');
logInit($log['term']);

if (!array_key_exists('orderId', $_POST) || empty($_POST['orderId'])) {
    RenderErrorAjax('Invalid calling sequence.');
}

$orderId = $_POST['orderId'];

load_cc_procs();

$locationId = getSessionVar('terminal');
if ($locationId) {
    $locationId = $locationId['locationId'];
} else if (array_key_exists('location_artpos', $cc)) {
    $locationId = $cc['location_artpos'];
} else if (array_key_exists('location', $cc)) {
    $locationId = $cc['location'];
} else {
    $locationId = 'Unknown';
}

cc_cancelOrder('atcon', $orderId, true, $locationId);

$upT = <<<EOS
UPDATE transaction
SET orderId = NULL
WHERE orderId = ?;
EOS;

$rows_upd = dbSafeCmd($upT, 's', array($orderId));

 ajaxSuccess(array ('status' => 'success', 'message' => "Order $orderId cancelled."));
