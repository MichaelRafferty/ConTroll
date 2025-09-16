<?php
// ConTroll Registration System, Copyright 2015-2025, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: admin_searchUsers.php
// Author: Syd Weinstein
// search the perinfo table for matches for adding a user to atcon

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'admin';

$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con=get_conf('con');
$conid= $con['id'];

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'searchUsers') {
    RenderErrorAjax('Invalid calling sequence.');
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
    (LOWER(TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name), ' +', ' '))) LIKE ? OR LOWER(badge_name) LIKE ?);
EOS;
    $search_string = '%' . str_replace(' ', '%', $search_string) . '%';
    $typestr = 'iss';
    $params = [$conid, $search_string, $search_string];
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
