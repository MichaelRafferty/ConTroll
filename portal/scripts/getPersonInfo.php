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

$personId = getSessionVar('id');
$personType = getSessionVar('idType');

$response['personType'] = $personType;
$response['personId'] = $personId;
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
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, 
    share_reg_ok, contact_ok, managedBy, NULL AS managedByNew, lastVerified, 'p' AS personType,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM perinfo
WHERE id = ?;
EOS;
} else {
    $getPersonQ =  <<<EOS
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, 
    share_reg_ok, contact_ok, managedBy, managedByNew, lastVerified, 'n' AS personType,
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
if ($personId != $person['managedBy'] && $personId != $person['managedByNew'] && $personId != $person['id']) {
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
            $interests[] = $row;
        }
        $iR->free();
    }
    $response['interests'] = $interests;
}

// memberships of both Y and A types
if ($membershipReq == 'Y' || $membershipReq == 'B') {
    $memberships = [];
    $mQ = <<<EOS
SELECT r.id, r.create_date, r.memId, r.conid, r.status, r.price, r.paid, r.couponDiscount, m.label, m.memType, m.memCategory, m.memAge
FROM reg r
JOIN memList m ON m.id = r.memId
WHERE r.$rfield = ? AND r.conid IN (?, ?)
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

if ($membershipReq == 'A') {
    $allMemberships = [];
    $mQ = <<<EOS
SELECT r.id, r.create_date, r.memId, r.conid, r.status, r.price, r.paid, r.couponDiscount, m.label, m.memType, m.memCategory, m.memAge
FROM reg r
JOIN memList m ON m.id = r.memId
JOIN $ptable p ON p.id = r.$rfield
LEFT OUTER JOIN $ptable pm ON p.$mfield = pm.id
WHERE r.$rfield = p.id AND r.conid IN (?, ?) AND (pm.id = ? OR p.id = ?)
ORDER BY r.create_date;
EOS;
    $mR = dbSafeQuery($mQ, 'iiii', array($conid, $conid + 1, $personId, $personId));
    if ($mR !== false) {
        while ($row = $mR->fetch_assoc()) {
            $allMemberships[] = $row;
        }
    }
    $response['allMemberships'] = $allMemberships;
}

ajaxSuccess($response);
