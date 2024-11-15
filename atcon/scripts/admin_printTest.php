<?php

// library AJAX Processor: admin_printTest.php
// Balticon Registration System
// Author: Syd Weinstein
// send test print jobs to the various printer types

require_once('../lib/base.php');
require_once('../lib/badgePrintFunctions.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'manager';
$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'printTest') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if (array_key_exists('currency', $con)) {
    $currency = $con['currency'];
} else {
    $currency = 'USD';
}

// printTest: print a test page/badge to check printer format/issues
// server: print server name
// printer: printer queue name
// type: type of the prionter
// codepage: encoding of the printer

if (array_key_exists("server", $_POST)) {
    $server = $_POST['server'];
} else {
    ajaxError("No server specified");
}
if (array_key_exists('printer', $_POST)) {
    $printer = $_POST['printer'];
} else {
    ajaxError('No printer specified');
}
if (array_key_exists('type', $_POST)) {
    $type = $_POST['type'];
} else {
    ajaxError('No type specified');
}
if (array_key_exists('codepage', $_POST)) {
    $codepage = $_POST['codepage'];
} else {
    ajaxError('No codepage specified');
}

$p = ['name' => "Test", 'host' => $server, 'queue' =>  $printer, 'type' => $type, 'code' => $codepage];
$curLocale = locale_get_default();
$dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);
$dolamt = $dolfmt->formatCurrency(123456.78, $currency);

switch($type) {
    case 'receipt':
        $receipt = <<<EOR

This is a test receipt.
The first line is blank.
It is designed to show the 
character set compliance:
Dollars: $dolamt
Special Character:
EUR: €
CENT: ¢
Pound: £
A grave: À
E^: Ê
c,: ç
o:: ö	
tm: ™
rg: ®
cr: ©
deg: °

End of receipt test,
there is a trailing blank line

EOR;
        print_receipt($p, $receipt);
        break;

    case 'badge':
        $badge = [];
        $badge['type'] = 'test';
        $badge['badge_name'] = 'Test Badge Name';
        $badge['full_name'] = 'Test Full Name';
        $badge['category'] = 'test';
        $badge['id'] = '00000';
        $badge['day'] = 'Mon';
        $badge['age'] = 'Any';
        $file_full = init_file($p);
        write_badge($badge, $file_full, $p);
        print_badge($p, $file_full);
        break;
    case 'generic':
        $generic = <<<EOR

This is a test for generic printers.  It is designed with longer lines than the receipt test.
The first line is blank.
It is designed to show the character set compliance:
Dollars: $dolamt
Special Characters:
EUR: €
CENT: ¢
Pound: £
A grave: À
E^: Ê
c,: ç
o:: ö	
tm: ™
rg: ®
cr: ©
deg: °

End of generic test, there is a trailing blank line

EOR;
        print_receipt($p, $generic);
        break;

    default:
        $response['error'] = "Invalid printer type received";
        ajaxSuccess($response);
        return;
}

$response['message'] = "Printed test page for printer type $type using encoding $codepage to $server:$printer";
ajaxSuccess($response);
