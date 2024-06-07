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

if (!(array_key_exists('person', $_POST) && array_key_exists('currentPerson', $_POST) && array_key_exists('currentPersonType', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(array_key_exists('id', $_SESSION) && array_key_exists('idType', $_SESSION))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$personId = $_SESSION['id'];
$personType = $_SESSION['idType'];

$currentPerson = $_POST['currentPerson'];
$currentPersonType = $_POST['currentPersonType'];
$person = $_POST['person'];

$response['currentPersonType'] = $currentPersonType;
$response['currentPeron'] = $currentPerson;
$response['personId'] = $personId;

// update the record
if ($personType == 'p') {
    $updPersonQ =  <<<EOS
UPDATE perinfo
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, email_addr = ?, phone = ?, badge_name = ?, legalName = ?, address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, 
    share_reg_ok = ?, contact_ok = ?, updatedBy = ?
WHERE id = ?;
EOS;
} else {
    $updPersonQ =  <<<EOS
UPDATE newperson
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, email_addr = ?, phone = ?, badge_name = ?, legalName = ?, address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, 
    share_reg_ok = ?, contact_ok = ?, updatedBy = ?
WHERE id = ?;
EOS;
}

$fields = ['lname', 'mname', 'fname', 'suffix', 'email1', 'phone', 'badgename', 'legalname', 'addr', 'addr2', 'city', 'state', 'zip', 'country'];
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
    trim($person['addr']),
    trim($person['addr2']),
    trim($person['city']),
    trim($person['state']),
    trim($person['zip']),
    trim($person['country']),
    array_key_exists('contact', $person) ? $person['contact'] : 'Y',
    array_key_exists('share', $person) ? $person['share'] :'Y',
    $personId,
    $currentPerson
);

$rows_upd = dbSafeCmd($updPersonQ, 'ssssssssssssssssii', $value_arr);
if ($rows_upd === false) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Error updating person'));
    exit();
}
$response['rows_upd'] = $rows_upd;
$response['status'] = 'success';
$response['message'] = $rows_upd == 0 ? "No changes" : "$rows_upd person updated";
ajaxSuccess($response);
