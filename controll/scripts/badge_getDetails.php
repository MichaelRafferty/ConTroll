<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "search";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if ((!array_key_exists('type', $_POST)) || $_POST['type'] != 'details') {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$perid = $_POST['perid'];
if ($perid == '' || is_numeric($perid) == false) {
    $response['error'] = 'The person id cannot be empty or non numeric.';
    ajaxSuccess($response);
    exit();
}

$con_conf = get_conf('con');
$conid = $con_conf['id'];

// get the people's full details
$mQ = <<<EOS
WITH memAge AS (
    SELECT p.id, MAX(m.memAge) AS memAgeType
    FROM perinfo p
    LEFT OUTER JOIN reg r on p.id = r.perid AND r.conid = ?
    LEFT OUTER JOIN memList m on r.memId = m.id
    WHERE m.memAge != 'all'
    GROUP BY p.id
)
SELECT p.id, email_addr, badge_name, badgeNameL2, legalName, pronouns, phone, first_name, middle_name, last_name, suffix,
    address, addr_2, city, state, zip, country, currentAgeType, currentAgeConid,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName, map.memAgeType
FROM perinfo p
LEFT OUTER JOIN memAge map ON p.id = map.id
WHERE p.id = ?
EOS;

$mR = dbSafeQuery($mQ, 'ii', array($conid, $perid));
if ($mR === false) {
    $response['error'] = 'Select managed by failed';
    ajaxSuccess($response);
    return;
}

$person= null;
while ($row = $mR->fetch_assoc()) {
    $row['badgename'] = badgeNameDefault($row['badge_name'], $row['badgeNameL2'], $row['first_name'], $row['last_name']);
    $person = $row;
}
$mR->free();
$response['person'] = $person;

// get the interests values
$mQ = <<<EOS
SELECT id, perid, conid, interest, interested, notifyDate, csvDate, createDate, updateDate, updateBy
FROM memberInterests
WHERE perid = ? and conid = ?;
EOS;
$mR = dbSafeQuery($mQ, 'ii', array($perid, $conid));
if ($mR === false) {
    $response['error'] = 'Select interests failed';
    ajaxSuccess($response);
    return;
}

$interests= [];
$iQ = <<<EOS
SELECT i.interest, i.description, i.sortOrder, m.interested, m.id
FROM interests i
LEFT OUTER JOIN memberInterests m ON m.perid = ? AND m.interest = i.interest AND conid = ?
WHERE i.active = 'Y'
ORDER BY i.sortOrder
EOS;
$iR = dbSafeQuery($iQ, 'ii', array($perid, $conid));
if ($iR !== false) {
    while ($row = $iR->fetch_assoc()) {
        $interests[$row['interest']] = $row;
    }
    $iR->free();
}
$response['interests'] = $interests;

// get the policies
$policies = [];
$pQ = <<<EOS
SELECT p.policy, p.prompt, p.description, p.required, p.defaultValue, p.sortOrder, m.id, m.perid, m.conid, m.newperid, m.response
FROM policies p
LEFT OUTER JOIN memberPolicies m ON p.policy = m.policy AND m.perid = ? AND m.conid = ?
WHERE p.active = 'Y'
ORDER BY p.sortOrder;
EOS;
$pR = dbSafeQuery($pQ, 'ii', array($perid, $conid));
if ($pR !== false) {
    while ($row = $pR->fetch_assoc()) {
        $policies[] = $row;
    }
    $pR->free();
}
$response['policies'] = $policies;

ajaxSuccess($response);
