<?php
// library AJAX Processor: artpos_printReceipt.php
// ConTroll Registration System
// Author: Syd Weinstein
// Print a receipt from the Art Sales POS

require_once('../lib/base.php');
require_once('../lib/badgePrintFunctions.php');
require_once('../../lib/receipt.php');
require_once('../../lib/email__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;
$response = [];

$con = get_conf('con');
$conid = $con['id'];
$atcon_info = get_conf('atcon');
$vendor_conf = get_conf('vendor');
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'printReceipt') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$currency = getConfValue('con', 'currency', 'USD');
// printReceipt: print the text receipt "text", if printer name starts with 0, then just log the receipt
$header = $_POST['header'];
$person = $_POST['person'];
$payTid = $_POST['payTid'];

if (array_key_exists('receipt_type', $_POST))
    $receipt_type = $_POST['receipt_type'];
else
    $receipt_type = 'print';

$receipts = trans_receipt($payTid);

if ($receipt_type == 'print') {
    $receipt = $receipts['receipt'];
    $printer = getSessionVar('receiptPrinter');
    if ($printer && $printer['name'] != 'None') {
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
        $response['error'] = "No email recipients specified";
    } else {
        load_email_procs();
        if (array_key_exists('artist', $vendor_conf)) {
            $from = $vendor_conf['artist'];
        } else {
            $from = $con['regadminemail'];
        }
        $emails = $_POST['email_addrs'];
        foreach ($emails as $email_addr) {
            if (!filter_var($email_addr, FILTER_VALIDATE_EMAIL)) {
                $response['error'] = "Unable to email receipt, email address of '$email_addr' is not in the valid format.";
            } else { // valid email, send the email
                $return_arr = send_email($from, $email_addr, null, $header, $receipts['receipt'], $receipts['receipt_tables']);
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
}

ajaxSuccess($response);
