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

if ((!array_key_exists('ajax_request_action', $_POST)) || $_POST['ajax_request_action'] != 'match' ||
        (!array_key_exists('newperid', $_POST))) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$newperid = $_POST['newperid'];
$con_conf = get_conf('con');
$conid = $con_conf['id'];

// first fetch the details on the new people we are matching against this person
$nQ = <<<EOS
WITH regs AS (
	SELECT ? AS id, GROUP_CONCAT(DISTINCT m.label ORDER BY m.id SEPARATOR ',') AS regs
    FROM reg r
	LEFT OUTER JOIN memList m ON (r.memId = m.id)
	WHERE r.newperid = ? AND r.conid = ?
)
SELECT n.*, 'Y' as active, 'N' AS banned, r.regs, 
    TRIM(REGEXP_REPLACE(
        CONCAT(IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.last_name, ''), ' ',  IFNULL(n.suffix, '')),
        '  *', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(
    CONCAT(IFNULL(n.address, ''),' ', IFNULL(n.addr_2, ''), ' ', IFNULL(n.city, ''), ' ',  IFNULL(n.state, ''), ' ', IFNULL(n.zip, ''),
            ' ', IFNULL(n.country, '')),
        '  *', ' ')) AS fullAddr,
    TRIM(REGEXP_REPLACE(
        CONCAT(IFNULL(m.first_name, ''),' ', IFNULL(m.middle_name, ''), ' ', IFNULL(m.last_name, ''), ' ',  IFNULL(m.suffix, '')),
        '  *', ' ')) AS manager, IFNULL(n.managedBy, n.managedByNew) AS managerId
FROM newperson n
LEFT OUTER JOIN newperson mn ON n.managedByNew = mn.id
LEFT OUTER JOIN perinfo m ON n.managedBy = m.id
LEFT OUTER JOIN regs r ON r.id = n.id
WHERE n.id = ?;
EOS;

$nR = dbSafeQuery($nQ, 'iiii', array($newperid, $newperid, $conid, $newperid));
if ($nR === false || $nR->num_rows != 1) {
    $response['error'] = 'Select newperson failed';
    ajaxSuccess($response);
}

$newperson = [];
$newperson = $nR->fetch_assoc();
$nR->free();

$response['newperson'] = $newperson;

// now get the policies and responses from that new person
$nQ = <<<EOS
SELECT policy, response
FROM memberPolicies
WHERE conid = ? and newperid = ?;
EOS;

$nR = dbSafeQuery($nQ, 'ii', array($conid, $newperid));
if ($nR === false) {
    $response['error'] = 'Select newperson policies failed';
    ajaxSuccess($response);
}

$npolicies = [];
while ($policy = $nR->fetch_assoc()) {
        $npolicies[$policy['policy']] = $policy['response'];
}
$nR->free();
$response['npolicies'] = $npolicies;

