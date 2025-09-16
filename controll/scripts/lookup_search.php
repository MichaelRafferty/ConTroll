<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "lookup";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('action', $_POST) && array_key_exists('pattern', $_POST))) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$findPattern = $_POST['pattern'];
if ($findPattern == NULL || $findPattern == '') {
    $response['error'] = 'The search pattern cannot be empty.';
    ajaxSuccess($response);
    exit();
}

$con_conf = get_conf('con');
$conid = $con_conf['id'];
$limit = 100;

if (is_numeric($findPattern)) {
    // this is a perid/transaction match
    $mQ = <<<EOS
SELECT p.id AS perid, p.email_addr, p.badge_name, p.first_name, p.middle_name, p.last_name,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName,
    IFNULL(r.complete_trans, r.create_trans) AS tid, 
    CASE 
        WHEN r.status != 'paid' THEN ''
        WHEN r.complete_trans = t2.id THEN t2.create_date
        WHEN r.complete_trans = t1.id THEN t1.create_date
        ELSE ''
    END AS paidDate, r.price, r.paid, r.status, r.create_date, r.change_date, m.label, pm.id AS managerId,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', pm.first_name, pm.middle_name, pm.last_name, pm.suffix), '  *', ' ')) AS managerName
FROM perinfo p
JOIN reg r ON (r.perid = p.id AND r.status IN ('paid', 'unpaid', 'plan'))
JOIN memList m ON (r.memId = m.id AND m.conid in (?, ?))
LEFT OUTER JOIN transaction t1 ON (r.create_trans = t1.id)
LEFT OUTER JOIN transaction t2 ON (r.complete_trans = t2.id)
LEFT OUTER JOIN perinfo pm ON (p.managedBy = pm.id)
WHERE p.id = ?
UNION 
SELECT p.id AS perid, p.email_addr, p.badge_name, p.first_name, p.middle_name, p.last_name,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName,
    IFNULL(r.complete_trans, r.create_trans) AS tid, 
    CASE 
        WHEN r.status != 'paid' THEN ''
        WHEN r.complete_trans = t2.id THEN t2.create_date
        WHEN r.complete_trans = t1.id THEN t1.create_date
        ELSE ''
    END AS paidDate, r.price, r.paid, r.status, r.create_date, r.change_date, m.label, pm.id AS managerId,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', pm.first_name, pm.middle_name, pm.last_name, pm.suffix), '  *', ' ')) AS managerName
FROM reg r
JOIN perinfo p ON (r.perid = p.id)
JOIN memList m ON (r.memId = m.id AND m.conid in (?, ?))
LEFT OUTER JOIN transaction t1 ON (r.create_trans = t1.id)
LEFT OUTER JOIN transaction t2 ON (r.complete_trans = t2.id)
LEFT OUTER JOIN perinfo pm ON (p.managedBy = pm.id)
WHERE r.create_trans = ? or r.complete_trans = ?;
EOS;
    $typestr = 'iiiiiii';
    $valArray = array($conid, $conid, $findPattern, $conid, $conid + 1, $findPattern, $findPattern);
    $mR = dbSafeQuery($mQ, $typestr, $valArray);
} else {
    // this is a pattern match
    $findPattern = '%' . strtolower(str_replace(' ', '%', $findPattern)) . '%';
    $notMerge = " (NOT (first_name = 'Merged' AND middle_name = 'into')) AND banned = 'N'";
    // does anyone match this pattern?
    $mQ = <<<EOS
WITH per AS (
    SELECT id, first_name, last_name, middle_name, suffix, legalName, badge_name, address, addr_2, email_addr,
        TRIM(REGEXP_REPLACE(CONCAT_WS(' ', first_name, middle_name, last_name, suffix), '  *', ' ')) AS fullName
    FROM perinfo
    WHERE $notMerge
), perids AS (
    SELECT id
    FROM per p
    WHERE
        (LOWER(p.legalName) LIKE ?
        OR LOWER(p.badge_name) LIKE ?
        OR LOWER(p.address) LIKE ?
        OR LOWER(p.addr_2) LIKE ?
        OR LOWER(p.email_addr) LIKE ?
        OR LOWER(CONCAT(p.first_name, ' ', p.last_name)) LIKE ?
        OR LOWER(CONCAT(p.last_name, ' ', p.first_name)) LIKE ?
        OR LOWER(p.fullName) LIKE ?)
)
SELECT p.id AS perid, p.email_addr, p.badge_name, p.legalName, p.first_name, p.middle_name, p.last_name,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName,
    IFNULL(r.complete_trans, r.create_trans) AS tid, 
    CASE 
        WHEN r.status != 'paid' THEN ''
        WHEN r.complete_trans = t2.id THEN t2.create_date
        WHEN r.complete_trans = t1.id THEN t1.create_date
        ELSE ''
    END AS paidDate, r.price, r.paid, r.status, r.create_date, r.change_date, m.label, pm.id AS managerId,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', pm.first_name, pm.middle_name, pm.last_name, pm.suffix), '  *', ' ')) AS managerName
FROM perids i
JOIN perinfo p ON (p.id = i.id)
JOIN reg r ON (r.perid = p.id AND r.status IN ('paid', 'unpaid', 'plan'))
JOIN memList m ON (r.memId = m.id AND m.conid in (?, ?))
LEFT OUTER JOIN transaction t1 ON (r.create_trans = t1.id)
LEFT OUTER JOIN transaction t2 ON (r.complete_trans = t2.id)
LEFT OUTER JOIN perinfo pm ON (p.managedBy = pm.id)
ORDER BY p.last_name, p.first_name, p.id
LIMIT $limit;
EOS;

    $typestr = 'ssssssssii';
    $valArray = array ($findPattern, $findPattern, $findPattern, $findPattern,
                       $findPattern, $findPattern, $findPattern, $findPattern, $conid, $conid +1);

    $mR = dbSafeQuery($mQ, $typestr, $valArray);
}
if ($mR === false) {
    $response['error'] = 'Select matching pattern failed';
    ajaxSuccess($response);
    return;
}

$matches= [];
while ($match = $mR->fetch_assoc()) {
    $matches[] = $match;
}
$mR->free();
$response['matches'] = $matches;

if (count($matches) < $limit)
    $response['success'] = count($matches) . ' potential matches found';
else
    $response['success'] = "Too many records were matched, only the first $limit potential matches returned";

ajaxSuccess($response);
