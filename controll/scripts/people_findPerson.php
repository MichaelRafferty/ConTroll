<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "search";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('type', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

if ($_POST['type'] == 'find' || $_POST['type'] == 'manager' ||$_POST['type'] == 'managed')
    $searchType = $_POST['type'];
else {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$user_perid = $_SESSION['user_perid'];
$findPattern = $_POST['pattern'];
if ($findPattern == NULL || $findPattern == '') {
    $response['error'] = 'The search pattern cannot be empty.';
    ajaxSuccess($response);
    exit();
}

$excludeFree = '';
$excludeJoin = '';
if (array_key_exists('excludeFree', $_POST)) {
    $excludeJoin = " LEFT OUTER JOIN badgeList b ON (p.id = b.perid AND b.conid = ? AND b.user_perid = ?)";
    $excludeFree = " AND b.perid IS NULL";
}

$con_conf = get_conf('con');
$conid = $con_conf['id'];
$limit = 50;

if (is_numeric($findPattern)) {
    // this is a perid match
    $mQ = <<<EOS
WITH perids AS (
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalname, p.pronouns, 
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,  
        p.creation_date, p.update_date, p.active, p.banned, p.open_notes, p.admin_notes,
        p.managedBy, p.managedByNew, p.lastverified, p.managedreason,
        REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(IFNULL(p.phone, ''))), ')', ''), '(', ''), '-', ''), ' ', '') AS phoneCheck,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  
            IFNULL(p.suffix, '')), '  *', ' ')) AS fullName,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.address, ''),' ', IFNULL(p.addr_2, ''), ' ', IFNULL(p.city, ''), ' ',
            IFNULL(p.state, ''), ' ', IFNULL(p.zip, ''), ' ', IFNULL(p.country, '')), '  *', ' ')) AS fullAddr,
        CASE
            WHEN mp.id IS NOT NULL THEN 
                TRIM(REGEXP_REPLACE(CONCAT(IFNULL(mp.first_name, ''),' ', IFNULL(mp.middle_name, ''), ' ',
                    IFNULL(mp.last_name, ''), ' ', IFNULL(mp.suffix, '')), '  *', ' ')) 
            ELSE ''
        END AS manager,
        CASE
            WHEN mp.id IS NOT NULL THEN mp.id
            ELSE NULL
        END AS managerId,
        GROUP_CONCAT(DISTINCT TRIM(CONCAT(CASE WHEN m.conid = ? THEN '' ELSE m.conid END, ' ', m.label)) ORDER BY m.id SEPARATOR ', ') AS memberships
    FROM perinfo p
    $excludeJoin
    LEFT OUTER JOIN perinfo mp ON (p.managedBy = mp.id)
    LEFT OUTER JOIN reg r ON (r.perid = p.id)
    LEFT OUTER JOIN memList m ON (r.memId = m.id AND m.conid in (?, ?))
    WHERE p.id = ? $excludeFree
    GROUP BY p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalname, p.pronouns, 
        p.address, p.addr_2, p.city, p.state, p.zip, p.country, 
        p.creation_date, p.update_date, p.active, p.banned, p.open_notes, p.admin_notes,
        p.managedBy, p.managedByNew, p.lastverified, p.managedreason, phoneCheck, fullName, manager, managerId
), his AS (
    SELECT p.id, count(h.historyId) AS historyCount
    FROM perids p
    LEFT OUTER JOIN perinfoHistory h ON (h.id = p.id)
    GROUP BY p.id
)
SELECT p.*, his.historyCount
FROM perids p
LEFT OUTER JOIN his ON (p.id = his.id);
EOS;
    if ($excludeJoin != '') {
        $typestr = 'iiiiii';
        $valArray = array($conid, $conid, $user_perid, $conid, $conid + 1, $findPattern);
    } else {
        $typestr = 'iiii';
        $valArray = array($conid, $conid, $conid + 1, $findPattern);
    }
    $mR = dbSafeQuery($mQ, $typestr, $valArray);
} else {
    // this is a pattern match
    $findPattern = '%' . strtolower(str_replace(' ', '%', $findPattern)) . '%';
    $notMerge = '';
    if ($searchType != 'find') {
        $notMerge = " AND (NOT (p.first_name = 'Merged' AND p.middle_name = 'into')) AND p.banned = 'N'";
    }
    // does anyone match this pattern?
    $mQ = <<<EOS
WITH per AS (
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalname, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    p.creation_date, p.update_date,  p.active, p.banned, p.open_notes, p.admin_notes,
    p.managedBy, p.managedByNew, p.lastverified, p.managedreason,
    REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(IFNULL(p.phone, ''))), ')', ''), '(', ''), '-', ''), ' ', '') AS phoneCheck,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  
        IFNULL(p.suffix, '')), '  *', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.address, ''),' ', IFNULL(p.addr_2, ''), ' ', IFNULL(p.city, ''), ' ',
        IFNULL(p.state, ''), ' ', IFNULL(p.zip, ''), ' ', IFNULL(p.country, '')), '  *', ' ')) AS fullAddr,
    CASE
        WHEN mp.id IS NOT NULL THEN 
            TRIM(REGEXP_REPLACE(CONCAT(IFNULL(mp.first_name, ''),' ', IFNULL(mp.middle_name, ''), ' ',
                IFNULL(mp.last_name, ''), ' ', IFNULL(mp.suffix, '')), '  *', ' ')) 
        ELSE ''
    END AS manager,
    CASE
        WHEN mp.id IS NOT NULL THEN mp.id
        ELSE NULL
    END AS managerId,
    GROUP_CONCAT(DISTINCT TRIM(CONCAT(CASE WHEN m.conid = ? THEN '' ELSE m.conid END, ' ', m.label)) ORDER BY m.id SEPARATOR ', ') AS memberships
