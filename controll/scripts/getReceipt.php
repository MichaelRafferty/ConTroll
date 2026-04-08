<?php
require_once "../lib/base.php";
require_once "../../lib/receipt.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'reg_staff';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('transid', $_POST)) {
    $transid = $_POST['transid'];
    $response = trans_receipt($transid);
} else {
    $response['error'] = 'Improper calling sequence';
}

ajaxSuccess($response);
exit();
