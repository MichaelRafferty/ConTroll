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

$response['conid'] = $conid;

if (!(array_key_exists('person', $_POST) && array_key_exists('currentPerson', $_POST) && array_key_exists('currentPersonType', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$personId = getSessionVar('id');
$personType = getSessionVar('idType');

$currentPerson = $_POST['currentPerson'];
$currentPersonType = $_POST['currentPersonType'];
if (array_key_exists('oldPolicies', $_POST))
    $oldPoliciesArr = json_decode($_POST['oldPolicies'], true);
else
    $oldPoliciesArr = array();
if (array_key_exists('newPolicies', $_POST))
    $newPolicies = json_decode($_POST['newPolicies'], true);
else
    $newPolicies = array();
// convert oldPolicies to an associative array with the p_ in the front of the indicies
$oldPolicies = array();
foreach ($oldPoliciesArr as $oldPolicy) {
    $oldPolicies['p_' . $oldPolicy['policy']] = $oldPolicy;
}
$person = $_POST['person'];

$response['currentPersonType'] = $currentPersonType;
$response['currentPeron'] = $currentPerson;
$response['personId'] = $personId;

// update the record
if ($currentPersonType == 'p') {
    $updPersonQ =  <<<EOS
UPDATE perinfo
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, email_addr = ?, phone = ?, badge_name = ?, legalName = ?, pronouns = ?,
    address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, 
    share_reg_ok = ?, contact_ok = ?, updatedBy = ?, lastVerified = NOW()
WHERE id = ?;
EOS;
} else {
    $updPersonQ =  <<<EOS
UPDATE newperson
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, email_addr = ?, phone = ?, badge_name = ?, legalName = ?, pronouns = ?,
    address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, 
    share_reg_ok = ?, contact_ok = ?, updatedBy = ?, lastVerified = NOW()
WHERE id = ?;
EOS;
}

$fields = ['lname', 'mname', 'fname', 'suffix', 'email1', 'phone', 'badgename', 'legalname', 'pronouns', 'addr', 'addr2', 'city', 'state', 'zip', 'country'];
foreach ($fields as $field) {
    if ($person[$field] == null)
        $person[$field] = '';
}
$value_arr = array(
    trim($person['lname']),
    trim($person['mname']),
    trim($person['fname']),
    trim($person['suffix']),
    trim($person['email1']),
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
    array_key_exists('contact', $person) ? $person['contact'] : 'Y',
    array_key_exists('share', $person) ? $person['share'] :'Y',
    $personId,
    $currentPerson,
);

$rows_upd = dbSafeCmd($updPersonQ, 'sssssssssssssssssii', $value_arr);
if ($rows_upd === false) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Error updating person'));
    exit();
}

$message = $rows_upd == 0 ? 'No changes' : "$rows_upd person updated";

// now update the policies
$policies = getPolicies();
$iQ = <<<EOS
INSERt INTO memberPolicies(perid, conid, newperid, policy, response, updateBy)
VALUES (?,?,?,?,?,?);
EOS;
$uQ = <<<EOS
UPDATE memberPolicies
SET response = ?, updateBy = ?
WHERE id = ?;
EOS;

if ($policies != null) {
    $policy_upd = 0;
    foreach ($policies as $policy) {
        $old = '';
        $new = 'N';
        if (array_key_exists('p_' . $policy['policy'], $oldPolicies))
            $old = $oldPolicies['p_' . $policy['policy']];
        if (array_key_exists('p_' . $policy['policy'], $newPolicies))
            $new = $newPolicies['p_' . $policy['policy']];

        // ok the options if old is blank, there likely isn't an entry in the database, New if missing is a 'N';
        if ($old == '') {
            $valueArray = array (
                $currentPersonType == 'p' ? $currentPerson : null,
                $conid,
                $currentPersonType == 'n' ? $currentPerson : null,
                $policy['policy'],
                $new,
                $personType == 'p' ? $personId : null
            );
            $ins_key = dbSafeInsert($iQ, 'iiissi', $valueArray);
            if ($ins_key !== false) {
                $policy_upd++;
            }
        } else if ($old != $new) {
            $policy_upd += dbSafeCmd($uQ, 'sii', array($new, $personType == 'p' ? $personId : null, $old['id']));
        }
    }
    if ($policy_upd > 0) {
        $message .= "<br/>$policy_upd policy responses updated";
    }
}

$response['rows_upd'] = $rows_upd;
$response['status'] = 'success';
$response['message'] = $message;
ajaxSuccess($response);
