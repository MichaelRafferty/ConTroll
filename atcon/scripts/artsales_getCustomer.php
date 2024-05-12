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
if ($ajax_request_action != 'getCustomer') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// findRecord:
// load all perinfo/reg records matching the search string
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
SELECT DISTINCT P.id as perid, IFNULL(P.first_name, '') as first_name, IFNULL(P.middle_name, '') as middle_name, IFNULL(P.last_name, '') as last_name, IFNULL(P.suffix, '') as suffix, IFNULL(P.badge_name, '') as badge_name, IFNULL(P.email_addr, '') as email_addr, IFNULL(P.phone, '') as phone,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(P.last_name, ''), ', ', IFNULL(P.first_name, ''),' ', IFNULL(P.middle_name, ''), ' ', IFNULL(P.suffix,'')), '  *', ' ')) AS fullname
    FROM perinfo P 
    WHERE P.id=?
EOS;
    //web_error_log($findPersonQ);
    $findPersonR = dbSafeQuery($findPersonQ, 'i', array($name_search));
} else {
//
// this is the string search portion as the field is alphanumeric
//
    // name match
    $limit = 50; // only return 50 people's memberships
    $name_search = '%' . preg_replace('/ +/', '%', $name_search) . '%';
    web_error_log("match string: $name_search");
    $findPersonQ = <<<EOS
SELECT DISTINCT P.id as perid, IFNULL(P.first_name, '') as first_name, IFNULL(P.middle_name, '') as middle_name, IFNULL(P.last_name, '') as last_name, IFNULL(P.suffix, '') as suffix, IFNULL(P.badge_name, '') as badge_name, IFNULL(P.email_addr, '') as email_addr, IFNULL(P.phone, '') as phone,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(P.last_name, ''), ', ', IFNULL(P.first_name, ''),' ', IFNULL(P.middle_name, ''), ' ', IFNULL(P.suffix,'')), '  *', ' ')) AS fullname
FROM perinfo P
WHERE 
(LOWER(TRIM(CONCAT_WS(' ', TRIM(CONCAT_WS(' ', IFNULL(first_name, ''), IFNULL(middle_name, ''))), IFNULL(last_name, '')))) LIKE ? OR LOWER(badge_name) LIKE ? OR LOWER(email_addr) LIKE ?)
ORDER BY last_name, first_name LIMIT $limit;
EOS;
    //web_error_log($findPersonQ);
    $findPersonR = dbSafeQuery($findPersonQ, 'sss', array($name_search, $name_search, $name_search));
}

$perinfo = [];
$index = 0;
$perids = [];
$num_rows = $findPersonR->num_rows;
while ($l = $findPersonR->fetch_assoc()) {
    $l['index'] = $index;
    $perinfo[] = $l;
    $perids[$l['perid']] = $index;
    $index++;
}
$response['perinfo'] = $perinfo;
if ($num_rows >= $limit) {
    $response['warn'] = "$num_rows people found, limited to $limit, use different search criteria to refine your search.";
} else {
    $response['message'] = "$num_rows people found";
}
mysqli_free_result($findPersonR);

ajaxSuccess($response);
