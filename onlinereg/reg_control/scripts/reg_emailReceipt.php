<?php
// library AJAX Processor: reg_printReceipt.php
// Balticon Registration System
// Author: Syd Weinstein
// Print a receipt from the regcontrol registration screen

require_once '../lib/base.php';
require_once('../../../lib/log.php');
require_once('../../../lib/email__load_methods.php');

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
$reg_info = get_conf('reg');
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'printReceipt') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

// printReceipt: print the text receipt "text", if printer name starts with 0, then just log the receipt
$header = $_POST['header'];
$prows = $_POST['prows'];
$mrows = $_POST['mrows'];
$pmtrows = $_POST['pmtrows'];
$footer = $_POST['footer'];

$dolfmt = new NumberFormatter("", NumberFormatter::CURRENCY);

// start with header
$receipt = $header . "\n";
// cart rows, only added to printout:
$already_paid = 0;
$total_due = 0;

foreach ($prows as $prow) {
    $receipt .= "\nMember: " . trim($prow['first_name'] . ' ' . $prow['last_name']) . "\n";
    $member_due = 0;
    foreach ($mrows as $mrow) {
        if ($mrow['perid'] == $prow['perid']) {
            $receipt .= "   " . $mrow['label'] . ", " . $dolfmt->formatCurrency((float) $mrow['price'], 'USD') . "\n";
            if (array_key_exists('prior_paid', $mrow))
                $already_paid += $mrow['prior_paid'];
            $member_due += $mrow['price'];
        }
    }
    $member_due = round($member_due, 2);
    $receipt .= "   Subtotal: " . $dolfmt->formatCurrency($member_due, 'USD') . "\n";
    $total_due += $member_due;
}
$receipt .= "Total Due:   " . $dolfmt->formatCurrency((float) $total_due, 'USD') . "\n\nPayment   Amount Description/Code\n";
$total_pmt = 0;
if ($already_paid > 0) {
    $total_pmt += $already_paid;
    $receipt .= sprintf("prior%15s Already Paid\n", $dolfmt->formatCurrency($already_paid, 'USD'));
}

foreach ($pmtrows as $pmtrow) {
    $type = $pmtrow['type'];
    $amtlen = 20 - mb_strlen($type);

    $line = sprintf("%s%" . $amtlen . "s %s", $type, $dolfmt->formatCurrency($pmtrow['amt'], 'USD'), $pmtrow['desc']);
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
$receipt .= "         ----------\n" . sprintf("total%15s Total Amount Tendered", $dolfmt->formatCurrency($total_pmt, 'USD')) . "\n$footer\n" . "\n" . $endtext . "\n\n\n";

if (!array_key_exists('email_addrs', $_POST)) {
    $response['error'] = "No email recipeints specified";
} else {
    load_email_procs();
    if ($reg_info['test'] == 1) {
        $emails = array($con['regadminemail']);
    } else {
        $emails = $_POST['email_addrs'];
    }
    foreach ($emails as $email_addr) {
        if (!filter_var($email_addr, FILTER_VALIDATE_EMAIL)) {
            $response['error'] = "Unable to email receipt, email address of '$email_addr' is not in the valid format.";
        } else { // valid email, send the email
            $return_arr = send_email($con['regadminemail'], $email_addr, null, $header, $receipt, null);
            if (array_key_exists('error_code', $return_arr)) {
                $error_code = $return_arr['error_code'];
            } else {
                $error_code = null;
            }
            if (array_key_exists('email_error', $return_arr)) {
                $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
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
