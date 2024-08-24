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

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
if ($resolveUpdates != null && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
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

if ($loginType == 'p')
    $pfield = 'managedBy';
else
    $pfield = 'managedByNew';


// how many match
$cQ = <<<EOS
SELECT managedBy, managedByNew, 'p' AS accountType, id
FROM perinfo
WHERE email_addr = ? AND id != ? AND NOT (first_name = 'Merged' AND middle_name = 'into')
UNION
SELECT managedBy, managedByNew, 'n' AS accountType, id
FROM newperson
WHERE email_addr = ? and perid IS NULL and id != ? AND  NOT (first_name = 'Merged' AND middle_name = 'into');
EOS;

$cR = dbSafeQuery($cQ, 'sisi', array($email, $pcheckid, $email, $ncheckid));
if ($cR === false) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Database error, get assistance.'));
    exit();
}

$accounts = 0;
$managedByMe = 0;
$managedByOther = 0;
$accountType = '';
$accountId = 0;
while ($cL= $cR->fetch_assoc()) {
    $accounts++;
    if ($cL['managedBy'] != null) {
        if ($loginType == 'p' && $cL['managedBy'] == $loginId) {
            $managedByMe++;
        } else {
            $mangedByOther++;
        }
    } else if ($cL['managedByNew'] != null) {
        if ($loginType == 'n' && $cL['mangedBy'] == $loginId) {
            $managedByMe++;
        } else {
            $mangedByOther++;
        }
    } else {
        $accountType = $cL['accountType'];
        $accountId = $cL['id'];
    }
}
$cR->free();

$response['countFound'] = $accounts;
$response['managedByMe'] = $managedByMe;
$response['managedByOther'] = $managedByOther;
$response['accountType'] = $accountType;
$response['accountId'] = $accountId;
ajaxSuccess($response);