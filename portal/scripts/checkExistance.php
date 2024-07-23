<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);



if (!(array_key_exists('action', $_POST) && array_key_exists('email', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');
$email = $_POST['email'];

$response['email'] = $email;

$pcheckid = -1;
$ncheckid = -1;
if ($loginType == 'p') {
    $mQ = <<<EOS
SELECT email_addr
FROM perinfo
WHERE id = ?;
EOS;
    $pcheckid = $loginId;
}
if ($loginType == 'n') {
    $mQ = <<<EOS
SELECT email_addr
FROM newperson
WHERE id = ?;
EOS;
    $ncheckid = $loginId;
}
$mR = dbSafeQuery($mQ, 'i', array($loginId));
if ($mR === false) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Database error, get assistance.'));
    exit();
}
$myEmail = $mR->fetch_row()[0];
$mR->free();

if (strtolower($myEmail) == strtolower($email)) {
    ajaxSuccess(array('status'=>'error', 'message'=>'You cannot request to manage yourself. That is your email address.'));
    exit();
}

// how many match
$cQ = <<<EOS
SELECT COUNT(*) AS accounts, COUNT(managedBy) AS managedBy, COUNT(managedByNew) AS managedByNew, 'p' AS accountType, MIN(id) AS acctId
FROM perinfo
WHERE email_addr = ? AND id != ?
UNION
SELECT COUNT(*) AS accounts, COUNT(managedBy) AS managedBy, COUNT(managedByNew) AS managedByNew, 'n' AS accountType, MIN(id) AS acctId
FROM newperson
WHERE email_addr = ? and perid IS NULL and id != ?;
EOS;

$cR = dbSafeQuery($cQ, 'sisi', array($email, $pcheckid, $email, $ncheckid));
if ($cR === false) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Database error, get assistance.'));
    exit();
}

$accounts = 0;
$managed = 0;
$accountType = '';
$accountId = 0;
while ($cL= $cR->fetch_assoc()) {
    $accounts += $cL['accounts'];
    if ($cL['accounts'] == 1) {
        $accountType = $cL['accountType'];
        $accountId = $cL['acctId'];
    }
    $managed += $cL['managedBy'] + $cL['managedByNew'];
}
$cR->free();

$response['countFound'] = $accounts;
$response['managedBy'] = $managed;
$response['accountType'] = $accountType;
$response['accountId'] = $accountId;
ajaxSuccess($response);