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
$nQ = <<<EOS
WITH regs AS (
	SELECT ? AS id, GROUP_CONCAT(DISTINCT m.label ORDER BY m.id SEPARATOR ',') AS regs
    FROM reg r
	LEFT OUTER JOIN memLabel m ON (r.memId = m.id)
	WHERE r.newperid = ? AND r.conid = ?
)
SELECT n.*, r.regs, 
    TRIM(REGEXP_REPLACE(
        CONCAT(IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.last_name, ''), ' ',  IFNULL(n.suffix, '')),
        '  *', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(
    CONCAT(IFNULL(n.address, ''),' ', IFNULL(n.addr_2, ''), ' ', IFNULL(n.city, ''), ' ',  IFNULL(n.state, ''), ' ', IFNULL(n.zip, ''),
            ' ', IFNULL(n.country, '')),
        '  *', ' ')) AS fullAddr,
    TRIM(REGEXP_REPLACE(
        CONCAT(IFNULL(m.first_name, ''),' ', IFNULL(m.middle_name, ''), ' ', IFNULL(m.last_name, ''), ' ',  IFNULL(m.suffix, '')),
        '  *', ' ')) AS manager
FROM newperson n
LEFT OUTER JOIN newperson mn ON n.managedByNew = mn.id
LEFT OUTER JOIN perinfo m ON n.managedBy = m.id
LEFT OUTER JOIN regs r ON r.id = n.id
WHERE n.id = ?
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

$mQ = <<<EOS
WITH lNew AS (
    SELECT 
		id,
        LOWER(TRIM(IFNULL(first_name, ''))) AS first_name, 
        LOWER(TRIM(IFNULL(last_name, ''))) AS last_name, 
        LOWER(TRIM(IFNULL(middle_name, ''))) AS middle_name,
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
        middle_name, SOUNDEX(middle_name) AS sMiddleName,
        badge_name, SOUNDEX(middle_name) AS sBadgeName,
        email_addr, SOUNDEX(email_addr) AS sEmailAddr,
        address, SOUNDEX(address) AS sAddress,
        addr_2, SOUNDEX(addr_2) AS sAddr_2,
        phone,
        city, SOUNDEX(city) AS sCity,
        state, SOUNDEX(state) AS sState, country
    FROM lNew
), pOld AS (
    SELECT
		id,
        LOWER(TRIM(IFNULL(first_name, ''))) AS first_name, 
        LOWER(TRIM(IFNULL(last_name, ''))) AS last_name, 
        LOWER(TRIM(IFNULL(middle_name, ''))) AS middle_name,
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
        middle_name, SOUNDEX(middle_name) AS sMiddleName,
        badge_name, SOUNDEX(middle_name) AS sBadgeName,
        email_addr, SOUNDEX(email_addr) AS sEmailAddr,
        address, SOUNDEX(address) AS sAddress,
        addr_2, SOUNDEX(addr_2) AS sAddr_2,
        phone,
        city, SOUNDEX(city) AS sCity,
        state, SOUNDEX(state) AS sState, country
    FROM pOld
), pids AS (
	SELECT p.id
	FROM lsNew n
	JOIN psOld p ON (p.last_name = n.last_name OR p.sLastName = n.sLastName) AND 
			(p.first_name like CONCAT(SUBSTRING(n.first_name, 1, 2), '%') OR p.sFirstName = n.sFirstName)
	UNION SELECT p.id
	FROM lsNew n
	JOIN psOld p ON (p.email_addr = n.email_addr AND n.email_addr != '') OR (p.phone = n.phone && n.phone != '')
	UNION SELECT p.id
	FROM lsNew n
	JOIN psOld p ON (n.address != '' AND (p.address = n.address OR p.sAddress = n.sAddress)) OR 
					(n.addr_2 != '' AND (p.addr_2 = n.addr_2 OR p.sAddr_2 = n.sAddr_2)) 
), regs AS (
	SELECT p.id, GROUP_CONCAT(DISTINCT m.label ORDER BY m.id SEPARATOR ',') AS regs
	FROM pids p
	LEFT OUTER JOIN reg r on (r.perid = p.id AND r.conid = ?)
	LEFT OUTER JOIN memLabel m ON (r.memId = m.id)
    GROUP BY p.id
)
SELECT DISTINCT p.*, r.regs, 
    TRIM(REGEXP_REPLACE(
        CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  IFNULL(p.suffix, '')),
        '  *', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(
    CONCAT(IFNULL(p.address, ''),' ', IFNULL(p.addr_2, ''), ' ', IFNULL(p.city, ''), ' ',  IFNULL(p.state, ''), ' ', IFNULL(p.zip, ''),
            ' ', IFNULL(p.country, '')),
        '  *', ' ')) AS fullAddr,
    TRIM(REGEXP_REPLACE(
        CONCAT(IFNULL(m.first_name, ''),' ', IFNULL(m.middle_name, ''), ' ', IFNULL(m.last_name, ''), ' ',  IFNULL(m.suffix, '')),
        '  *', ' ')) AS manager
FROM perinfo p
JOIN pids ON p.id = pids.id
LEFT OUTER JOIN regs r ON r.id = p.id
LEFT OUTER JOIN perinfo m ON p.managedBy = m.id
EOS;

$mR = dbSafeQuery($mQ, 'ii', array($newperid, $conid));
if ($mR === false) {
    $response['error'] = 'Select potential matches failed';
    ajaxSuccess($response);
}

$matches= [];
while ($match = $mR->fetch_assoc()) {
    $matches[] = $match;
}
$mR->free();

$response['matches'] = $matches;
$response['success'] = count($matches) . ' potential matches found';

ajaxSuccess($response);
?>
