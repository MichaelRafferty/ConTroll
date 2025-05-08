<?php
// library AJAX Processor: artpos_printReceipt.php
// ConTroll Registration System
// Author: Syd Weinstein
// Print a receipt from the Art Sales POS

require_once('../lib/base.php');
require_once('../lib/badgePrintFunctions.php');
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

if (array_key_exists('currency', $con)) {
    $currency = $con['currency'];
} else {
    $currency = 'USD';
}
// printReceipt: print the text receipt "text", if printer name starts with 0, then just log the receipt
$header = $_POST['header'];
$person = $_POST['person'];

try {
    $arows = json_decode($_POST['arows'], true, 512, JSON_THROW_ON_ERROR);
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

$footer = $_POST['footer'];
if (array_key_exists('receipt_type', $_POST))
    $receipt_type = $_POST['receipt_type'];
else
    $receipt_type = 'print';

$dolfmt = new NumberFormatter("", NumberFormatter::CURRENCY);

// start with header
$receipt = $header . "\n";
// cart rows, only added to printout:
$total_due = 0;

// compute tax
$taxAmt = 0;
foreach ($pmtrows as $pmtrow) {
    $taxAmt += $pmtrow['tax'];
}

foreach ($arows as $arow) {
    $receipt .= "Art Item: " . $arow['exhibitorNumber'] . '-' . $arow['item_key'] . ' (' . $arow['type'] . ')' . PHP_EOL;
    // Artist
    $receipt .= '     Artist: ' . $arow['exhibitorName'] . PHP_EOL;
    $receipt .= '     Title: ' . $arow['title'] . PHP_EOL;
    // Material
    $receipt .= '     Material: ' . $arow['material'] . PHP_EOL;
    if ($arow['type'] == 'print') {
        $receipt .= '     Quantity: ' . $arow['purQuantity'] . ' at ' . $dolfmt->formatCurrency((float) $arow['sale_price'], $currency) . ' each' . PHP_EOL;
        $arow['final_price'] = $arow['sale_price'] * $arow['purQuantity'];
    }
    // price
    $receipt .= $arow['priceType'] . ' Price: ' . $dolfmt->formatCurrency((float) $arow['final_price'], $currency) . PHP_EOL . PHP_EOL;

    $total_due += $arow['final_price'];
}
if ($taxAmt > 0) {
    $receipt .= 'Pre Tax Due: ' . $dolfmt->formatCurrency((float)$total_due, $currency) . PHP_EOL .
        'Sales Tax:   ' . $dolfmt->formatCurrency((float)$taxAmt, $currency) . PHP_EOL;
    $total_due += $taxAmt;
}
$receipt .= 'Total Due:   ' . $dolfmt->formatCurrency((float)$total_due, $currency);

$receipt .= "\n\nPayment   Amount Description/Code\n";
$total_pmt = 0;

foreach ($pmtrows as $pmtrow) {
    $type = $pmtrow['type'];
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
$endtext = "\nThank you for your purchase. All sales are final. There are no refunds or exchanges.\n";
$receipt .= "         ----------\n" . sprintf("Total%15s Total Amount Tendered", $dolfmt->formatCurrency($total_pmt, $currency)) . "\n$footer\n" . "\n" . $endtext . "\n\n\n";

if ($receipt_type == 'print') {
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
        $response['error'] = "No email recipeints specified";
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
                $return_arr = send_email($from, $email_addr, null, $header, $receipt, null);
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
