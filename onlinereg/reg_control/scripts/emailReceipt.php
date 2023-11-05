<?php
global $db_ini;

require_once "../lib/base.php";
require_once(__DIR__ . '/../../../lib/email__load_methods.php');

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

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
    $response['error'] = 'Improper calling sequence';
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
    $response['success'] = $success;
}

ajaxSuccess($response);
exit();
