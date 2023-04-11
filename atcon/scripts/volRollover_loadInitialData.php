<?php

// library AJAX Processor: volRollover_loadInitialData.php
// Balticon Registration System
// Author: Syd Weinstein
// Load the matching and session data for volRollover.js

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'vol_roll';
$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'loadInitialData') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// loadInitialData:
// Load all the mapping tables for the rollover POS function

$response['label'] = $con['label'];
$response['conid'] = $conid;
$response['user_id'] = $_SESSION['user'];

// get the memId and label for conid + 1, volunteer rollover
$priceQ = <<<EOS
SELECT id, label, shortname
FROM memLabel
WHERE
    conid = ? AND shortname = 'Volunteer';
EOS;

$memarray = array();
$r = dbSafeQuery($priceQ, 'i', array($conid + 1));
if ($r->num_rows != 1) {
    ajaxError("Volunteer type not defined for conid " . ($conid + 1));
    return;
}
$l = fetch_safe_assoc($r);
$response['rollover_memId'] = $l['id'];
$response['rollover_label'] = $l['label'];
$response['rollover_shortname'] = $l['shortname'];
mysqli_free_result($r);
ajaxSuccess($response);
