<?php
// library AJAX Processor: admin_getTerminalCodes.php
// Balticon Registration System
// Author: Syd Weinsteinretrieve the details on a terminal after it's been paired

require_once('../lib/base.php');
require_once('../../lib/term__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'manager';
$con = get_conf('con');
$conid = $con['id'];
$log = get_conf('log');
logInit($log['term']);

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'codeStatus') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if (!(array_key_exists('terminal', $_POST) && array_key_exists('id', $_POST))) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$name = $_POST['terminal'];
$id = $_POST['id'];
// now call API to create the terminal
load_term_procs();
$terminal = term_getDevice($name, true);

$data = $terminal['device_code'];
$id = $data['id'];
$name = $data['name'];
$code = $data['code'];
$product_type = $data['product_type'];
$locationId = $data['location_id'];
$created_at = $data['created_at'];
if (array_key_exists('pair_by', $data)) {
    $pair_by = $data['pair_by'];
} else {
    $pair_by = null;
}
if (array_key_exists('paired_at', $data)) {
    $paired_at = $data['paired_at'];
} else {
    $paired_at = null;
}
if (array_key_exists('device_id', $data)) {
    $device_id = $data['device_id'];
} else {
    $device_id = null;
}
$status = $data['status'];
$status_changed_at = $data['status_changed_at'];

$updQ = <<<EOS
UPDATE terminals
SET deviceId = ?, pairBy = ?, pairedAt = ?, status = ?, statusChanged = ?
WHERE name = ?;
EOS;
$dt = 'ssssss';
$numRows = dbSafeUpdate($updQ, $dt, array($device_id, $pair_by, $paired_at,  $status, $status_changed_at, $name));
if ($numRows === false) {
    ajaxSuccess(array('error'=>'Error updatingterminal in database, seek assistance'));
    exit();
}

$response['name'] = $name;
$response['terminal'] = $data;
if ($paired_at !== null && $paired_at != '') {
    $response['message'] = "Terminal $name updated, now paired with status $status";
    $response['ok'] = 1;
} else {
    $response['message'] = "Terminal $name updated, not yet paired, please check that it logged in correctly, current status is $status";
    $response['ok'] = 0;
}
ajaxSuccess($response);
