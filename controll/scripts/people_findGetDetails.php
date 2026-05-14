<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'search';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
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
    $response['error'] = 'The persomn id cannot be empty or non numeric.';
    ajaxSuccess($response);
    exit();
}

$con_conf = get_conf('con');
$conid = $con_conf['id'];

// get the interests and member interests values
$mQ = <<<EOS
SELECT m.id, m.perid, m.conid, m.interest, m.interested, m.notifyDate, m.csvDate, m.createDate, m.updateDate, m.updateBy, m.notes,
       i.notesPrompt, i.endDate
FROM memberInterests m
JOIN interests i ON i.interest = m.interest
WHERE perid = ? and conid = ?;
EOS;
$mR = dbSafeQuery($mQ, 'ii', array($perid, $conid));
if ($mR === false) {
    $response['error'] = 'Select interests failed';
    ajaxSuccess($response);
    return;
}

$interests= [];
if ($mR !== false) {
    while ($row = $mR->fetch_assoc()) {
        $interests[] = $row;
    }
    $mR->free();
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

// get the convention roles
if (getConfValue('con', 'conRoles', 0) == 1) {
    $cQ = <<<EOS
SELECT mc.id, c.conRole, c.description, c.memLabel, IFNULL(mc.assigned, 'N') AS assigned
FROM conRoles c
LEFT OUTER JOIN memberConRoles mc ON mc.conRole = c.conRole AND mc.perid = ? AND mc.conid = ?
WHERE c.active = 'Y'
EOS;
    $conRoles = [];
    $cR = dbSafeQuery($cQ, 'ii', array($perid, $conid));
    if ($cR !== false) {
        while ($row = $cR->fetch_assoc()) {
            $conRoles[] = $row;
        }
        $cR->free();
    }
    $response['conroles'] = $conRoles;
}

// get the people managed
$mQ = <<<EOS
SELECT '' AS type, id, email_addr, badge_name, badgeNameL2, legalName, phone, first_name, last_name,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName
FROM perinfo p
WHERE managedBy = ?
UNION
SELECT 'n' AS type, id, email_addr, badge_name, badgeNameL2, legalName, phone, first_name, last_name,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName
FROM newperson p
WHERE managedBy = ? AND p.perid IS NULL
EOS;

$mR = dbSafeQuery($mQ, 'ii', array($perid, $perid));
if ($mR === false) {
    $response['error'] = 'Select managed by failed';
    ajaxSuccess($response);
    return;
}

$managed= [];
while ($row = $mR->fetch_assoc()) {
    $row['badgename'] = badgeNameDefault($row['badge_name'], $row['badgeNameL2'], $row['first_name'], $row['last_name']);
    $managed[] = $row;
}
$mR->free();
$response['managed'] = $managed;

ajaxSuccess($response);
