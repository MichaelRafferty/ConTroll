<?php

// library AJAX ProcessorvolRollover_rolloverMember.php
// Balticon Registration System
// Author: Syd Weinstein
// update the database to add the rollover

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'vol_roll';
$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'rolloverMember') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    RenderErrorAjax('No permission.');
    exit();
}

// rolloverMember
// create the rollover membership for this user
//  inputs:
//      member: info on the member to rollover
//      rollover_memId: memberhip to assign
//      user_id: user doing rollover
//  Outputs:
//      updated member record
$user_id = $_POST['user_id'];
if ($user_id != getSessionVar('user')) {
    ajaxError("Invalid credentials passed");
}
$member = $_POST['member'];
if (sizeof($member) <= 0) {
    ajaxError('no member passed');
    return;
}
$memId = $_POST['rollover_memId'];
if ($memId === null) {
    ajaxError("No rollover type passed");
}
$shortname = $_POST['rollover_shortname'];
$response['index'] = $_POST['index'];

// create the controlling transaction for the rollover
$notes = 'Volunteer Rollover';
$insTransactionSQL = <<<EOS
INSERT INTO transaction(conid,perid,userid,price,tax,withtax,paid,type,create_date)
VALUES (?,?,?,0,0,0,0,'atcon',now());
EOS;
// now insert the master transaction
$paramarray = array($conid + 1, $member['perid'], $user_id);
$typestr = 'iii';
$master_transid = dbSafeInsert($insTransactionSQL, $typestr, $paramarray);
if ($master_transid === false) {
    ajaxError('Unable to create master transaction');
    return;
}
// now insert the rollover membership

$insRegSQL = <<<EOS
INSERT INTO reg(conid,perid,price,paid,status,create_user,create_trans,memId,create_date)
VALUES (?,?,0,0,'paid',?,?,?,now());
EOS;
$paramarray = array($conid + 1, $member['perid'], $user_id, $master_transid, $memId);
$typestr = 'iiiii';
$new_regid = dbSafeInsert($insRegSQL, $typestr, $paramarray);
if ($new_regid === false) {
    ajaxError("Insert of rollover membership failed");
}
$member['roll_regid'] = $new_regid;
$member['shortname'] = $shortname;
$member['roll_tid'] = $master_transid;

$response['message'] = "Member volunteer rollover created as tid: $master_transid, id: $new_regid";
$response['member'] = $member;
$response['master_tid'] = $master_transid;
ajaxSuccess($response);
