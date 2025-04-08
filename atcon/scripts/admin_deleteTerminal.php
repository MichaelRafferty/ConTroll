<?php
// library AJAX Processor: admin_deleteTerminal.php
// Balticon Registration System
// Author: Syd Weinstein
// delete and re-get the list of terminals

require_once('../lib/base.php');

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
if ($ajax_request_action != 'delete') {
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


// delete the terminal and fetch the remainer for refreshing the screen
$delQ = "DELETE FROM terminals WHERE name = ?;";
$del_rows = dbSafeCmd($delQ, 's', array($_POST['terminal']));

$terminals = [];

$terminalSQL = <<<EOS
SELECT *
FROM terminals
ORDER BY name
EOS;
$terminalQ = dbQuery($terminalSQL);
while ($terminal = $terminalQ->fetch_assoc()) {
    $terminals[] = $terminal;
}
$response['terminals'] = $terminals;
$terminalQ->free();

$response['message'] = "$del_rows terminal(s) deleted";
ajaxSuccess($response);
