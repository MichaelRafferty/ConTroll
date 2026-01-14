<?php
require_once "../lib/base.php";
require_once '../../lib/posFindRecord.php';
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'lookup';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('action', $_POST) && array_key_exists('name_search', $_POST))) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$findPattern = $_POST['name_search'];
if ($findPattern == NULL || $findPattern == '') {
    $response['error'] = 'The search pattern cannot be empty.';
    ajaxSuccess($response);
    exit();
}

$response = posFindRecord();
ajaxSuccess($response);
