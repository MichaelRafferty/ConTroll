<?php
require_once "../lib/base.php";
require_once(__DIR__ . '/../../lib/checkmerge.php');
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

if(!isset($_POST)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('remain', $_POST)) {
    $response['error'] = 'Invalid Calling Sequence';
    ajaxSuccess($response);
    exit();
}

$remain = $_POST['remain'];

if (array_key_exists('matchCount', $_POST)) {
    $matchCount = $_POST['matchCount'];
} else {
    $matchCount = 6;
}

$response = checkmerge($remain, $matchCount);

ajaxSuccess($response);
