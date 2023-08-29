<?php

// library AJAX Processor: regpos_getCouponData.php
// Balticon Registration System
// Author: Syd Weinstein
// Get the coupon data for a specific coupon ID including mtype tables

require_once('../lib/base.php');
require_once('../../lib/coupon.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$atcon = get_conf('atcon');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
$ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'getCouponDetails') {
RenderErrorAjax('Invalid calling sequence.');
exit();
}
if (!(check_atcon('cashier', $conid))) {
$message_error = 'No permission.';
RenderErrorAjax($message_error);
exit();
}

if(!isset($_POST) || !isset($_POST['couponId'])) {
    ajaxSuccess(array('status'=>'error', 'error'=>"Error: no coupon id")); exit();
}

$condata = get_con();
$con = get_conf('con');

$id = $_POST['couponId'];
$results = load_coupon_details($id);
ajaxSuccess($results);
?>
