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

// get the people managed
$mQ = <<<EOS
SELECT '' AS type, id, email_addr, badge_name, legalname, phone,
       TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  
        IFNULL(p.suffix, '')), '  *', ' ')) AS fullName
FROM perinfo p
WHERE managedBy = ?
UNION
SELECT 'n' AS type, id, email_addr, badge_name, legalname, phone,
       TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  
        IFNULL(p.suffix, '')), '  *', ' ')) AS fullName
FROM newperson p
WHERE managedBy = ?
EOS;

$mR = dbSafeQuery($mQ, 'ii', array($perid, $perid));
if ($mR === false) {
    $response['error'] = 'Select managed by failed';
    ajaxSuccess($response);
    return;
}

$managed= [];
while ($row = $mR->fetch_assoc()) {
    $managed[] = $row;
}
$mR->free();
$response['managed'] = $managed;

ajaxSuccess($response);
?>
