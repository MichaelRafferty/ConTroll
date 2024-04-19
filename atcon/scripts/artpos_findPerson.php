<?php

// library AJAX Processor: regpos_findRecord.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve perinfo and reg records for the Find and Add tabs

require_once('../lib/base.php');

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
if ($ajax_request_action != 'findRecord') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('artsales', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// findRecord:
// load all perinfo/reg records matching the search string or unpaid if that flag is passed
$find_type = $_POST['find_type'];
$name_search = $_POST['name_search'];

$response['find_type'] = $find_type;
$response['name_search'] = $name_search;

$limit = 99999999;
if (is_numeric($name_search)) {
//
// this is perid
//
    $findPersonQ = <<<EOS
SELECT p.id, first_name, last_name, badge_name, email_addr
FROM perinfo p
WHERE p.id=?;
EOS;
    $response['findPersonQ'] = $findPersonQ;
    $personR = dbSafeQuery($findPersonQ, 'i', array($name_search));
    $response['num_rows'] = $personR->num_rows;
    if($personR->num_rows == 0) {
        $response['status'] = "error";
        $response['error'] = "No Person Found";
    } else if($personR->num_rows == 1) {
        $response['person'] = $personR->fetch_assoc();
        $response['status'] = 'success';
        $response['message'] = "One Person Found";
    } else {
        for ($row = 0; $row < $personR->num_rows; $row++) {
            $response['person'][$row] = $personR->fetch_assoc();
            $response['person'][$row]['index'] = $row;
        }
        $response['status'] = 'success';
        $response['message'] = $personR->num_rows . " People Found.";
    }
} else {
//
// this is the string search portion as the field is alphanumeric
//
    $response['error'] = "Alphanumeric Search Not Implemented here!";
}

ajaxSuccess($response);
