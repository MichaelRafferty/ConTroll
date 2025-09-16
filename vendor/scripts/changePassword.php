<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array("post" => $_POST, "get" => $_GET);
$vendor = 0;

$con = get_conf('con');
$conid = $con['id'];

if(isSessionVar('id')) {
    $vendor = getSessionVar('id');
    $client = getSessionVar('eyID');
} else {
    $response['status']='error';
    $response['message']='Authentication Failure';
    ajaxSuccess($response);
    exit();
}

if($_POST['oldPassword'] == $_POST['password']) {
    $response['status']='error';
    $response['message']='Please select a new password';
    ajaxSuccess($response);
    exit();
}
if ($_POST['pwType'] == 'c') {
    $testQ = "SELECT contactPassword AS password FROM exhibitorYears WHERE id=? AND conid=?;";
    $testR = dbSafeQuery($testQ, 'ii', array($client, $conid));
} else {
    $testQ = 'SELECT password FROM exhibitors WHERE id=?;';
    $testR = dbSafeQuery($testQ, 'i', array($vendor));
}

$testPw = $testR->fetch_assoc();

if(!password_verify($_POST['oldPassword'], $testPw['password'])) {
    $response['status']='error';
    $response['message']='Authentication Failure - Incorrect Old Password';
    ajaxSuccess($response);
    exit();
} else {
    $response['pwcheck'] = 'passwd';
}

if ($_POST['pwType'] == 'c') {
    $updateQ = <<<EOS
UPDATE exhibitorYears
SET contactPassword = ?, need_new = 0
WHERE id=? AND conid=?;
EOS;
    $typestr = 'sii';
    $params = array(password_hash($_POST['password'], PASSWORD_DEFAULT), $client, $conid);
} else {
    $updateQ = <<<EOS
UPDATE exhibitors
SET password = ?, need_new = 0
WHERE id=?;
EOS;
    $typestr = 'si';
    $params = array(password_hash($_POST['password'], PASSWORD_DEFAULT), $vendor);
}

$numRows = dbSafeCmd($updateQ, $typestr, $params);
if ($numRows == 1)
    $response['status']='success';
else
    $response['status']='nothing changed';

if ($_POST['pwType'] == 'e') {
    // if the exhibitor password got changed and the email address is the same for the contact email, make that password match
    $updateQ = <<<EOS
UPDATE exhibitorYears
JOIN exhibitors e ON e.id = ?
SET exhibitorYears.contactPassword = e.password, exhibitorYears.need_new = 0
WHERE e.id = ? AND e.exhibitorEmail = exhibitorYears.contactEmail AND exhibitorYears.exhibitorId = ? AND conid = ?;
EOS;
    $numRows = dbSafeCmd($updateQ, 'iiii', array($vendor, $vendor, $vendor, $conid));
}

ajaxSuccess($response);
