<?php
// ConTroll Registration System, Copyright 2015-2026, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: reg_getCouponData.php
// Author: Syd Weinstein
// Retrieve all the details for a specific coupomn

require_once '../lib/base.php';
require_once('../../lib/coupon.php');
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'registration';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

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
