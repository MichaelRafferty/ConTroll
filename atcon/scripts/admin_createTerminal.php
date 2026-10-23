<?php
// ConTroll Registration System, Copyright 2015-2026, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: admin_createTerminal.php
// Author: Syd Weinstein
// call API create terminal and show the details and add it to the database

require_once('../lib/base.php');
require_once('../../lib/cc__load_methods.php');
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
if ($ajax_request_action != 'create') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if (!(array_key_exists('terminal', $_POST) && array_key_exists('location', $_POST))) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$ccType = getConfValue('cc', 'type', '');
if ($ccType != 'square' && $ccType != 'stripe') {
    ajaxSuccess(array('error' => "Terminals not supported for credit provider $ccType"));
    exit();
}

$newName = $_POST['terminal'];
$location = $_POST['location'];
$regCode = null;
if ($ccType == 'stripe')
    $regCode = $_POST['regCode'];

// check if its in the database
if (getTerminal($newName)) {
    ajaxSuccess(array('error'=>"Error Terminal $newName already exists."));
    exit();
}
// now call API to create the terminal
load_cc_procs(); // needed for log code
load_term_procs();
$data = term_createDeviceCode($newName, $location, $regCode, true);

// common values
$id = $data['id'];


$status = $data['status'];
$pair_by = null;

// square only items
if ($ccType == 'square') {
    $code = $data['code'];
    $locationId = $data['location_id'];
    $name = $data['name'];
    $product_type = $data['product_type'];
    $createdAt = new DateTime($data['created_at']);
    $statusChangedAt = new DateTime($data['status_changed_at']);
    $dateTime = new DateTime($data['pair_by']);
    $dateTime->setTimezone(new DateTimeZone('UTC'));
    $pair_by = $dateTime->format('Y-m-d H:i:s');
}
// stripe only itemns
if ($ccType == 'stripe') {
    $code = '';
    $createdAt = new DateTime();
    $statusChangedAt = new DateTime();
    $name = $data['label'];
    $locationId = $data['location'];
    $product_type = $data['object'];
    $pair_by = null;
}

$createdAt->setTimezone(new DateTimeZone('UTC'));
$created_at = $createdAt->format('Y-m-d H:i:s');

$statusChangedAt->setTimezone(new DateTimeZone('UTC'));
$status_changed_at = $statusChangedAt->format('Y-m-d H:i:s');

$insQ = <<<EOS
INSERT INTO terminals(name, productType, locationId, squareId, squareCode, createDate, pairBy, status, statusChanged)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
$dt = 'sssssssss';
$newKey = dbSafeInsert($insQ, $dt, array($name, $product_type, $locationId, $id, $code, $created_at, $pair_by, $status, $status_changed_at));
if ($newKey === false) {
    ajaxSuccess(array('error'=>'Error inserting terminal into database, seek assistance'));
    exit();
}

$response['name'] = $newKey;
if ($ccType == 'square')
    $response['message'] = "Terminal $name created, pair with code $code before $pair_by";
else {
    $data['created_at'] = $created_at;
    $data['status_changed_at'] = $status_changed_at;
    $data['pair_by'] = null;
    $data['paired_at'] = null;
    $data['product_type'] = $data['device_type'];
    $data['code'] = $regCode;
    $data['device_id'] = $data['serial_number'];
    $data['name'] = $data['label'];
    $data['location_id'] = $data['location'];
    updateTerminal($data);
    $response['message'] = "Terminal $name created";
}
$response['terminal'] = $data;

ajaxSuccess($response);
