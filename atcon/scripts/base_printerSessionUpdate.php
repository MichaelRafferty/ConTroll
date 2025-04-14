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

if (!check_atcon('any', $conid)) {
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
    $printer = array(
        'name' => 'None',
        'host' => '',
        'queue' => '',
        'type' => '',
        'code' => 'UTF-8',
    );
    if (array_key_exists($prt, $_POST)) {
        $pr = $_POST[$prt];
        if ($pr != '') {
            $printerTop = explode(':::', $pr);
            $server = explode(':-:', $printerTop[1]);
            $printer = array (
                'name' => $printerTop[0],
                'host' => $server[0],
                'queue' => $server[1],
                'type' => $server[2],
                'code' => $server[3],
            );
        }
    }
    setSessionVar($prt . 'Printer', $printer);
    $response[$prt] = $printer['name'];
    $response[$prt . 'Printer'] = $printer['name'] != 'None';
}

if (array_key_exists('terminal', $_POST)) {
    $term = $_POST['terminal'];
    if ($term != '') {
        $termTop = explode(':::', $term);
        $terminal = array (
            'name' => $termTop[0],
            'squareId' => $termTop[1],
            'deviceId' => $termTop[2],
            'squareCode' => $termTop[3],
            'locationId' => $termTop[4],
        );
        setSessionVar('terminal', $terminal);
        $response['terminal'] = $terminal['name'];
    } else {
        unsetSessionVar('terminal');
        $response['terminal'] = 'None';
    }
} else {
    unsetSessionVar('terminal');
    $response['terminal'] = 'None';
}

ajaxSuccess($response);
