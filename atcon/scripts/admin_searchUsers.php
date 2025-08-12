<?php

// library AJAX Processor: admin_searchUsers.php
// Balticon Registration System
// Author: Syd Weinstein
// return list of users for add search

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
if ($ajax_request_action != 'searchUsers') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// searchUsers: given a search string, return a list of potential users
//      who match that string for first/middle/last name or badge_name.
// if the search string is numeric, use the perid as the search parameter

if (!isset($_POST['search_string'])) {
    ajaxError('No Data');
}
$search_string = $_POST['search_string'];
$response['search'] = $search_string;
$response['message'] = 'ok';

if (is_numeric($search_string)) {
    $searchSql = <<<EOS
SELECT p.id, first_name, last_name, badge_name, email_addr
FROM perinfo p
LEFT OUTER JOIN atcon_user a ON (a.perid = p.id and a.conid = ?)
WHERE a.id is NULL AND p.id = ?
EOS;
    $typestr = 'ii';
    $params = [$conid, $search_string];
} else {
    $searchSql = <<<EOS
SELECT p.id, first_name, last_name, badge_name, email_addr
FROM perinfo p
LEFT OUTER JOIN atcon_user a ON (a.perid = p.id and a.conid = ?)
WHERE a.id is NULL AND
    LOWER(TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name), ' +', ' '))) LIKE ? OR
    LOWER(TRIM(p.badge_name) LIKE ? OR LOWER(TRIM(p.email_addr)) LIKE ?)
ORDER BY first_name, last_name;
EOS;
    $search_string = '%' . str_replace(' ', '%', $search_string) . '%';
    $typestr = 'isss';
    $params = [$conid, $search_string, $search_string, $search_string];
}

$res = dbSafeQuery($searchSql, $typestr, $params);
if (!$res) {
    ajaxSuccess([
        'args' => $_POST,
        'query' => $searchSql,
        'error' => 'query failed']);
    exit();
}
$results = [];
while ($row = $res->fetch_assoc()) {
    $results[] = $row;
}
$res->free();

$response['data'] = $results;
$response['rows'] = sizeof($results);
$response['message'] = $response['rows'] . " matching users found";
ajaxSuccess($response);
