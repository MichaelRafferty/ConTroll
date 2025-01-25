<?php

// library AJAX Processor: pos_printBadge.php
// Balticon Registration System
// Author: Syd Weinstein
// print a badge from POS

require_once('../lib/base.php');
require_once('../lib/badgePrintFunctions.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'printBadge') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// print a badge if the printer is defined, note queue starting with 0 == make temp file only
$response = array();
$response['message'] = '';
$printer = getSessionVar('badgePrinter');
if ($printer != null && $printer['name'] != 'None') {
    try {
        $params = json_decode($_POST['params'], true, 512, JSON_THROW_ON_ERROR);
    }
    catch (Exception $e) {
        $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
        $response['error'] = $msg;
        error_log($msg);
        ajaxSuccess($response);
        exit();
    }
    $response['badges'] = $params;

    foreach ($params as $param) {
        $badge = [];
        $badge['type'] = $param['type'];
        $badge['badge_name'] = $param['badge_name'];
        $badge['full_name'] = $param['full_name'];
        $badge['category'] = $param['category'];
        $badge['id'] = $param['badge_id'];
        $badge['day'] = $param['day'];
        $badge['age'] = $param['age'];
        if (array_key_exists('regId', $param))
            $badge['regId'] = $param['regId'];

        if ($badge['badge_name'] == '') {
            $badge['badge_name'] = $badge['full_name'];
        }

        if (($badge['type'] == 'one-day') || ($badge['type'] == 'oneday') || ($badge['type'] == 'oneDay')) {
            $file_1day = init_file($printer);
            write_badge($badge, $file_1day, $printer);
            $badgefile = print_badge($printer, $file_1day);
            $response['message'] .= $badge['day'] . ' badge for ' . $badge['badge_name'] . ' printed.';
            if (mb_substr($printer['queue'], 0, 1) == '0') {
                $response['message'] .= " <a href='$badgefile'>Badge</a>";
            }
            $response['message'] .= '<br/>';
        } else {
            $file_full = init_file($printer);
            write_badge($badge, $file_full, $printer);
            $badgefile = print_badge($printer, $file_full);
            $response['message'] .= "Full badge for " . $badge['badge_name'] . " printed.";
            if(mb_substr($printer['queue'],0,1)=='0') {
                $response['message'] .= " <a href='$badgefile'>Badge</a>";
            }
            $response['message'] .= "<br/>";
        }
    }
    ajaxSuccess($response);
} else {
    ajaxError("No printer selected");
}