FROM perinfo p
$excludeJoin
LEFT OUTER JOIN perinfo mp ON (p.managedBy = mp.id)
LEFT OUTER JOIN reg r ON (r.perid = p.id)
LEFT OUTER JOIN memList m ON (r.memId = m.id AND m.conid in (?, ?))
WHERE 1=1  $excludeFree $notMerge
GROUP BY p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalname, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, p.banned, 
    p.creation_date, p.update_date, p.active, p.open_notes,
    p.managedBy, p.managedByNew, p.lastverified, p.managedreason, phoneCheck, fullName, manager, managerId
), perids AS (
    SELECT *
    FROM per p
    WHERE
        (LOWER(p.legalname) LIKE ?
        OR LOWER(p.badge_name) LIKE ?
        OR LOWER(p.address) LIKE ?
        OR LOWER(p.addr_2) LIKE ?
        OR LOWER(p.email_addr) LIKE ?
        OR LOWER(CONCAT(p.first_name, ' ', p.last_name)) LIKE ?
        OR LOWER(CONCAT(p.last_name, ' ', p.first_name)) LIKE ?
        OR LOWER(CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name, ' ', p.suffix)) LIKE ?
        OR LOWER(p.fullName) LIKE ?)
    ORDER BY p.last_name, p.first_name, p.id
), his AS (
    SELECT p.id, count(h.historyId) AS historyCount
    FROM perids p
    LEFT OUTER JOIN perinfoHistory h ON (h.id = p.id)
    GROUP BY p.id
)
SELECT p.*, his.historyCount
FROM perids p
LEFT OUTER JOIN his ON (p.id = his.id)
LIMIT $limit;
EOS;
    if ($excludeJoin != '') {
        $typestr = 'iiiiisssssssss';
        $valArray = array ($conid, $conid, $user_perid, $conid, $conid + 1, $findPattern, $findPattern, $findPattern, $findPattern,
                           $findPattern, $findPattern, $findPattern, $findPattern, $findPattern);
    } else {
        $typestr = 'iiisssssssss';
        $valArray = array ($conid, $conid, $conid + 1, $findPattern, $findPattern, $findPattern, $findPattern,
                           $findPattern, $findPattern, $findPattern, $findPattern, $findPattern);
    }
    $mR = dbSafeQuery($mQ, $typestr, $valArray);
}
if ($mR === false) {
    $response['error'] = 'Select people matching pattern failed';
    ajaxSuccess($response);
    return;
}

$pids = [];
$matches= [];
while ($match = $mR->fetch_assoc()) {
    $matches[] = $match;
    $pids[] = $match['id'];
}
$mR->free();

$response['matches'] = $matches;
if (count($matches) < 50)
    $response['success'] = count($matches) . ' potential matches found';
else
    $response['success'] = "Too many records were matched, only the first $limit potential matches returned";

ajaxSuccess($response);
?>
