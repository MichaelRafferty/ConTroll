<?php
require_once('../lib/base.php');
require_once('../../lib/reg_receipt.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

global $con;
$con = get_con();
$conid=$con['id'];

$response['conid'] = $conid;

if (!isSessionVar('id')) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Session Failure'));
    exit;
}

$exhId = getSessionVar('id');

$response = array("post" => $_POST, "get" => $_GET);

// which space purchased
if (!array_key_exists('regionYearId', $_POST)) {
    ajaxError("invalid calling sequence");
    exit();
}
$regionYearId = $_POST['regionYearId'];

$response = trans_receipt(null, $exhId, $regionYearId);
ajaxSuccess($response);
