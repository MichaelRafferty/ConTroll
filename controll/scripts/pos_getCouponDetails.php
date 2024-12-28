<?php
// library AJAX Processor: reg_getCouponData.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve all the details for a specific coupomn

require_once '../lib/base.php';
require_once('../../lib/coupon.php');

$check_auth = google_init('ajax');
$perm = 'registration';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    RenderErrorAjax('Authentication Failed');
    exit();
}

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

if(!isset($_POST) || !isset($_POST['couponId'])) {
    ajaxSuccess(array('status'=>'error', 'error'=>"Error: no coupon id")); exit();
}

$condata = get_con();
$con = get_conf('con');

$id = $_POST['couponId'];
$results = load_coupon_details($id);
ajaxSuccess($results);
?>
