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

if (!(array_key_exists('id', $_SESSION) && array_key_exists('idType', $_SESSION))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$personId = $_SESSION['id'];
$personType = $_SESSION['idType'];

$response['personType'] = $personType;
$response['personId'] = $personId;
$getId = $_POST['getId'];
$getType = $_POST['getType'];

// get the record
if ($personType == 'p') {
    $getPersonQ =  <<<EOS
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, 
    share_reg_ok, contact_ok, managedBy, NULL AS managedByNew,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM perinfo
WHERE id = ?;
EOS;
} else {
    $getPersonQ =  <<<EOS
SELECT id, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip, country, 
    share_reg_ok, contact_ok, managedBy, managedByNew,
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

// now if asked for memberships, get them as well
if ($personType == 'p') {
    $rfield = 'perid';
} else {
    $rfield = 'newperid';
}
if (array_key_exists('memberships', $_POST) && $_POST['memberships'] == 'Y') {
    $memberships = [];
    $mQ = <<<EOS
SELECT r.id, r.create_date, r.memId, r.conid, r.status, r.price, r.paid, r.couponDiscount, m.label, m.memType, m.memCategory
FROM reg r
JOIN memList m ON m.id = r.memId
WHERE r.$rfield = ? AND r.conid IN (?, ?)
ORDER BY r.create_date;
EOS;
    $mR = dbSafeQuery($mQ,'iii', array($person['id'], $conid, $conid + 1));
    if ($mR !== false) {
        while ($row = $mR->fetch_assoc()) {
            $memberships[] = $row;
        }
    }
    $response['memberships'] = $memberships;
}
ajaxSuccess($response);
