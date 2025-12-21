<?php
require_once "../lib/base.php";
require_once "../../lib/policies.php";
require_once "../../lib/interests.php";

$check_auth = google_init("ajax");
$perm = "people";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('perid', $_POST)) && array_key_exists('action', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$action = $_POST['action'];
$perid = $_POST['perid'];
$updatedBy = $_SESSION['user_perid'];
if ($action != 'saveedit' || $perid == null || is_numeric($perid) == false || $perid <= 0) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid=$con['id'];

// this updates the database for a people screen edit of a person
// it has several sections...
//  1. profile
//  2. policies
//  3. interests
//  4. manages

//  1. Profile, update the perinfo record

$last_name = $_POST['lastName'] == null ? '' : trim($_POST['lastName']);
$first_name = $_POST['firstName'] == null ? '' : trim($_POST['firstName']);
$middle_name = $_POST['middleName'] == null ? '' : trim($_POST['middleName']);
$suffix = $_POST['suffix'] == null ? '' : trim($_POST['suffix']);
$legalName = $_POST['legalName'] == null ? '' : trim($_POST['legalName']);
$pronouns = $_POST['pronouns'] == null ? '' : trim($_POST['pronouns']);
$badge_name = $_POST['badgeName'] == null ? '' : trim($_POST['badgeName']);
$badgeNameL2 = $_POST['badgeNameL2'] == null ? '' : trim($_POST['badgeNameL2']);
$address = $_POST['address'] == null ? '' : trim($_POST['address']);
$addr_2 = $_POST['addr2'] == null ? '' : trim($_POST['addr2']);
$city = $_POST['city'] == null ? '' : trim($_POST['city']);
$state = $_POST['state'] == null ? '' : trim($_POST['state']);
$zip = $_POST['zip'] == null ? '' : trim($_POST['zip']);
$country = $_POST['country'] == null ? '' : trim($_POST['country']);
$email_addr = $_POST['emailAddr'] == null ? '' : trim($_POST['emailAddr']);
$phone = $_POST['phone'] == null ? '' : trim($_POST['phone']);
$managedBy = $_POST['managerId'] == '' ? null : trim($_POST['managerId']);
$active = $_POST['active'] == null ? 'Y' : trim($_POST['active']);
$banned = $_POST['banned'] == null ? 'N' : trim($_POST['banned']);
$admin_notes = $_POST['adminNotes'] == null ? '' : trim($_POST['adminNotes']);
$open_notes = $_POST['openNotes'] == null ? '' : trim($_POST['openNotes']);
$currentAgeType = $_POST['currentAgeType'];
$origAgeType = $_POST['origAgeType'];

// check if manager is managed by someone else if $managedBy is not null
if ($managedBy != null) {
    $chkQ = <<<EOS
SELECT managedByNew, managedBy
FROM perinfo
WHERE id = ?;
EOS;
    $chkR = dbSafeQuery($chkQ, 'i', array($managedBy));
    if ($chkR === false) {
        $response['error'] = 'SQL Error in checking if manager is managed' . '<br/>Nothing updated.';
        ajaxSuccess($response);
        exit();
    }
    $managerData = $chkR->fetch_assoc();
    $chkR->free();
    if ($managerData['managedBy'] != null) {
        $response['error'] = "Manager $managedBy is already managed by " . $managerData['managedBy'] . "<br/>Nothing updated.";
        ajaxSuccess($response);
        exit();
    }
    if ($managerData['managedByNew'] != null) {
        $response['error'] = "Manager $managedBy is already managed by newperson" . $managerData['managedByNew'];
        ajaxSuccess($response);
        exit();
    }
}

if ($origAgeType != $currentAgeType) {
    $ageSQL = ' currentAgeConid = ?, currentAgeType = ?,';
    $typeStr = 'is';
    $valArray = array(($currentAgeType == '' ? null : $conid), $currentAgeType);
} else {
    $ageSQL = '';
    $typeStr = '';
    $valArray = [];
}

$uP = <<<EOS
UPDATE perinfo
SET $ageSQL last_name = ?, first_name = ?, middle_name = ?, suffix = ?, email_addr = ?, phone = ?, badge_name = ?, badgeNameL2 = ?, legalName = ?, pronouns = ?,
    address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, banned = ?,
    active = ?, open_notes = ?, admin_notes = ?, managedBy = ?, updatedBy = ?, 
    managedByNew = NULL, lastVerified = NULL, update_date = NOW(), change_notes = CONCAT(change_notes, '<br/>Updated by People Edit screen')
WHERE id = ?;
EOS;


$typeStr .= 'ssssssssssssssssssssiii';
array_push($valArray, $last_name, $first_name, $middle_name, $suffix, $email_addr, $phone, $badge_name, $badgeNameL2, $legalName, $pronouns,
    $address, $addr_2, $city, $state, $zip, $country, $banned, $active, $open_notes, $admin_notes, $managedBy, $updatedBy, $perid);

$upd = dbSafeCmd($uP, $typeStr, $valArray);
if ($upd === false) {
    $response['error'] = 'Error updating Perinfo Record';
    ajaxSuccess($response);
    return;
}

$message = 'Perinfo Record Updated';

//  2. Policies

$policy_upd =  updateMemberPolicies($conid, $perid, 'p', $updatedBy, 'p');
if ($policy_upd > 0) {
    $message .= "<br/>$policy_upd policy responses updated";
}

//  3. interests
$interest_upd =  updateMemberInterests($conid, $perid, 'p', $updatedBy, 'p');
if ($interest_upd > 0) {
    $message .= "<br/>$interest_upd interest responses updated";
}

//  4. manages
// handled directly in the JS using people_unmanage.php and people_manage.php

// 5. now return the updated record
$updRowSQL = <<<EOS
WITH memAge AS (
    SELECT r.perid, MAX(m.memAge) AS memAgeType
    FROM reg r
    LEFT OUTER JOIN memList m on r.memId = m.id
    WHERE m.memAge != 'all' AND r.perid = ?
    GROUP BY r.perid
)
SELECT p.id, p.email_addr, p.phone, CONCAT_WS('<BR>', p.currentAgeType, ma.memAgeType) AS displayAgeType, p.creation_date, 
    p.first_name, p.middle_name, p.last_name, p.suffix, p.legalName, p.pronouns, p.badge_name, p.badgeNameL2,
    p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    p.currentAgeType, p.active, p.banned, p.admin_notes, p.open_notes,
    REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(IFNULL(p.phone, ''))), ')', ''), '(', ''), '-', ''), ' ', '') AS phoneCheck,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.address, p.addr_2, p.city, p.state, p.zip, p.country), ' +', ' ')) AS fullAddr,
    CASE
        WHEN mp.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT_WS(' ', mp.first_name, mp.middle_name, mp.last_name, mp.suffix), ' +', ' ')) 
        ELSE ''
    END AS manager,
    CASE
        WHEN mp.id IS NOT NULL THEN mp.id
        ELSE NULL
    END AS managerId,
    GROUP_CONCAT(DISTINCT TRIM(CONCAT(CASE WHEN m.conid = ? THEN '' ELSE m.conid END, ' ', m.label)) ORDER BY m.id SEPARATOR ', ') AS memberships
