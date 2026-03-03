<?php
require_once "../lib/base.php";
require_once('../../lib/log.php');
require_once('../../lib/policies.php');
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'reg_staff';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('remain', $_POST) && array_key_exists('merge',$_POST))) {
    $response['error'] = 'Invalid Calling Sequence';
    ajaxSuccess($response);
    exit();
}

$conid = getConfValue('con', 'id');
$remain = $_POST['remain'];
$merge = $_POST['merge'];
$user = $authToken->getEmail();
$response['user'] = $user;
$values = $_POST['values'];
$userQ = "SELECT id, perid FROM user WHERE email=?;";
$userR = dbSafeQuery($userQ, 's', array($user));
$userL = $userR->fetch_assoc();
$userid = $userL['id'];
$userPerid = $userL['perid'];
$userR->free();

if ($merge == $remain) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Merge cannot be the same as Survive'));
    exit();
}

// check if one is already merged
$mQ = <<<EOS
SELECT first_name, middle_name, last_name, email_addr
FROM perinfo
WHERE id IN (?,?);
EOS;
$mR = dbSafeQuery($mQ, 'ii', array($merge, $remain));
if ($mR === false) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Database error retrieving perinfo rows'));
    exit();
}
while ($mL = $mR->fetch_assoc()) {
    if (($mL['first_name'] == 'Merged' && $mL['middle_name'] == 'into') || str_starts_with($mL['email_addr'], 'merged into')) {
        ajaxSuccess(array('status'=>'error', 'error'=>'One of the candidates is already a merged record, not allowed to merge a merged record'));
        exit();
    }
}
$mR->free();

// first update remain to have the join values
$mQ = <<<EOS
UPDATE perinfo
SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, legalName = ?, pronouns = ?, badge_name = ?, badgeNameL2 = ?,
    address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, email_addr = ?, currentAgeType = ?, phone = ?,
    active = ?, banned = ?
WHERE id = ?;
EOS;
$valArr = array($values['first_name'], $values['middle_name'], $values['last_name'], $values['suffix'], $values['legalName'],
    $values['pronouns'], $values['badge_name'], $values['badgeNameL2'],
    $values['address'], $values['addr_2'], $values['city'], $values['state'], $values['zip'], $values['country'],
    $values['email_addr'], $values['currentAgeType'], $values['phone'], $values['active'], $values['banned'], $remain);
$numUpd = dbSafeCmd($mQ, 'sssssssssssssssssssi', $valArr);
if ($numUpd === false) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Cannot update remaining person with the new values'));
    exit();
}

// now update the policies before the merge itself
// build the policy array
$memPol = [];
$pQ = <<<EOS
SELECT *
FROM memberPolicies
WHERE conid = ? and perid = ?;
EOS;
$pR = dbSafeQuery($pQ, 'ii', array($conid, $remain));
while ($pL = $pR->fetch_assoc()) {
    $memPol[$pL['policy']] = $pL;
}
$pR->free();

$policies = [];
foreach ($values AS $key => $value) {
    if (!str_starts_with($key, 'p_'))
        continue;
    $policyName = substr($key, 2);
    if (array_key_exists($policyName, $memPol))
        $pol = $memPol[$policyName];
    else
        $pol = array('perid' => $remain, 'conid' => $conid, 'policy' => $policyName);

    $pol['response'] = $value;
    $policies[] = $pol;
}
$numUpd += updateExisingMemberPolicies($policies, $conid, $remain, $userPerid);

$mergeSQL = <<<EOS
    CALL mergePerid($userPerid, $merge, $remain, @status, @rollback); select @status AS status, @rollback AS rollback;
EOS;

$mqr = dbMultiQuery($mergeSQL);
$result = dbNextResult();
$row = $result->fetch_assoc();
$status = $row['status'];
$rollback = $row['rollback'];

$log = get_conf('log');
logInit($log['db']);
logWrite(array('type' => 'merge', 'merge' => $merge, 'remain' => $remain, 'status' => $status, 'rollback' => $rollback));

$response['success'] = 'merged';
$response['status'] = $status;
$response['rollback'] = $rollback;

ajaxSuccess($response);
