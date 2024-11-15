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
$log = get_conf('log');
$regadminemail = $conf['regadminemail'];

$response['conid'] = $conid;


if (!(array_key_exists('email', $_POST) && array_key_exists('currentPersonId', $_POST) &&
    array_key_exists('currentPersonType', $_POST) && array_key_exists('action', $_POST))) {
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

$currentPersonId = $_POST['currentPersonId'];
$currentPersonType = $_POST['currentPersonType'];

if ($currentPersonType == 'n' && $resolveUpdates != null) {
    $updateMap = $resolveUpdates['remap'];
    if (array_key_exists($currentPersonId, $updateMap)) {
        $currentPersonType = 'p';
        $currentPersonId = $updateMap[$currentPersonId];
    }
}

$email = trim($_POST['email']);
$action = $_POST['action'];
$lcemail = strtolower($email);

if ($action != 'validate') {
    ajaxSuccess(array('status'=>'error', 'message'=>'Unknown request - get assistance'));
    exit();
}

$ptable = $currentPersonType == 'p' ? 'perinfo' : 'newperson';
// check that this is a change
$curQ = <<<EOS
SELECT email_addr
FROM $ptable
WHERE id = ?;
EOS;

$curR = dbSafeQuery($curQ, 'i', array($currentPersonId));
if ($curR === false || $curR->num_rows != 1) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Invalid request - get assistance'));
    exit();
}
$curEmail = trim($curR->fetch_row()[0]);
$response['curEmail'] = $curEmail;
$curEmail = strtolower($curEmail);

if ($curEmail == $lcemail) {
    ajaxSuccess(array('status'=>'warn', 'message'=>'This is the current email address, nothing to change.'));
    exit();
}

// both our own account and managed accounts can use our identities as their email address
$vQ = <<<EOS
SELECT DISTINCT LOWER(TRIM(email_addr))
FROM perinfoIdentities
WHERE perid = ?
UNION SELECT DISTINCT LOWER(TRIM(email_addr))
FROM perinfo
WHERE id = ?
EOS;
$vR = dbSafeQuery($vQ, 'ii', array($personId, $personId));
if ($vR === false) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Error finding valid email addresses for you - get assistance'));
    exit();
}
$validEmails = array();
while ($vL = $vR->fetch_row()) {
    $validEmails[] = $vL[0];
}
$vR->free();

// ok, check that we can change to this email
if ($personId == $currentPersonId && $personType == $currentPersonType) {
    // we are the logged in account holder, only ourselves
    if ($currentPersonType == 'n') {
        ajaxSuccess(array('status'=>'warn', 'message'=>'You can not change your email address until you are assigned a permanent ID.' .
            "<br/>Please contact registration at $regadminemail for assistance."));
        exit();
    }

    if (count($validEmails) == 0) {
        ajaxSuccess(array('status'=>'error', 'message'=>'You have no other validated email addresses.' .
            '<br/>Use the Add New section of Identities in Account Settings to add additional validated email addresses for you account.'));
        exit();
    }

    if (!in_array($lcemail, $validEmails)) {
        ajaxSuccess(array('status'=>'error', 'message'=>"The email, $email, is not one of your validated email addresses." .
            '<br/>Use the Add New section of Identities in Account Settings to add additional validated email addresses for you account.'));
        exit();
    }

    // ok, it's valid, and it's one of ours, allow things to continue
} else {
    $mfield = $personType == 'p' ? 'managedBy' : 'managedByNew';
    // its not the account holder, add in the email addresses of everyone we manage
    $vQ = <<<EOS
SELECT DISTINCT LOWER(TRIM(email_addr))
FROM perinfo
WHERE $mfield = ? AND NOT (first_name = 'merged' AND middle_name = 'into')
UNION SELECT DISTINCT LOWER(TRIM(email_addr))
FROM newperson
WHERE $mfield = ? AND NOT (first_name = 'merged' AND middle_name = 'into')
EOS;
    $typeStr = 'ii';
    $values = array($personId, $personId);
    if ($personType == 'N') {
        $vQ .= <<<EOS
UNION SELECT DISTINCT LOWER(TRIM(email_addr))
FROM newperson
WHERE id = ?
EOS;
        $typeStr .= 'i';
        $values[] = $personId;
    }
    $vQ .= ';';
    $vR = dbSafeQuery($vQ, $typeStr, $values);
    if ($vR === false) {
        ajaxSuccess(array('status'=>'error', 'message'=>'Error finding valid email addresses for you - get assistance'));
        exit();
    }
    $validEmails = array();
    while ($vL = $vR->fetch_row()) {
        $validEmails[] = $vL[0];
    }
    $vR->free();

    if (!in_array($lcemail, $validEmails)) {
        ajaxSuccess(array('status'=>'error', 'message'=>"The email, $email, is not one of your validated email addresses nor is it one of the people you manage.<br/>" .
            "Please ask them to change it themselves, otherwise contact $regadminemail for assistance."));
        exit();
    }
}
$response['currentPersonType'] = $currentPersonType;
$response['currentPeron'] = $currentPersonId;
$response['personId'] = $personId;
$response['personType'] = $personType;

$uQ = <<<EOS
UPDATE $ptable
SET email_addr = ?, updatedBy = ?
WHERE id = ?;
EOS;
$rows_upd = dbSafeCmd($uQ, 'sii', array($email, $personId, $currentPersonId));

if ($rows_upd === false) {
    ajaxSuccess(array('status'=>'error', 'f message'=>'Error updating email addresses'));
    exit();
}


$response['rows_upd'] = $rows_upd;
$response['status'] = 'success';
$response['logmessage'] = "$rows_upd rows updated, email address update successful from $curEmail to $email";
$response['message'] = "Email address successfully updated from $curEmail to $email";
logInit($log['reg']);
logWrite($response);

ajaxSuccess($response);
