<?php
// library AJAX Processor: pos_updateCartElements.php
// ConTroll Registration System
// Author: Syd Weinstein
// Store the cart into the system using add/update/delete and create appropriate transaction records
// Used both by mail-in registration (controll/registration.php) and atcon (atcon/regpos.php)

require_once '../lib/base.php';
require_once '../../lib/policies.php';
require_once '../../lib/posUpdateCartElements.php';
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
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'updateCartElements') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$user_id = $_POST['user_id'];
$user_perid = $authToken->getPerid();
if ($user_id != $user_perid) {
    ajaxError("Invalid credentials passed");
    return;
}


if (!array_key_exists('source', $_POST)) {
    $message_error = 'Source Missing';
    RenderErrorAjax($message_error);
    exit();
}
$source = $_POST['source'];

posUpdateCartElements($conid, $user_perid, $source);
