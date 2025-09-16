<?php
require_once '../lib/base.php';
require_once '../../lib/email__load_methods.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array ('post' => $_POST, 'get' => $_GET);

$con = get_con();
$conid = $con['id'];
$conf = get_conf('con');
$portal_conf = get_conf('portal');

$response['conid'] = $conid;

if (!(array_key_exists('okmsg', $_POST) && array_key_exists('email', $_POST))) {
    ajaxSuccess(array ('status' => 'error', 'message' => 'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array ('status' => 'error', 'message' => 'Not logged in.'));
    exit();
}

validateLoginId();

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
    if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');

//        email: email,
//        okmsg: success,
//        text: document.getElementById('receipt-text').innerHTML,
//        html: document.getElementById('receipt-div').innerHTML,
//        subject: document.getElementById('receiptTitle').innerHTML,

if (array_key_exists('okmsg', $_POST)) {
    $okmsg = $_POST['okmsg'];
    $email = $_POST['email'];
    $text = $_POST['text'];
    $html = $_POST['html'];
    $subject = $_POST['subject'];
    $success = $_POST['success'];
} else {
    $response['status'] = 'error';
    $response['message'] = 'Improper calling sequence';
}

load_email_procs();
$con = get_conf('con');
$response['email'] = $email;
$response['subject'] = $subject;

$return_arr = send_email($con['regadminemail'], trim($email), /* cc */ null, $subject, $text, $html);
if ($return_arr['status'] == 'success') {
    $response['status'] = 'success';
    $response['message'] = $success;
}

ajaxSuccess($response);
exit();
