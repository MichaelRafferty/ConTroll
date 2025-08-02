<?php
// library AJAX Processor: pos_printReceipt.php
// ConTroll Registration System
// Author: Syd Weinstein
// Print a receipt from the regcontrol registration screen

require_once '../lib/base.php';
require_once('../../lib/log.php');
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
if (array_key_exists('currency', $con)) {
    $currency = $con['currency'];
} else {
    $currency = 'USD';
}

$response = array('post' => $_POST, 'get' => $_GET);
// printReceipt: print the text receipt "text", if printer name starts with 0, then just log the receipt
$header = $_POST['header'];
$footer = $_POST['footer'];
$payTid = $_POST['payTid'];
try {
    $prows = json_decode($_POST['prows'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}
try {
    $pmtrows = json_decode($_POST['pmtrows'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}
if (array_key_exists('receipt_type', $_POST))
    $receipt_type = $_POST['receipt_type'];
else
    $receipt_type = 'print';

$dolfmt = new NumberFormatter("", NumberFormatter::CURRENCY);

// start with header
$receipt = $header . "\n";
// cart rows, only added to printout:
$already_paid = 0;
$total_due = 0;
$taxPaid = 0;
foreach ($pmtrows as $pmtrow) {
    $type = $pmtrow['type'];
    if ($type == 'prior')
        continue;

    $taxPaid += $pmtrow['taxAmt'];
}

foreach ($prows as $prow) {
    $first = true;
    $member_due = 0;
    foreach ($prow['memberships'] as $mrow) {
        if ($mrow['tid2'] != $payTid)
            continue;
        if ($first) {
            $receipt .= "\nMember: " . trim($prow['fullName']) . "\n";
            $first = false;
        }
        $receipt .= "   " . $mrow['label'] . ", " . $dolfmt->formatCurrency((float) $mrow['price'], $currency) .
            " (" . $mrow['status'] . ")\n";
        if (array_key_exists('prior_paid', $mrow))
            $already_paid += $mrow['prior_paid'];
        $member_due += $mrow['price'];
    }
    $member_due = round($member_due, 2);
    $receipt .= "   Member Subtotal: " . $dolfmt->formatCurrency($member_due, $currency) . "\n";
    $total_due += $member_due;
}
if ($taxPaid > 0) {
    $receipt .= 'Pre-Tax Subtotal:  ' . $dolfmt->formatCurrency($total_due, $currency) . "\n";
    $receipt .= 'Sales Tax:  ' . $dolfmt->formatCurrency($taxPaid, $currency) . "\n";
    $total_due += $taxPaid;
}
$receipt .= "Total Due:   " . $dolfmt->formatCurrency((float) $total_due, $currency) . "\n\nPayment   Amount Description/Code\n";
$total_pmt = 0;
if ($already_paid > 0) {
    $total_pmt += $already_paid;
    $receipt .= sprintf("prior%15s Already Paid\n", $dolfmt->formatCurrency($already_paid, $currency));
}

foreach ($pmtrows as $pmtrow) {
    $type = $pmtrow['type'];
    if ($type == 'prior')
        continue;

    $amtlen = 20 - mb_strlen($type);

    $line = sprintf("%s%" . $amtlen . "s %s", $type, $dolfmt->formatCurrency($pmtrow['amt'], $currency), $pmtrow['desc']);
    if ($type == 'check') {
        $line .= ' /' . $pmtrow['checkno'];
    }
    if ($type == 'credit') {
        $line .= ' /' . $pmtrow['ccauth'];
    }
    $receipt .= $line . "\n";
    $total_pmt += $pmtrow['amt'];
}
$endtext = "\n";
if (array_key_exists('endtext', $con))
    $endtext = $con['endtext'] . "\n";
$receipt .= "         ----------\n" . sprintf("total%15s Total Amount Tendered", $dolfmt->formatCurrency($total_pmt, $currency)) . "\n$footer\n" . "\n" . $endtext . "\n\n\n";

if ($receipt_type == 'print') {
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
    }
    else {
        load_email_procs();
        if (getConfValue('reg','test']) == 1) {
            $emails = array ($con['regadminemail']);
        }
        else {
            $emails = $_POST['email_addrs'];
        }
        foreach ($emails as $email_addr) {
            if (!filter_var($email_addr, FILTER_VALIDATE_EMAIL)) {
                $response['error'] = "Unable to email receipt, email address of '$email_addr' is not in the valid format.";
            }
            else { // valid email, send the email
                $return_arr = send_email($con['regadminemail'], $email_addr, null, $header, $receipt, null);
                if (array_key_exists('error_code', $return_arr)) {
                    $error_code = $return_arr['error_code'];
                }
                else {
                    $error_code = null;
                }
                if (array_key_exists('email_error', $return_arr)) {
                    $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error_code';
                }
                else {
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
