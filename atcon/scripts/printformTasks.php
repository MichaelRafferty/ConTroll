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

function printBadge($conid) {
    if (isset($_SESSION['badgePrinter'])) {
        $printer = $_SESSION['badgePrinter'];
        $params = $_POST['params'];
        $badge['type'] = $params['type'];
        $badge['badge_name'] = $params['badge_name'];
        $badge['category'] = $params['category'];
        $badge['id'] = $params['badge_id'];
        $badge['day'] = $params['day'];
        $badge['age'] = $params['age'];

        $response = array();
        
        if ($badge['type'] == 'full') {
            $file_full = init_file($printer);
            write_badge($badge, $file_full, $printer);
            print_badge($printer, $file_full);
            $response['message'] = "Full badge for " . $badge['badge_name'] . " printed";
        } else {
            $file_1day = init_file($printer);
            write_badge($badge, $file_1day, $printer);
            print_badge($printer, $file_1day);
            $response['message'] = $badge['day'] . ' badge for ' . $badge['badge_name'] . ' printed';
        }

        ajaxSuccess($response);
    } else {
        ajaxError("No printer selected");
    }
}
// outer ajax wrapper
// method - permission required to access this AJAX function
// action - passed in from the javascript

$method = 'cashier';
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
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}
switch ($ajax_request_action) {
    case 'printBadge':
        printBadge($conid);
        break;
    default:
        $message_error = 'Internal error.';
        RenderErrorAjax($message_error);
        exit();
}
