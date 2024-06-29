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
$log = get_conf('log');
logInit($log['reg']);

$response['conid'] = $conid;

if (!(array_key_exists('person', $_POST) && array_key_exists('currentPerson', $_POST) && array_key_exists('currentPersonType', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}
$currentPerson = $_POST['currentPerson'];
$currentPersonType = $_POST['currentPersonType'];

if (array_key_exists('validation', $_POST) && array_key_exists('valEmail', $_POST)) {
    $validationType = $_POST['validation'];
    $validationEmail = $_POST['valEmail'];
} else {
    $validationType = '';
    $validationEmail = '';
}

if (!array_key_exists('source', $_POST) || $_POST['source'] != 'login' || $currentPerson != -12345) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if ($currentPerson != -12345) {
    $loginId = getSessionVar('id');
} else {
    $loginId = 4;
}

$person = $_POST['person'];

$response['currentPersonType'] = $currentPersonType;
$response['currentPeron'] = $currentPerson;
$response['personId'] = $loginId;

// insert into newPerson
$iQ = <<<EOS
insert into newperson (last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip,
                       country, share_reg_ok, contact_ok, updatedBy, lastVerified)
values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW());
EOS;
$typeStr = 'ssssssssssssssssi';
$valArray = array(
    trim($person['lname']),
    trim($person['mname']),
    trim($person['fname']),
    trim($person['suffix']),
    trim($person['email1']),
    trim($person['phone']),
    trim($person['badgename']),
    trim($person['legalname']),
    trim($person['addr']),
    trim($person['addr2']),
    trim($person['city']),
    trim($person['state']),
    trim($person['zip']),
    trim($person['country']),
    $person['share'],
    $person['contact'],
    $loginId
);
$personId = dbSafeInsert($iQ, $typeStr, $valArray);
if ($personId === false || $personId < 0) {
    $response['status'] = 'error';
    $response['message'] = 'Error inserting the new person into the database. Seek assistance';
    ajaxSuccess($response);
}
$response['newPersonId'] = $personId;
$response['message'] = "New person with Temporary ID $personId added";
setSessionVar("id", $personId);
setSessionVar("idType", 'n');
logWrite(array('con'=>$con['name'], 'action' => 'Create new person on login', 'person' => array('n', $personId), 'newperson' => $person ));


if ($validationType == 'token') {
    $updSQL = <<<EOS
    UPDATE portalTokenLinks
    SET useCnt = 0, useCnt = 0, useIP = null, useTS = null
    WHERE email = ? AND useCnt = 1 AND TIMESTAMPDIFF(SECOND,createdTS,NOW()) < 3600;
    EOS;
    $num_upd = dbSafeCmd($updSQL, 's', array($valArray['email']));
    $response['valCleared'] = $num_upd;
}

ajaxSuccess($response);
