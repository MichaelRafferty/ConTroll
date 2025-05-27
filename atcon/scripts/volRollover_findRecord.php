<?php

// library AJAX Processor: volRollover_findRecord.php
// Balticon Registration System
// Author: Syd Weinstein
// search for matching perinfo/reg records for the query

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
if ($ajax_request_action != 'findRecord') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// findRecord:
// load all perinfo/reg records matching the search string or unpaid if that flag is passed
$name_search = $_POST['name_search'];
$rollover_memId = $_POST['rollover_memId'];
$response['name_search'] = $name_search;

$response['elig'] = $con['rollover_eligible'];

$limit = 99999999;
if (is_numeric($name_search)) {
    //
    // this is perid
    //
    $searchSQL = <<<EOS
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
    p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned, 
    CASE 
        WHEN p.last_name != '' THEN TRIM(REGEXP_REPLACE(CONCAT(p.last_name, ', ', CONCAT_WS(' ', p.first_name, p.middle_name, p.suffix)), '  *', ' '))
        ELSE TRIM(REGEXP_REPLACE(CONCAT_WS(p.first_name, p.middle_name, p.suffix), '  *', ' '))
    END AS fullname,
    p.open_notes, r.id AS regid, m.label, rn.id AS roll_regid, mn.shortname,
    CASE 
        WHEN m.memCategory is null THEN 'no membership'
        WHEN m.memCategory in ({$con['rollover_eligible']}) THEN 'eligible'
        ELSE m.memCategory
    END AS memCategory
FROM perinfo p
JOIN reg r ON (r.perid = p.id)
LEFT OUTER JOIN reg rn ON (rn.perid = p.id AND rn.conid = ?)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN memLabel mn ON (rn.memId = mn.id)
WHERE p.id = ? AND r.conid = ?
ORDER BY r.id;
EOS;
    //web_error_log($searchSQLM);
    $r = dbSafeQuery($searchSQL, 'iii', array($conid + 1, $name_search, $conid));
} else {
//
// this is the string search portion as the field is alphanumeric
//
    // name match
    $limit = 50; // only return 50 people's memberships
    $name_search = '%' . preg_replace('/ +/', '%', $name_search) . '%';
    //web_error_log("match string: $name_search");
    $searchSQL = <<<EOS
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
    p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    CASE
        WHEN IFNULL(p.last_name, '') != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(p.last_name, ', ', p.first_name, ' ', p.middle_name, ' ', p.suffix), '  *', ' '))
        ELSE
            TRIM(REGEXP_REPLACE(CONCAT(p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' '))
        END AS fullname
    p.open_notes, r.id AS regid, m.label, rn.id AS roll_regid, mn.shortname,
    CASE
        WHEN m.memCategory is null THEN 'no membership'
        WHEN m.memCategory in ({$con['rollover_eligible']}) THEN 'eligible'
        ELSE m.memCategory
    END AS memCategory
FROM perinfo p
JOIN reg r ON (r.perid = p.id)
LEFT OUTER JOIN reg rn ON (rn.perid = p.id AND rn.conid = ?)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN memLabel mn ON (rn.memId = mn.id)
WHERE r.conid = ? AND (
    LOWER(TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name), '  *', ' '))) LIKE ? OR
    LOWER(TRIM(p.badge_name) LIKE ? OR LOWER(TRIM(p.email_addr)) LIKE ?)
ORDER BY last_name, first_name
LIMIT $limit;
EOS;
    $r = dbSafeQuery($searchSQL, 'iisss', array($conid + 1, $conid, $name_search, $name_search, $name_search));

}
// now process the search results
$perinfo = [];
$index = 0;
$perids = [];
$num_rows = $r->num_rows;
while ($l = $r->fetch_assoc()) {
    if (!array_key_exists($l['perid'], $perids)) {
        $perids[$l['perid']] = $index;
        $l['index'] = $index;
        $perinfo[] = $l;
    }
    $index++;
}
$response['perinfo'] = $perinfo;
if ($num_rows >= $limit) {
    $response['warn'] = "$num_rows memberships found, limited to $limit, use different search criteria to refine your search.";
} else {
    $response['message'] = "$num_rows memberships found";
}
$r->free();
ajaxSuccess($response);
