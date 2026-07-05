<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'badge';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

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
if ($ajax_request_action != 'loadWatchList') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$user_perid = $authToken->getPerid();
$response['id'] = $authToken->getUserId();
$response['user_perid'] = $user_perid;

$watchQ = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.badgeNameL2, p.legalName, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, p.banned, 
    p.creation_date, p.update_date, p.active, p.banned, p.open_notes, p.admin_notes, p.lastverified,
    REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(IFNULL(p.phone, ''))), ')', ''), '(', ''), '-', ''), ' ', '') AS phoneCheck,
    TRIM(REGEXP_REPLACE(CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name, ' ', p.suffix), ' +', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.address, p.addr_2, p.city, p.state, p.zip, p.country), ' +', ' ')) AS fullAddr,
    GROUP_CONCAT(DISTINCT m.label ORDER BY m.id SEPARATOR ', ') AS memberships, SUM(IF(ra.action='print', 1, 0)) AS printCount, 
    COUNT(IF(m.memCategory = 'freebie', 1, NULL)) AS numFreebie, COUNT(IF(m.memCategory != 'freebie',1, NULL)) AS numNonFreebie,
    MAX(IF(m.memCategory = 'freebie', r.id, -1)) AS freeRegId, MAX(IF(m.memCategory = 'freebie', r.memId, -1))  AS curFreeId
FROM badgeList b
JOIN perinfo p ON (p.id = b.perid)
LEFT OUTER JOIN perinfo mp ON (p.managedBy = mp.id)
LEFT OUTER JOIN reg r ON (r.perid = p.id AND r.conid = ? AND r.status IN ('paid', 'unpaid', 'plan'))
LEFT OUTER JOIN regActions ra ON r.id = ra.regid AND ra.action = 'print'
LEFT OUTER JOIN memList m ON (r.memId = m.id AND m.conid = ? AND m.memType in ('full', 'oneday', 'virtual'))
WHERE b.conid = ? AND b.user_perid = ?
GROUP BY p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, 
    p.creation_date, p.update_date, p.active, p.banned, p.open_notes, p.admin_notes,
    p.lastverified, phoneCheck, fullName;
EOS;

$response['query']=$watchQ;
$badges = [];
$watchR = dbSafeQuery($watchQ, 'iiii', array($conid, $conid, $conid, $user_perid));
if ($watchR === false) {
    $response['error'] = 'Query failed-see log';
    ajaxSuccess($response);
    exit();
}
$response['success'] = $watchR->num_rows . " members being watched";
while($badge = $watchR->fetch_assoc()) {
    $badge['badgename'] = badgeNameDefault($badge['badge_name'], $badge['badgeNameL2'], $badge['first_name'], $badge['last_name']);

    $badges[] = $badge;
}
$watchR->free();
$response['watchMembers'] = $badges;
ajaxSuccess($response);
