<?php
// ConTroll Registration System, Copyright 2015-2026, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: admin_getTerminalStatus.php
// Author: Syd Weinstein
// refresh the status of a single terminal

require_once('../lib/base.php');
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/term__load_methods.php');

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
if ($ajax_request_action != 'refreshStatus') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if (!array_key_exists('terminal', $_POST)) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$ccType = getConfValue('cc', 'type', 'offline');
$terminal = $_POST['terminal'];
if ($ccType == 'stripe') {
    // get the id from the database
    $idSQL = <<<EOS
SELECT squareId
FROM terminals
WHERE name = ?;
EOS;
    $idR = dbSafeQuery($idSQL, 's', array($terminal));
    if ($idR === false || $idR->num_rows != 1) {
        RenderErrorAjax('Invalid calling sequence.');
        exit();
    }
    $id = $idR->fetch_assoc()['squareId'];
    $idR->free();
} else
    $id = null;

load_cc_procs(); // for exception processing
load_term_procs();
// get the status from the terminal
$response = term_getStatus($terminal, $id);
// temp row for now
if (!array_key_exists('updCnt', $response)) {
    $response['updCnt'] = 0;
}
$response['message'] = "$terminal status updated, " . $response['updCnt'] . " row(s) updated";
ajaxSuccess($response);
