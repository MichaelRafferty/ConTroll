<?php
global $db_ini;

require_once '../lib/base.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'reg_staff';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid = $con['id'];

if (!isset($_POST) || !isset($_POST['name_search'])) {
    $response['error'] = 'Missing Information';
    ajaxSuccess($response);
    exit();
}
// findRecord:
// load all perinfo/reg records matching the search string or unpaid if that flag is passed
$name_search = $_POST['name_search'];
$response['name_search'] = $name_search;

$limit = 99999999;
if (is_numeric($name_search)) {
//
// this is perid
//
    $searchSQLP = <<<EOS
WITH regcnt AS (
    SELECT p.id, COUNT(r.id) as regcnt, GROUP_CONCAT(m.label SEPARATOR ', ') AS regs
    FROM perinfo p
    LEFT OUTER JOIN reg r ON (r.perid = p.id AND r.conid = ?)
    LEFT OUTER JOIN memList m ON (r.memId = m.id)
    WHERE p.id = ?
    GROUP BY p.id
)
SELECT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name, p.address as address_1, p.addr_2 as address_2,
    p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone, p.share_reg_ok, p.contact_ok, p.active, p.banned,
    CASE 
        WHEN p.last_name, != '' THEN
            TRIM(REGEXP_REPLACE(CONCAT(p.last_name, ', ', CONCAT_WS(' ', p.first_name, p.middle_name, p.suffix, '')), '  *', ' ')) 
        ELSE TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.suffix), '  *', ' ')) 
    END AS fullName,
    r.regcnt, r.regs
FROM regcnt r
JOIN perinfo p ON (p.id = r.id)
ORDER BY last_name, first_name;
EOS;
    //web_error_log($searchSQLP);
    $rp = dbSafeQuery($searchSQLP, 'ii', array($conid, $name_search));
} else {
//
// this is the string search portion as the field is alphanumeric
//
    // name match
    $limit = 50; // only return 50 people's memberships
    $name_search = '%' . preg_replace('/ +/', '%', $name_search) . '%';
    //web_error_log("match string: $name_search");
    $searchSQLP = <<<EOS
WITH regcnt AS (
    SELECT p.id, COUNT(r.id) as regcnt, GROUP_CONCAT(m.label SEPARATOR ', ') AS regs
    FROM perinfo p
    LEFT OUTER JOIN reg r ON (r.perid = p.id AND r.conid = ?)
    LEFT OUTER JOIN memList m ON (r.memId = m.id)
    WHERE (LOWER(TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name), '  *', ' '))) LIKE ? OR
    LOWER(legalName) LIKE ? OR LOWER(badge_name) LIKE ? OR LOWER(email_addr) LIKE ? OR LOWER(address) LIKE ? OR LOWER(addr_2) LIKE ?)
    GROUP BY p.id
)
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name,  p.suffix, p.badge_name, p.address as address_1, p.addr_2 as address_2,
    p.city, p.state, p.zip, as postal_code, p.country, p.email_addr, p.phone, p.share_reg_ok, p.contact_ok, p.active, p.banned,
    CASE  
        WHEN last_name != '' THEN TRIM(REGEXP_REPLACE(CONCAT(p.last_name, ', ', CONCAT_WS(' ', p.first_name, p.middle_name, p.suffix)), '  *', ' ')) 
        ELSE TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.suffix), '  *', ' '))  
    END AS fullName,      
    r.regcnt, r.regs
FROM regcnt r
JOIN perinfo p ON (p.id = r.id)
WHERE p.first_name != 'Merged' AND p.middle_name != 'into'
ORDER BY last_name, first_name LIMIT $limit;
EOS;
    $rp = dbSafeQuery($searchSQLP, 'isssss', array($conid, $name_search, $name_search, $name_search, $name_search, $name_search));
}

$perinfo = [];
$num_rows = $rp->num_rows;
while ($l = $rp->fetch_assoc()) {
    $perinfo[] = $l;
}
$response['perinfo'] = $perinfo;
if ($num_rows >= $limit) {
    $response['warn'] = "$num_rows memberships found, limited to $limit, use different search criteria to refine your search.";
} else {
    $response['message'] = "$num_rows memberships found";
}
$rp->free();
ajaxSuccess($response);
