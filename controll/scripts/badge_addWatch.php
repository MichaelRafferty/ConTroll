<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "badge";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
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
if ($ajax_request_action != 'addWatch') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$user_perid = $_SESSION['user_perid'];
$response['id'] = $_SESSION['user_id'];
$response['user_perid'] = $user_perid;

$perid = $_POST['perid'];
// check to see if already on list

$cQ = <<<EOS
SELECT count(*) as num 
FROM badgeList
WHERE user_perid = ? AND conid = ? AND perid = ?;
EOS;
$typeStr = 'iii';
$values = array($user_perid, $conid, $perid);
$cR = dbSafeQuery($cQ, $typeStr, $values);
if ($cR === false) {
    $response['error'] = "Check of $perid to watch failed, see log.";
    ajaxSuccess($response);
    exit();
}
$numRows = $cR->fetch_row()[0];
$cR->free();

if ($numRows > 0) {
    $response['error'] = "$perid is already being watched;";
    ajaxSuccess($response);
    exit();
}

$iQ = <<<EOS
INSERT INTO badgeList(user_perid, conid, perid) VALUES (?, ?, ?);
EOS;
$newid = dbSafeInsert($iQ, $typeStr, $values);
if ($newid === false) {
    $response['error'] = "Insert of $perid to watch failed, see log.";
    ajaxSuccess($response);
    exit();
}

$watchQ = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, p.banned, 
    p.creation_date, p.update_date, p.active, p.banned, p.open_notes, p.admin_notes, p.lastverified,
    REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(p.phone)), ')', ''), '(', ''), '-', ''), ' ', '') AS phoneCheck,
    TRIM(REGEXP_REPLACE(CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name, ' ', p.suffix), ' +', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.address, p.addr_2, p.city, p.state, p.zip, p.country), ' +', ' ')) AS fullAddr,
    GROUP_CONCAT(DISTINCT m.label ORDER BY m.id SEPARATOR ', ') AS memberships
FROM badgeList b
JOIN perinfo p ON (p.id = b.perid)
LEFT OUTER JOIN perinfo mp ON (p.managedBy = mp.id)
LEFT OUTER JOIN reg r ON (r.perid = p.id AND r.conid = ? AND r.status IN ('paid', 'unpaid', 'plan'))
LEFT OUTER JOIN memList m ON (r.memId = m.id AND m.conid = ? AND m.memType in ('full', 'oneday', 'virtual'))
WHERE b.conid = ? AND b.user_perid = ?
GROUP BY p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, 
    p.creation_date, p.update_date, p.active, p.banned, p.open_notes, p.admin_notes,
    p.lastverified, phoneCheck, fullName;
EOS;

$response['query']=$watchQ;
$badges = [];

$watchR = dbSafeQuery($watchQ, 'iiii', array($con['id'], $con['id'], $con['id'], $user_perid));
if ($watchR === false) {
    $response['error'] = 'Query failed-see log';
    ajaxSuccess($response);
    exit();
}
$response['success'] = $newid . " added, " . $watchR->num_rows . " members now being watched";
while($badge = $watchR->fetch_assoc()) {
    $badges[] = $badge;
}
$watchR->free();
$response['watchMembers'] = $badges;
ajaxSuccess($response);
