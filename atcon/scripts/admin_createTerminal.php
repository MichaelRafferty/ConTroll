<?php
// library AJAX Processor: admin_createTerminal.php
// Balticon Registration System
// Author: Syd Weinstein
// call API create terminal and show the details and add it to the database

require_once('../lib/base.php');
require_once('../../lib/term__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'manager';
$con = get_conf('con');
$conid = $con['id'];
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

$newName = $_POST['terminal'];
$location = $_POST['location'];
// now call API to create the terminal
load_term_procs();
$terminal = term_createDeviceCode($newName, $location);

$data = $terminal['device_code'];
$id = $data['id'];
$name = $data['name'];
$code = $data['code'];
$product_type = $data['product_type'];
$locationId = $data['location_id'];
$created_at = $data['created_at'];
$pair_by = $data['pair_by'];
$status = $data['status'];
$status_changed_at = $data['status_changed_at'];

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
$response['terminal'] = $data;
$response['message'] = "Terminal $name created, pair with code $code before $pair_by";
ajaxSuccess($response);
