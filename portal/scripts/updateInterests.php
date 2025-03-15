<?php
require_once('../lib/base.php');
require_once('../../lib/log.php');
require_once('../../lib/interests.php');

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

if (!(array_key_exists('currentPerson', $_POST) && array_key_exists('currentPersonType', $_POST)
    && array_key_exists('existingInterests', $_POST) && array_key_exists('newInterests', $_POST))) {
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

$personId = getSessionVar('id');
$personType = getSessionVar('idType');

$currentPerson = $_POST['currentPerson'];
$currentPersonType = $_POST['currentPersonType'];

$response['currentPersonType'] = $currentPersonType;
$response['currentPeron'] = $currentPerson;
$response['personId'] =$personId;
$response['personType'] = $personType;
$rows_upd = updateMemberInterests($conid, $currentPerson, $currentPersonType, $personId, $personType);

$response['rows_upd'] = $rows_upd;
$response['status'] = 'success';
$response['logmessage'] = $rows_upd == 0 ? "No changes" : "$rows_upd interests updated";
$response['message'] = 'Interests successfully updated';
logInit($log['reg']);
logWrite($response);
ajaxSuccess($response);
