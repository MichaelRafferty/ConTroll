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

$token = null;
if(isSessionVar('id')) {
    $vendor = getSessionVar('id');
    $client = getSessionVar('eyID');
} else if (isSessionVar('pwToken')) {
    $token = getSessionVar('pwToken');
    unsetSessionVar('pwToken');
    $age = time() - $token['ts'];
    if ($age > 90 * 60) { // allow an extra 1/2 hour for the submit function
        $response['status']='error';
        $response['message']='Password Reset Link has expired, please get a new one.';
        ajaxSuccess($response);
        exit();
    }
} else {
    $response['status']='error';
    $response['message']='Authentication Failure';
    ajaxSuccess($response);
    exit();
}

if($token == null && $_POST['oldPassword'] == $_POST['password']) {
    $response['status']='error';
    $response['message']='Please select a new password';
    ajaxSuccess($response);
    exit();
}

switch ($_POST['pwType']) {
    case 'c':
        $testQ = "SELECT contactPassword AS password FROM exhibitorYears WHERE id=? AND conid=?;";
        $testR = dbSafeQuery($testQ, 'ii', array ($client, $conid));
        break;
    case 'e':
        $testQ = 'SELECT password FROM exhibitors WHERE id=?;';
        $testR = dbSafeQuery($testQ, 'i', array ($vendor));
        break;
    case 'a':
        $testR = null;
}

if ($testR != null) {
    $testPw = $testR->fetch_assoc();

    if (!password_verify($_POST['oldPassword'], $testPw['password'])) {
        $response['status'] = 'error';
        $response['message'] = 'Authentication Failure - Incorrect Old Password';
        ajaxSuccess($response);
        exit();
    } else {
        $response['pwcheck'] = 'passwd';
    }
}
    $numRows = 0;
    switch ($_POST['pwType']) {
        case 'c':
            $updateQ = <<<EOS
UPDATE exhibitorYears
SET contactPassword = ?, need_new = 0
WHERE id=? AND conid=?;
EOS;
            $typestr = 'sii';
            $params = array(password_hash(ifnull($_POST['password'], ''), PASSWORD_DEFAULT), $client, $conid);
            $numRows = dbSafeCmd($updateQ, $typestr, $params);
            break;
        case 'e':
            $updateQ = <<<EOS
UPDATE exhibitors
SET password = ?, need_new = 0
WHERE id=?;
EOS;
            $typestr = 'si';
            $params = array(password_hash(ifnull($_POST['password'], ''), PASSWORD_DEFAULT), $vendor);
            $numRows = dbSafeCmd($updateQ, $typestr, $params);
            break;
        case 'a':
            $updateQ = <<<EOS
UPDATE portalTokenLinks
SET useCnt = useCnt + 1, useIP = ?, useTS = now()
WHERE id = ?;
EOS;
            $typestr = 'si';
            $params = array($_SERVER['REMOTE_ADDR'], $token['lid']);
            $numRows = dbSafeCmd($updateQ, $typestr, $params);
            $updateQ = <<<EOS
UPDATE exhibitors
SET password = ?, need_new = 0
WHERE exhibitorEmail = ?;
EOS;
            $typestr = 'ss';
            $params = array(password_hash(ifnull($_POST['password'], ''), PASSWORD_DEFAULT), $token['email']);
            $numRows = dbSafeCmd($updateQ, $typestr, $params);
            $updateQ = <<<EOS
UPDATE exhibitorYears
SET contactPassword = ?, need_new = 0
WHERE contactEmail = ? AND conid = ?;
EOS;
            $typestr = 'ssi';
            $params = array(password_hash(ifnull($_POST['password'], ''), PASSWORD_DEFAULT), $token['email'], $conid);
            $numRows += dbSafeCmd($updateQ, $typestr, $params);
}

if ($numRows === false) {
    $response['status'] = 'error';
    $response['message'] = 'Password change query failed, seek assistance';
} else if ($numRows == 1)
    $response['status']='success';
else if ($numRows == 0)
    $response['status']='nothing changed';
else
    $response['status']="$numRows passwords updated";

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