FROM perinfo p
LEFT OUTER JOIN reg r  ON (r.perid = p.id AND r.status IN ('paid', 'unpaid', 'plan'))
LEFT OUTER JOIN perinfo mp ON (p.managedBy = mp.id)
LEFT OUTER JOIN memList m ON (r.memId = m.id AND m.conid in (?, ?))
LEFT OUTER JOIN memAge ma ON p.id = ma.perid
WHERE p.id = ?
GROUP BY p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.badgeNameL2, p.legalName, p.pronouns, 
    p.address, p.addr_2, p.city, p.state, p.zip, p.country, 
    p.creation_date, p.update_date, p.active, p.banned, p.open_notes, p.admin_notes,
    p.managedBy, p.managedByNew, p.lastverified, p.managedreason, phoneCheck, fullName, manager, managerId,
    ma.memAgeType, p.currentAgeType
EOS;
$updRowR = dbSafeQuery($updRowSQL, 'iiiii', array($perid, $conid,  $conid, $conid + 1, $perid));
if ($updRowR === false) {
    $response['warn'] = 'Error retrieving updated row';
} else {
    $updRow = $updRowR->fetch_assoc();
    $updRow['badgename'] = badgeNameDefault($updRow['badge_name'], $updRow['badgeNameL2'], $updRow['first_name'], $updRow['last_name']);
    $updRowR->free();
    $response['updated'] = array($updRow);
}

$response['success'] = $message;
ajaxSuccess($response);
