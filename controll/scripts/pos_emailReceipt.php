<?php
// library AJAX Processor: pos_printReceipt.php
// ConTroll Registration System
// Author: Syd Weinstein
// Print a receipt from the regcontrol registration screen

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/receipt.php');
require_once('../../lib/email__load_methods.php');

$check_auth = google_init('ajax');
$perm = 'registration';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    RenderErrorAjax('Authentication Failed');
    exit();
}

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = [];

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'printReceipt') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
$con = get_conf('con');

// printReceipt: print the text receipt "text", if printer name starts with 0, then just log the receipt
$header = $_POST['header'];
$payTid = $_POST['payTid'];
$receipts = trans_receipt($payTid);

if (!array_key_exists('email_addrs', $_POST)) {
    $response['error'] = "No email recipients specified";
} else {
    load_email_procs();
    if (getConfValue('reg','test') == 1) {
        $emails = array($con['regadminemail']);
    } else {
        $emails = $_POST['email_addrs'];
    }
    foreach ($emails as $email_addr) {
        if (!filter_var($email_addr, FILTER_VALIDATE_EMAIL)) {
            $response['error'] = "Unable to email receipt, email address of '$email_addr' is not in the valid format.";
        } else { // valid email, send the email
            $return_arr = send_email($con['regadminemail'], $email_addr, null, $header, $receipts['receipt'], $receipts['receipt_tables']);
            if (array_key_exists('error_code', $return_arr)) {
                $error_code = $return_arr['error_code'];
            } else {
                $error_code = null;
            }
            if (array_key_exists('email_error', $return_arr)) {
                $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error_code';
            } else {
                if (array_key_exists('message', $response))
                    $response['message'] .= "<br/>Receipt sent to $email_addr";
                else
                    $response['message'] = "Receipt sent to $email_addr";
            }
        }
    }
}

ajaxSuccess($response);
