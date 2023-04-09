<?php

// library AJAX Processor: printformTasks.php
// Balticon Registration System
// Author: Syd Weinstein
// Perform tasks under the printform page about ATCON users

require_once('../lib/base.php');
require_once('../lib/badgePrintFunctions.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

// print a badge if the printer is defined, note queue 0 == make temp file only
function printBadge():void {
    $response = array();
    $response['message'] = '';
    if (isset($_SESSION['badgePrinter'])) {
        $printer = $_SESSION['badgePrinter'];
        $params = $_POST['params'];
        if (array_key_exists('badges', $_POST)) {
            $response['badges'] = $_POST['badges'];
        }

        foreach ($params as $param) {
            $badge = [];
            $badge['type'] = $param['type'];
            $badge['badge_name'] = $param['badge_name'];
            $badge['full_name'] = $param['full_name'];
            $badge['category'] = $param['category'];
            $badge['id'] = $param['badge_id'];
            $badge['day'] = $param['day'];
            $badge['age'] = $param['age'];

            if ($badge['badge_name'] == '') {
                $badge['badge_name'] = $badge['full_name'];
            }

            if ($badge['type'] == 'full') {
                $file_full = init_file($printer);
                write_badge($badge, $file_full, $printer);
                print_badge($printer, $file_full);
                $response['message'] .= "Full badge for " . $badge['badge_name'] . " printed<br/>";
            } else {
                $file_1day = init_file($printer);
                write_badge($badge, $file_1day, $printer);
                print_badge($printer, $file_1day);
                $response['message'] .= $badge['day'] . ' badge for ' . $badge['badge_name'] . ' printed<br/>';
            }
        }
        ajaxSuccess($response);
    } else {
        ajaxError("No printer selected");
    }
}

// printReceipt: print the text receipt "text", if queue == 0, then just log the receipt
function printReceipt():void {
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
        $member_due = (float)round($member_due, 2);
        $receipt .= "   Subtotal: " . $dolfmt->formatCurrency((float) $member_due, 'USD') . "\n";
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

    $receipt .= "         ----------\n" . sprintf("total%15s Total Amount Tendered", $dolfmt->formatCurrency($total_pmt, 'USD')) . "\n$footer\n";

    if (isset($_SESSION['receiptPrinter'])) {
        $printer = $_SESSION['receiptPrinter'];
        $result_code = print_receipt($printer, $receipt);
    } else {
        web_error_log($receipt);
    }
    if ($result_code == 0)
        $response['message'] = 'receipt print queued';
    else
        $response['error'] = "Error code $result_code queuing receipt";
    ajaxSuccess($response);
}
// outer ajax wrapper
// method - permission required to access this AJAX function
// action - passed in from the javascript

$method = 'data_entry';
$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action == '') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $method = 'cashier';
    if (!check_atcon($method, $conid)) {
        $message_error = 'No permission.';
        RenderErrorAjax($message_error);
        exit();
    }
}
switch ($ajax_request_action) {
    case 'printBadge':
        printBadge();
        break;
    case 'printReceipt':
        printReceipt();
        break;
    default:
        $message_error = 'Internal error.';
        RenderErrorAjax($message_error);
        exit();
}
