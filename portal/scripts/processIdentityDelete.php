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

if (!array_key_exists('action', $_POST)) {
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
    if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
}

$loginId = getSessionVar('id');
$action = $_POST['action'];
$provider = $_POST['provider'];
$email = $_POST['email'];

$dQ = <<<EOS
DELETE FROM perinfoIdentities
WHERE perid = ? AND provider = ? AND email_addr = ?;
EOS;

$delCnt = dbSafeCmd($dQ, 'iss', array($loginId, $provider, $email));
if ($delCnt == 1) {
    ajaxSuccess(array('status'=>'success', 'message'=>'The identity has been deleted.'));
} else {
    ajaxSuccess(array('status'=>'success', 'message'=>'The account already was deleted.'));
}
