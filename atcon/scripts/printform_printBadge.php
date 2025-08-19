<?php
// ConTroll Registration System, Copyright 2015-2025, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: printform_printBadge.php
// Author: Syd Weinstein
// print a badge from printform

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
if ($ajax_request_action != 'printBadge') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// print a badge if the printer is defined, note queue starting with 0 == make temp file only
$response = array();
$response['message'] = '';
$printer = getSessionVar('badgePrinter');
if ($printer) {
    $params = $_POST['params'];
    if (array_key_exists('badges', $_POST)) {
        $response['badges'] = $_POST['badges'];
    }

    foreach ($params as $param) {
        $badge = [];
        $badge['type'] = $param['type'];
        $badge['badge_name'] = $param['badge_name'];
        $badge['category'] = $param['category'];
        $badge['id'] = $param['badge_id'];
        $badge['day'] = $param['day'];
        $badge['age'] = $param['age'];

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
