<?php
require_once('../lib/base.php');
require_once('../../lib/email__load_methods.php');
require_once('../../lib/log.php');
require_once('../../lib/reg_receipt.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

global $con;
$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$vendor_conf = get_conf('vendor');

$response['conid'] = $conid;
load_email_procs();


if(!isset($_SESSION['id'])) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Session Failure'));
    exit;
}

$exhId = $_SESSION['id'];

// which space purchased
if (!array_key_exists('email', $_POST)) {
    ajaxError("invalid calling sequence");
    exit();
}
$email = $_POST['email'];
$receiptTxt = $_POST['text'];
$receiptHTML = $_POST['tables'];

$return_arr = send_email($conf['regadminemail'], $email, null, 'Receipt for Payment', $receiptTxt, $receiptHTML);

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}
if (array_key_exists('email_error', $return_arr)) {
    $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error_code';
} else {
    $response['success'] = "Email sent to $email";
}

ajaxSuccess($response);
