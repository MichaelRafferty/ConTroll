<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "people";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if ((!array_key_exists('ajax_request_action', $_POST)) || $_POST['ajax_request_action'] != 'additional' ||
    (!array_key_exists('additionalStr', $_POST))) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$user_perid = $_SESSION['user_perid'];
$findPattern = $_POST['additionalStr'];
if ($findPattern == NULL || $findPattern == '') {
    $response['error'] = 'The search pattern cannot be empty.';
    ajaxSuccess($response);
    exit();
}

$con_conf = get_conf('con');
$conid = $con_conf['id'];
$limit = 50;

if (is_numeric($findPattern)) {
    // this is a perid match
    $mQ = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalname, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, p.banned, 
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
    GROUP_CONCAT(DISTINCT TRIM(CONCAT(CASE WHEN m.conid = ? THEN '' ELSE m.conid END, ' ', m.label)) ORDER BY m.id SEPARATOR ', ') AS regs
FROM perinfo p
LEFT OUTER JOIN badgeList b ON (p.id = b.perid AND b.conid = ? AND b.user_perid = ?)
LEFT OUTER JOIN perinfo mp ON (p.managedBy = mp.id)
LEFT OUTER JOIN reg r ON (r.perid = p.id)
LEFT OUTER JOIN memList m ON (r.memId = m.id AND m.conid in (?, ?))
WHERE p.id = ?
GROUP BY p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalname, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, 
    p.creation_date, p.update_date, p.active, p.banned, p.open_notes, p.admin_notes,
    p.managedBy, p.managedByNew, p.lastverified, p.managedreason, phoneCheck, fullName, manager, managerId
EOS;
    $mR = dbSafeQuery($mQ, 'iiiiii', array($conid, $conid, $user_perid, $conid, $conid + 1, $findPattern));
} else {
    // this is a pattern match
    $findPattern = '%' . strtolower(str_replace(' ', '%', $findPattern)) . '%';
    $notMerge = " (NOT (p.first_name = 'Merged' AND p.middle_name = 'into')) AND p.banned = 'N'";
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
LEFT OUTER JOIN badgeList b ON (p.id = b.perid AND b.conid = ? AND b.user_perid = ?)
LEFT OUTER JOIN perinfo mp ON (p.managedBy = mp.id)
LEFT OUTER JOIN reg r ON (r.perid = p.id)
LEFT OUTER JOIN memList m ON (r.memId = m.id AND m.conid in (?, ?))
WHERE $notMerge
GROUP BY p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalname, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, p.banned, 
    p.creation_date, p.update_date, p.active, p.open_notes,
    p.managedBy, p.managedByNew, p.lastverified, p.managedreason, phoneCheck, fullName, manager, managerId
)
SELECT *, CASE
    WHEN LOWER(p.legalname) LIKE ? THEN 850
    WHEN LOWER(p.badge_name) LIKE ? THEN 840
    WHEN LOWER(p.address) LIKE ? THEN 600
    WHEN LOWER(p.addr_2) LIKE ? THEN 550
    WHEN LOWER(p.email_addr) LIKE ? THEN 700
    WHEN LOWER(CONCAT(p.first_name, ' ', p.last_name)) LIKE ? THEN 830
    WHEN LOWER(CONCAT(p.last_name, ' ', p.first_name)) LIKE ? THEN 820
    WHEN LOWER(CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name, ' ', p.suffix)) LIKE ? THEN 860
    WHEN LOWER(p.fullName) LIKE ? THEN 900
    ELSE 0
END AS priority
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
ORDER BY priority DESC, p.last_name, p.first_name, p.id
LIMIT $limit;
EOS;
    $mR = dbSafeQuery($mQ, 'iiiiissssssssssssssssss',
        array ($conid, $conid, $user_perid, $conid, $conid + 1,
           $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern,
           $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern, $findPattern));
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

$response['additional'] = $matches;

// and their policies
$matchPolicies = [];
if (count($matches ) > 0) {
    $pidInStr = implode(',', $pids);
    $mQ = <<<EOS
SELECT perid, policy, response
FROM memberPolicies
WHERE conid = ? AND perid in ($pidInStr);
EOS;

    $mR = dbSafeQuery($mQ, 'i', array ($conid));
    if ($mR === false) {
        $response['error'] = 'Select potential match policies failed';
        ajaxSuccess($response);
    }

    while ($row = $mR->fetch_assoc()) {
        $matchPolicies[$row['perid']][$row['policy']] = $row['response'];
    }
    $mR->free();
}

$response['additionalPolicies'] = $matchPolicies;

if (count($matches) < $limit)
    $response['success'] = count($matches) . ' potential additional matches found';
else
    $response['success'] = "Too many records were matched, only the first $limit additional matches returned";

ajaxSuccess($response);
?>
