<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../../lib/reg_receipt.php";

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

if (!(array_key_exists('transId', $_POST) && array_key_exists('action', $_POST))) {
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

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');

$response['loginId'] = $loginId;
$response['loginType'] = $loginType;
$action = $_POST['action'];
if ($action != 'portalReceipt') {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (array_key_exists('transId', $_POST)) {
    $transId = $_POST['transId'];
    if ($loginType == 'p')
        $cntR = dbSafeQuery("SELECT COUNT(*) FROM transaction WHERE id = ? AND perid = ?;", 'ii', array($transId, $loginId));
    else
        $cntR = dbSafeQuery('SELECT COUNT(*) FROM transaction WHERE id = ? AND newperid = ?;', 'ii', array ($transId, $loginId));

    if ($cntR == false || $cntR->num_rows == 0) {
        ajaxSuccess(array('status'=>'error', 'message'=>'Invalid Parameters - get assistance'));
        exit();
    }

    $cnt = $cntR->fetch_row()[0];
    $cntR->free();
    if ($cnt == 0) {
        ajaxSuccess(array('status'=>'warn', 'message'=>'You are not the owner of that transaction.</br>Only the owner can access the receipt.'));
        exit();
    }

    $response['receipt'] = trans_receipt($transId);
    $response['status'] = 'success';
} else {
    $response['error'] = 'Improper calling sequence';
}

ajaxSuccess($response);
exit();
