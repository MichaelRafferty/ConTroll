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

if (!array_key_exists('managedBy', $_POST)) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}
if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

validateLoginId();

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
}

$updateBy = getSessionVar('id');
// portal.js: client disassociate manager or manager disassociate client
//  managedB': 'disassociate',
//  loginId: config.id,
//  loginType: config.idType,
//
//  if (who != null) {
//     data.idNum = id;
//     data.idType = idtype;
//     data.who = who;
//
//  settings: manager dissociate client
//     loginId: config['id'],
//     loginType: config['idType'],
//     managedBy: 'client',
//     idType: type,
//     idNum: id,

$disType = $_POST['managedBy'];
if ($disType == 'client') {
    // manager dissociate client from settings
    $personId = $_POST['idNum'];
    $personType = $_POST['idType'];
    $checkLoginId = $_POST['loginId'];
    $checkLoginType = $_POST['loginType'];
    $reason = 'Mgr Req';
} else if (!array_key_exists('who', $_POST) || $_POST['who'] == null) {
    // this is a disassociate me from my manager
    $personId = getSessionVar('id');
    $personType = getSessionVar('idType');
    $reason = 'Client Req';
} else {
    $personId = $_POST['idNum'];
    $personType = $_POST['idType'];
    $checkLoginId = $_POST['loginId'];
    $checkLoginType = $_POST['loginType'];
    $who = $_POST['who'];
    $reason = 'Mgr Req';
}

if (!validateAccess($personId, $personType)) {
    ajaxSuccess(array('status'=>'error', 'message'=>'You do not have permission to disassociate this account.'));
    exit();
}

if ($personType == 'p') {
    $personQ = <<<EOS
SELECT id, managedBy, NULL as managedByNew
FROM perinfo
WHERE id = ?;
EOS;
    $personU = <<<EOS
UPDATE perinfo
SET managedBy = NULL, updatedBy = ?, managedReason = ?
WHERE id = ?;
EOS;
} else {
    $personQ = <<<EOS
SELECT id, managedBy, managedByNew
FROM newperson
WHERE id = ?;
EOS;
    $personU = <<<EOS
UPDATE newperson
SET managedBy = NULL, managedByNew = NULL, updatedBy = ?, managedReason = ?
WHERE id = ?;
EOS;
}
$personR = dbSafeQuery($personQ, 'i', array($personId));
if ($personR === false || $personR->num_rows < 1) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Unable to disassociate, seek assistance.'));
    exit();
}

$personL = $personR->fetch_assoc();
$personR->free();

if ($personL['managedBy'] == NULL && $personL['managedByNew'] == NULL) {
    ajaxSuccess(array('status'=>'warn', 'message'=>'This account is currently not managed.'));
    exit();
}

$num_rows = dbSafeCmd($personU, 'isi', array($updateBy, $reason, $personId));
if ($num_rows == 1) {
    ajaxSuccess(array('status'=>'success', 'message'=>'The account has been changed to unmanaged.'));
} else {
    ajaxSuccess(array('status'=>'success', 'message'=>'The account already was unmanaged.'));
}
