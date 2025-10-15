<?php
// library AJAX Processor: pos_printReceipt.php
// ConTroll Registration System
// Author: Syd Weinstein
// Print a receipt from the regcontrol registration screen

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/receipt.php');
require_once('../lib/badgePrintFunctions.php');
require_once('../../lib/email__load_methods.php');

$con = get_conf('con');
$conid = $con['id'];
// use common global Ajax return functions
    global $returnAjaxErrors, $return500errors;
    $returnAjaxErrors = true;
    $return500errors = true;

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$user_id = $_POST['user_id'];
if ($user_id != getSessionVar('user')) {
    ajaxError('Invalid credentials passed');
}
$user_perid = $user_id;

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'printReceipt') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$response = array('post' => $_POST, 'get' => $_GET);
// printReceipt: print the text receipt "text", if printer name starts with 0, then just log the receipt
$payTid = $_POST['payTid'];
$header = $_POST['header'];

if (array_key_exists('receipt_type', $_POST))
    $receipt_type = $_POST['receipt_type'];
else
    $receipt_type = 'print';

$receipts = trans_receipt($payTid);
if ($receipt_type == 'print') {
    $receipt = $receipts['receipt'];
    $printer = getSessionVar('receiptPrinter');
    if ($printer != null && $printer['name'] != 'None') {
        $result_code = print_receipt($printer, $receipt);
    } else {
        web_error_log($receipt);
        $result_code = 0;
    }
    if ($result_code == 0)
        $response['message'] = 'receipt print queued';
    else
        $response['error'] = "Error code $result_code queuing receipt";
}
if ($receipt_type == 'email') {
    if (!array_key_exists('email_addrs', $_POST)) {
        $response['error'] = "No email recipeints specified";
    } else {
        load_email_procs();
        if (getConfValue('reg','test') == 1) {
            $emails = array ($con['regadminemail']);
        } else {
            $emails = $_POST['email_addrs'];
        }
        foreach ($emails as $email_addr) {
            if ($email_addr == '/r')
                continue;
            if (!filter_var($email_addr, FILTER_VALIDATE_EMAIL)) {
                $response['error'] = "Unable to email receipt, email address of '$email_addr' is not in the valid format.";
                continue;
            }

            // valid email, send the email
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
