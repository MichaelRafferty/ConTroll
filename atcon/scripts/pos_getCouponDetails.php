<?php
// ConTroll Registration System, Copyright 2015-2025, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: reg_getCouponData.php
// Author: Syd Weinstein
// Retrieve all the details for a specific coupomn

require_once '../lib/base.php';
require_once('../../lib/coupon.php');

$response = array('post' => $_POST, 'get' => $_GET);

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'getCouponDetails') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!isset($_POST) || !isset($_POST['couponId'])) {
    ajaxSuccess(array ('status' => 'error', 'error' => 'Error: no coupon id'));
    exit();
}

$con = get_conf('con');
$conid = $con['id'];

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$condata = get_con();
$id = $_POST['couponId'];
$results = load_coupon_details($id);
ajaxSuccess($results);
