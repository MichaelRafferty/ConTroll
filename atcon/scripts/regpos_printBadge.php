<?php

// library AJAX Processor: regpos_printBadge.php
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
            $badgefile = print_badge($printer, $file_full);
            $response['message'] .= "Full badge for " . $badge['badge_name'] . " printed.";
            if(mb_substr($printer[2],0,1)=='0') {
                $response['message'] .= " <a href='$badgefile'>Badge</a>";
            }
            $response['message'] .= "<br/>";
        } else {
            $file_1day = init_file($printer);
            write_badge($badge, $file_1day, $printer);
            $badgefile = print_badge($printer, $file_1day);
            $response['message'] .= $badge['day'] . ' badge for ' . $badge['badge_name'] . ' printed.';
            if($mb_substr($printer[2],0,1)=='0') {
                $response['message'] .= " <a href='$badgefile'>Badge</a>";
            }
            $response['message'] .= "<br/>";
        }
    }
    ajaxSuccess($response);
} else {
    ajaxError("No printer selected");
}
