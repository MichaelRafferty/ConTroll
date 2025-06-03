<?php
// library AJAX Processor: admin_getTerminalCodes.php
// Balticon Registration System
// Author: Syd Weinsteinretrieve the details on a terminal after it's been paired

require_once('../lib/base.php');
require_once('../../lib/term__load_methods.php');
require_once('../../lib/log.php');

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
$data = term_getDevice($name, true);

$id = $data['id'];
$name = $data['name'];
$code = $data['code'];
$product_type = $data['product_type'];
$locationId = $data['location_id'];
$created_at = $data['created_at'];
if (array_key_exists('pair_by', $data)) {
    $dateTime = new DateTime($data['pair_by']);
    $dateTime->setTimezone(new DateTimeZone('UTC'));
    $pair_by = $dateTime->format('Y-m-d H:i:s');
} else {
    $pair_by = null;
}
if (array_key_exists('paired_at', $data)) {
    $dateTime = new DateTime($data['paired_at']);
    $dateTime->setTimezone(new DateTimeZone('UTC'));
    $paired_at = $dateTime->format('Y-m-d H:i:s');
} else {
    $paired_at = null;
}
if (array_key_exists('device_id', $data)) {
    $device_id = $data['device_id'];
} else {
    $device_id = null;
}
$status = $data['status'];

$dateTime = new DateTime($data['status_changed_at']);
$dateTime->setTimezone(new DateTimeZone('UTC'));
$status_changed_at = $dateTime->format('Y-m-d H:i:s');

$updQ = <<<EOS
UPDATE terminals
SET deviceId = ?, pairBy = ?, pairedAt = ?, status = ?, statusChanged = ?
WHERE name = ?;
EOS;
$dt = 'ssssss';
$numRows = dbSafeCmd($updQ, $dt, array($device_id, $pair_by, $paired_at,  $status, $status_changed_at, $name));
if ($numRows === false) {
    ajaxSuccess(array('error'=>'Error updatingterminal in database, seek assistance'));
    exit();
}

$terminalSQL = <<<EOS
SELECT *
FROM terminals
ORDER BY name
EOS;
$terminalQ = dbQuery($terminalSQL);
while ($terminal = $terminalQ->fetch_assoc()) {
    $terminals[] = $terminal;
}
$response['terminals'] = $terminals;
$terminalQ->free();

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
