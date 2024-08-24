<?php
require_once('../lib/base.php');
require_once('../../lib/log.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$portal_conf = get_conf('portal');

$response['conid'] = $conid;

if (!(array_key_exists('getId', $_POST) && array_key_exists('getType', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
if ($resolveUpdates != null && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');

$response['personType'] = $loginType;
$response['personId'] = $loginId;
$getId = $_POST['getId'];
$getType = $_POST['getType'];

if (array_key_exists('interests', $_POST)) {
    $interestReq = $_POST['interests'];
} else {
    $interestReq = '';
}

if (array_key_exists('memberships', $_POST)) {
    $membershipReq = $_POST['memberships'];
} else {
    $membershipReq = '';
}

// get the record
if ($getType == 'p') {
    $getPersonQ =  <<<EOS
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, pronouns, address, addr_2, city, state, zip, country, 
    managedBy, NULL AS managedByNew, lastVerified, 'p' AS personType,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM perinfo
WHERE id = ?;
EOS;
} else {
    $getPersonQ =  <<<EOS
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, pronouns, address, addr_2, city, state, zip, country, 
    managedBy, managedByNew, lastVerified, 'n' AS personType,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM newperson
WHERE id = ?;
EOS;
}

$getPersonR = dbSafeQuery($getPersonQ, 'i', array($getId));
if ($getPersonR == false || $getPersonR->num_rows != 1) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Person not found.'));
    exit();
}
$person = $getPersonR->fetch_assoc();
$getPersonR->free();

if ($person['country'] == null || $person['country'] == '')
    $person['country'] = 'USA';

// now we have the person, lets see if we can edit it.
if ($loginId != $person['managedBy'] && $loginId != $person['managedByNew'] && $loginId != $person['id']) {
    ajaxSuccess(array('status'=>'error', 'message'=>'You have no permission to edit this person.'));
    exit();
}

// ok we have permission, return the person record
$response['person'] = $person;

// now if asked for interests or  memberships, get them as well
if ($getType == 'p') {
    $rfield = 'perid';
    $ptable = 'perinfo';
    $mfield = 'managedBy';
} else {
    $rfield = 'newperid';
    $ptable = 'newperson';
    $mfield = 'managedByNew';
}

// now get the policies as that goes with the person record for the forms
$policies = [];
$pQ = <<<EOS
SELECT p.policy, p.prompt, p.description, p.required, p.defaultValue, p.sortOrder, m.id, m.perid, m.conid, m.newperid, m.response
FROM policies p
LEFT OUTER JOIN memberPolicies m ON p.policy = m.policy AND m.$rfield = ? AND m.conid = ?
WHERE p.active = 'Y'
ORDER BY p.sortOrder;
EOS;
$pR = dbSafeQuery($pQ, 'ii', array($person['id'], $conid));
if ($pR !== false) {
    while ($row = $pR->fetch_assoc()) {
        $policies[] = $row;
    }
    $pR->free();
}
$response['policies'] = $policies;

// interests
if ($interestReq == 'Y') {
    $interests = [];

    $iQ = <<<EOS
SELECT i.interest, i.description, i.sortOrder, m.interested, m.id
FROM interests i
LEFT OUTER JOIN memberInterests m ON m.$rfield = ? AND m.interest = i.interest AND conid = ?
WHERE i.active = 'Y'
ORDER BY i.sortOrder
EOS;
    $iR = dbSafeQuery($iQ, 'ii', array($person['id'], $conid));
    if ($iR !== false) {
        while ($row = $iR->fetch_assoc()) {
            $interests[$row['interest']] = $row;
        }
        $iR->free();
    }
    $response['interests'] = $interests;
}

// memberships of both Y and A types
if ($membershipReq == 'Y' || $membershipReq == 'B') {
    $memberships = [];
    $mQ = <<<EOS
SELECT r.id, r.create_date, r.memId, r.conid, r.status, r.price, r.paid, r.couponDiscount, r.perid, r.newperid,
       m.label, m.memType, m.memCategory, m.memAge, m.startdate, m.enddate, m.online
FROM reg r
JOIN memList m ON m.id = r.memId
WHERE r.$rfield = ? AND r.conid IN (?, ?) AND status IN ('unpaid', 'paid', 'plan', 'upgraded')
ORDER BY r.create_date;
EOS;
    $mR = dbSafeQuery($mQ, 'iii', array($person['id'], $conid, $conid + 1));
    if ($mR !== false) {
        while ($row = $mR->fetch_assoc()) {
            $memberships[] = $row;
        }
        $mR->free();
    }
    $response['memberships'] = $memberships;
}

if ($membershipReq == 'A' || $membershipReq == 'B') {
    $allMemberships = [];
    if ($loginType == 'p') {
        $mQ = <<<EOS
SELECT r.id, r.perid, r.newperid, r.create_date, r.memId, r.conid, r.status, r.price, r.paid, r.couponDiscount, m.label, m.memType, m.memCategory, m.memAge
FROM reg r
JOIN memList m ON m.id = r.memId
JOIN perinfo p ON p.id = r.perid
LEFT OUTER JOIN perinfo pm ON p.managedBy = pm.id
WHERE r.conid IN (?, ?) AND (pm.id = ? OR p.id = ?)
UNION
SELECT r.id, r.perid, r.newperid, r.create_date, r.memId, r.conid, r.status, r.price, r.paid, r.couponDiscount, m.label, m.memType, m.memCategory, m.memAge
FROM reg r
JOIN memList m ON m.id = r.memId
JOIN newperson n ON n.id = r.newperid
LEFT OUTER JOIN perinfo pm ON n.managedBy = pm.id
WHERE r.conid IN (?, ?) AND (pm.id = ?) AND n.perid IS NULL
ORDER BY create_date;
EOS;
        $mR = dbSafeQuery($mQ, 'iiiiiii', array ($conid, $conid + 1, $loginId, $loginId, $conid, $conid + 1, $loginId));
    } else {
        $mQ = <<<EOS
SELECT r.id, r.create_date, r.memId, r.conid, r.status, r.price, r.paid, r.couponDiscount, r.perid, r.newperid,
       m.label, m.memType, m.memCategory, m.memAge
FROM reg r
JOIN memList m ON m.id = r.memId
JOIN newperson n ON n.id = r.newperid
LEFT OUTER JOIN newperson nm ON n.managedByNew = nm.id
WHERE r.conid IN (?, ?) AND (nm.id = ? OR n.id = ?) AND n.perid IS NULL
ORDER BY create_date;
EOS;
        $mR = dbSafeQuery($mQ, 'iiii', array ($conid, $conid + 1, $loginId, $loginId));
    }
    if ($mR !== false) {
        while ($row = $mR->fetch_assoc()) {
            $allMemberships[] = $row;
        }
    }
    $response['allMemberships'] = $allMemberships;
}

ajaxSuccess($response);
