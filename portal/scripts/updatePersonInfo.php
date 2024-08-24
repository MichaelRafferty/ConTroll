<?php
require_once('../lib/base.php');
require_once('../../lib/log.php');
require_once('../../lib/policies.php');

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

$response['conid'] = $conid;

if (!(array_key_exists('person', $_POST) && array_key_exists('currentPerson', $_POST) && array_key_exists('currentPersonType', $_POST))) {
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

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
if ($resolveUpdates != null && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
}

$personId = getSessionVar('id');
$personType = getSessionVar('idType');

$currentPerson = $_POST['currentPerson'];
$currentPersonType = $_POST['currentPersonType'];
$person = $_POST['person'];

$response['currentPersonType'] = $currentPersonType;
$response['currentPeron'] = $currentPerson;
$response['personId'] = $personId;

// update the record
if ($currentPersonType == 'p') {
    $updPersonQ =  <<<EOS
UPDATE perinfo
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, phone = ?, badge_name = ?, legalName = ?, pronouns = ?,
    address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, updatedBy = ?, lastVerified = NOW()
WHERE id = ?;
EOS;
} else {
    $updPersonQ =  <<<EOS
UPDATE newperson
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, phone = ?, badge_name = ?, legalName = ?, pronouns = ?,
    address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, updatedBy = ?, lastVerified = NOW()
WHERE id = ?;
EOS;
}

$fields = ['lname', 'mname', 'fname', 'suffix', 'phone', 'badgename', 'legalname', 'pronouns', 'addr', 'addr2', 'city', 'state', 'zip', 'country'];
foreach ($fields as $field) {
    if ($person[$field] == null)
        $person[$field] = '';
}
$value_arr = array(
    trim($person['lname']),
    trim($person['mname']),
    trim($person['fname']),
    trim($person['suffix']),
    trim($person['phone']),
    trim($person['badgename']),
    trim($person['legalname']),
    trim($person['pronouns']),
    trim($person['addr']),
    trim($person['addr2']),
    trim($person['city']),
    trim($person['state']),
    trim($person['zip']),
    trim($person['country']),
    $personId,
    $currentPerson,
);

$rows_upd = dbSafeCmd($updPersonQ, 'ssssssssssssssii', $value_arr);
if ($rows_upd === false) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Error updating person'));
    exit();
}

$message = $rows_upd == 0 ? 'No changes' : "$rows_upd person updated";

// now update the policies
$policy_upd =  updateMemberPolicies($conid, $currentPerson, $currentPersonType, $personId, $personType);
if ($policy_upd > 0) {
    $message .= "<br/>$policy_upd policy responses updated";
}

$response['rows_upd'] = $rows_upd;
$response['status'] = 'success';
$response['logmessage'] = $message;
$response['message'] = 'Information successfully updated';
logInit($log['reg']);
logWrite($response);

ajaxSuccess($response);
