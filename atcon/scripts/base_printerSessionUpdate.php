<?php

// library AJAX Processor: base_showPrinterSelect.php
// Balticon Registration System
// Author: Syd Weinstein
// retrieve the select list for printers

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];

if (!(check_atcon('data-entry', $conid) || check_atcon('cashier', $conid) || check_atcon('vol_roll', $conid) ||
        check_atcon('maanager', $conid) || check_atcon('artshow', $conid) || check_atcon('artinventory', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'printerSessionUpdate') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$printers = ['badge', 'receipt', 'generic'];
foreach ($printers as $prt) {
    if (array_key_exists($prt, $_POST)) {
        $pr = $_POST[$prt];
        if ($pr != '') {
            $printer = explode(':::', $pr);
            $server = explode(':-:', $printer[1]);
            $printer = array($printer[0], $server[0], $server[1], $server[2], $server[3]);
            $_SESSION[$prt . 'Printer'] = $printer;
        } else {
            $printer = array('None', '', '', '', 'UTF-8');
            $_SESSION[$prt . 'Printer'] = $printer;
        }
        $response[$prt] = $printer[0];
    } else {
        $_SESSION[$prt . 'Printer'] = array('None', '', '', '', 'UTF-8');
        $response[$prt] = 'None';
    }
}

ajaxSuccess($response);
