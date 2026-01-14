<?php
require_once "../lib/base.php";
require_once "../../lib/policies.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'search';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('type', $_POST)) && array_key_exists('add', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$type = $_POST['type'];
$updatedBy = $authToken->getPerid();

$con = get_conf('con');
$conid = $con['id'];

if ($_POST['currentAgeType'] == '') {
    $currentAgeType = null;
    $currentAgeConId = null;
} else {
    $currentAgeType = $_POST['currentAgeType'];
    $currentAgeConId = $conid;
}

$iP = <<<EOS
INSERT INTO perinfo(last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, badgeNameL2,
    legalName, pronouns, address, addr_2, city, state, zip, country, currentAgeConId, currentAgeType,
    banned, active, updatedBy)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'N', 'Y',?);
EOS;
$typestr = 'ssssssssssssssssisi';

// built insert array
$values = [
    $_POST['lastName'] == null ? '' : $_POST['lastName'],
    $_POST['firstName'] == null ? '' : $_POST['firstName'],
    $_POST['middleName'] == null ? '' : $_POST['middleName'],
    $_POST['suffix'] == null ? '' : $_POST['suffix'],
    $_POST['emailAddr'] == null ? '' : $_POST['emailAddr'],
    $_POST['phone'] == null ? '' : $_POST['phone'],
    $_POST['badgeName'] == null ? '' : $_POST['badgeName'],
    $_POST['badgeNameL2'] == null ? '' : $_POST['badgeNameL2'],
    $_POST['legalName'] == null ? '' : $_POST['legalName'],
    $_POST['pronouns'] == null ? '' : $_POST['pronouns'],
    $_POST['address'] == null ? '' : $_POST['address'],
    $_POST['addr2'] == null ? '' : $_POST['addr2'],
    $_POST['city'] == null ? '' : $_POST['city'],
    $_POST['state'] == null ? '' : $_POST['state'],
    $_POST['zip'] == null ? '' : $_POST['zip'],
    $_POST['country'] == null ? '' : $_POST['country'],
    $currentAgeConId,
    $currentAgeType,
];
$values[] = $updatedBy;

$perid = dbSafeInsert($iP, $typestr, $values);
if ($perid === false) {
    $response['error'] = 'Error inserting into perinfo table, check logs and seek assistance';
    ajaxSuccess($response);
    return;
}
$message = "Person $perid created";
// add the policies
$policy_upd =  updateMemberPolicies($conid, $perid, 'p', $updatedBy, 'p', 'a_');
if ($policy_upd > 0) {
    $message .= "<br/>$policy_upd policy responses updated";
}

$response['success'] = $message;
$response['perid'] = $perid;
ajaxSuccess($response);
