<?php
global $db_ini;

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

if (!(array_key_exists('id', $_SESSION) && array_key_exists('idType', $_SESSION))) {
    ajaxSuccess(array ('status' => 'error', 'message' => 'Not logged in.'));
    exit();
}

$loginId = $_SESSION['id'];
$loginType = $_SESSION['idType'];

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
$reg = get_conf('reg');
if ($reg['test'] == 1 && $email != $con['regadminemail']) {
    $send_email = $con['regadminemail'];
    $send_subject = "Test email to $email for $subject";
} else {
    $send_email = $email;
    $send_subject = $subject;
}

$response['email'] = $send_email;
$response['subject'] = $send_subject;

$return_arr = send_email($con['regadminemail'], trim($send_email), /* cc */ null, $send_subject, $text, $html);
if ($return_arr['status'] == 'success') {
    $response['status'] = 'success';
    $response['message'] = $success;
}

ajaxSuccess($response);
exit();
