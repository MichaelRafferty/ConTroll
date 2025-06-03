<?php
require_once('../lib/base.php');
require_once('../../lib/reg_receipt.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'exhibitor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

global $con;
$con = get_con();
$conid=$con['id'];

$response['conid'] = $conid;

if (!isset($_SESSION['id'])) {
    // get exhibitor id from post data
    if (!isset($_POST['exhibitorId'])) {
        ajaxError('invalid calling sequence');
        exit();
    }
    $exhId = $_POST['exhibitorId'];
} else {
    $exhId = $_SESSION['id'];
}

// which space purchased
if (!array_key_exists('regionYearId', $_POST)) {
    ajaxError("invalid calling sequence");
    exit();
}
$regionYearId = $_POST['regionYearId'];

$response = trans_receipt(null, $exhId, $regionYearId);
ajaxSuccess($response);