// next is the candidate matches to that newperson
$mQ = <<<EOS
WITH lNew AS (
    SELECT 
		id,
        LOWER(TRIM(IFNULL(first_name, ''))) AS first_name, 
        LOWER(TRIM(IFNULL(last_name, ''))) AS last_name, 
        LOWER(TRIM(IFNULL(middle_name, ''))) AS middle_name,
        LOWER(TRIM(REGEXP_REPLACE(
            CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ',  IFNULL(suffix, '')),
            '  *', ' '))) AS fullName,
        LOWER(TRIM(IFNULL(email_addr, ''))) AS email_addr,
        LOWER(TRIM(IFNULL(badge_name, ''))) AS badge_name,
        LOWER(TRIM(IFNULL(address, ''))) AS address,
        LOWER(TRIM(IFNULL(addr_2, ''))) AS addr_2,
        REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(IFNULL(phone, ''))), ')', ''), '(', ''), '-', ''), ' ', '') AS phone,
        LOWER(TRIM(IFNULL(city, ''))) AS city,
        LOWER(TRIM(IFNULL(state, ''))) AS state,
        LOWER(TRIM(IFNULL(country, ''))) AS country
	FROM newperson
    WHERE id = ?
), lsNew AS (
    SELECT
		id,
        first_name, SOUNDEX(first_name) AS sFirstName,
        last_name, SOUNDEX(last_name) AS sLastName,
        middle_name, SOUNDEX(middle_name) AS sMiddleName, fullName,
        badge_name, SOUNDEX(middle_name) AS sBadgeName,
        email_addr, SOUNDEX(email_addr) AS sEmailAddr,
        address, addr_2, phone, city, state, country
    FROM lNew
), pOld AS (
    SELECT
		id,
        LOWER(TRIM(IFNULL(first_name, ''))) AS first_name, 
        LOWER(TRIM(IFNULL(last_name, ''))) AS last_name, 
        LOWER(TRIM(IFNULL(middle_name, ''))) AS middle_name,
        LOWER(TRIM(REGEXP_REPLACE(
            CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ',  IFNULL(suffix, '')),
            '  *', ' '))) AS fullName,
        LOWER(TRIM(IFNULL(email_addr, ''))) AS email_addr,
        LOWER(TRIM(IFNULL(badge_name, ''))) AS badge_name,
        LOWER(TRIM(IFNULL(address, ''))) AS address,
        LOWER(TRIM(IFNULL(addr_2, ''))) AS addr_2,
        REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(IFNULL(phone, ''))), ')', ''), '(', ''), '-', ''), ' ', '') AS phone,
        LOWER(TRIM(IFNULL(city, ''))) AS city,
        LOWER(TRIM(IFNULL(state, ''))) AS state,
        LOWER(TRIM(IFNULL(country, ''))) AS country
	FROM perinfo
), psOld AS (
	SELECT
		id,
        first_name, SOUNDEX(first_name) AS sFirstName,
        last_name, SOUNDEX(last_name) AS sLastName,
        middle_name, SOUNDEX(middle_name) AS sMiddleName, fullName,
        badge_name, SOUNDEX(middle_name) AS sBadgeName,
        email_addr, SOUNDEX(email_addr) AS sEmailAddr,
        address, addr_2, phone, city, state, country
    FROM pOld
), pids AS (
	SELECT p.id, CASE
	    WHEN p.fullName = n.fullName 
	        AND n.address != '' AND p.address =  n.address
	        AND n.email_addr != '' AND p.email_addr = n.email_addr 
	        AND n.phone != '' AND p.phone = n.phone THEN 900
	    WHEN p.fullName = n.fullName
	        AND n.email_addr != '' AND p.email_addr = n.email_addr 
	        AND n.phone != '' AND p.phone = n.phone THEN 850
	    WHEN p.fullName = n.fullName
	        AND n.email_addr != '' AND p.email_addr = n.email_addr THEN 800
	    WHEN p.fullName = n.fullName 
	        AND n.phone != '' AND p.phone = n.phone THEN 750
	    WHEN p.last_name = n.last_name AND p.first_name like CONCAT(SUBSTRING(n.first_name, 1, 2), '%') 
	        AND n.address != '' AND p.address =  n.address 
	        AND n.email_addr != '' AND p.email_addr = n.email_addr
	        AND n.phone != '' AND p.phone = n.phone THEN 890
        WHEN p.last_name = n.last_name AND p.first_name like CONCAT(SUBSTRING(n.first_name, 1, 2), '%')
            AND n.email_addr != '' AND p.email_addr = n.email_addr 
            AND n.phone != '' AND p.phone = n.phone THEN 840
        WHEN p.last_name = n.last_name AND p.first_name like CONCAT(SUBSTRING(n.first_name, 1, 2), '%')
            AND n.email_addr != '' AND p.email_addr = n.email_addr THEN 790
        WHEN p.last_name = n.last_name AND p.first_name like CONCAT(SUBSTRING(n.first_name, 1, 2), '%') THEN 740
        WHEN p.first_name = n.first_name AND p.last_name like CONCAT(SUBSTRING(n.last_name, 1, 2), '%') 
	        AND n.address != '' AND p.address =  n.address 
	        AND n.email_addr != '' AND p.email_addr = n.email_addr
	        AND n.phone != '' AND p.phone = n.phone THEN 885
        WHEN p.first_name = n.first_name AND p.last_name like CONCAT(SUBSTRING(n.last_name, 1, 2), '%') 
            AND n.email_addr != '' AND p.email_addr = n.email_addr 
            AND n.phone != '' AND p.phone = n.phone THEN 835
        WHEN p.first_name = n.first_name AND p.last_name like CONCAT(SUBSTRING(n.last_name, 1, 2), '%') 
            AND n.email_addr != '' AND p.email_addr = n.email_addr THEN 785
        WHEN p.first_name = n.first_name AND p.last_name like CONCAT(SUBSTRING(n.last_name, 1, 2), '%')  THEN 735
        ELSE 700
    END AS priority
	FROM lsNew n
    JOIN psOld p ON (
           (p.last_name = n.last_name OR p.sLastName = n.sLastName) AND 
            (p.first_name like CONCAT(SUBSTRING(n.first_name, 1, 2), '%') OR p.sFirstName = n.sFirstName) 
        OR (p.first_name = n.first_name OR p.sFirstName = n.sFirstName) AND 
            (p.last_name like CONCAT(SUBSTRING(n.last_name, 1, 2), '%') OR p.sLastName = n.sLastName) 
        OR p.fullName = n.fullName
        )
	UNION DISTINCT SELECT p.id, CASE
	    WHEN n.email_addr != '' AND p.email_addr = n.email_addr
	        AND n.phone != '' AND p.phone = n.phone THEN 650
        WHEN n.email_addr != '' AND p.email_addr = n.email_addr THEN 640
        WHEN n.phone != '' AND p.phone = n.phone THEN 630
        ELSE 600
    END AS priority 
	FROM lsNew n
	JOIN psOld p ON (p.email_addr = n.email_addr AND n.email_addr != '') OR (p.phone = n.phone && n.phone != '')
	UNION DISTINCT SELECT p.id, CASE
	    WHEN n.address != '' AND p.address = n.address THEN 640
	    WHEN n.addr_2 != '' AND p.addr_2 = n.addr_2 THEN 590
	    ELSE 550
	END AS priority
	FROM lsNew n
	JOIN psOld p ON (n.address != '' AND p.address = n.address ) OR (n.addr_2 != '' AND p.addr_2 = n.addr_2) 
), spids AS (
    SELECT id, MAX(priority) AS priority
    FROM pids
    GROUP BY id
),regs AS (
	SELECT p.id, GROUP_CONCAT(DISTINCT m.label ORDER BY m.id SEPARATOR ',') AS regs
	FROM spids p
	LEFT OUTER JOIN reg r on (r.perid = p.id AND r.conid = ?)
	LEFT OUTER JOIN memList m ON (r.memId = m.id)
    GROUP BY p.id
)
SELECT DISTINCT spids.priority, p.*, r.regs,
    TRIM(REGEXP_REPLACE(
        CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  IFNULL(p.suffix, '')),
        '  *', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(
    CONCAT(IFNULL(p.address, ''),' ', IFNULL(p.addr_2, ''), ' ', IFNULL(p.city, ''), ' ',  IFNULL(p.state, ''), ' ', IFNULL(p.zip, ''),
            ' ', IFNULL(p.country, '')),
        '  *', ' ')) AS fullAddr,
    TRIM(REGEXP_REPLACE(
        CONCAT(IFNULL(m.first_name, ''),' ', IFNULL(m.middle_name, ''), ' ', IFNULL(m.last_name, ''), ' ',  IFNULL(m.suffix, '')),
        '  *', ' ')) AS manager, m.id AS managerId
FROM perinfo p
JOIN spids ON p.id = spids.id
LEFT OUTER JOIN regs r ON r.id = p.id
LEFT OUTER JOIN perinfo m ON p.managedBy = m.id
ORDER BY spids.priority DESC, p.last_name, p.first_name;
EOS;

$mR = dbSafeQuery($mQ, 'ii', array($newperid, $conid));
if ($mR === false) {
    $response['error'] = 'Select potential matches failed';
    ajaxSuccess($response);
}

$pids = [];
$matches= [];
while ($match = $mR->fetch_assoc()) {
    $matches[] = $match;
    $pids[] = $match['id'];
}
$mR->free();

$response['matches'] = $matches;

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
$response['matchPolicies'] = $matchPolicies;

$response['success'] = count($matches) . ' potential matches found';

ajaxSuccess($response);
?>
