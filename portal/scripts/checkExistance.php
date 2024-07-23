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


// how many match
$cQ = <<<EOS
SELECT COUNT(*) AS accounts, COUNT(managedBy) AS managedBy, COUNT(managedByNew) AS managedByNew, 'p' AS accountType
FROM perinfo
WHERE email_addr = ?
UNION
SELECT COUNT(*) AS accounts, COUNT(managedBy) AS managedBy, COUNT(managedByNew) AS managedByNew, 'n' AS accountType
FROM newperson
WHERE email_addr = ? and perid IS NULL;
EOS;

$cR = dbSafeQuery($cQ, 'ss', array($email, $email));
if ($cR === false) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Database error, get assistance.'));
    exit();
}

$accounts = 0;
$managed = 0;
$accountType = '';
while ($cL= $cR->fetch_assoc()) {
    $accounts += $cL['accounts'];
    if ($cL['accounts'] == 1)
        $accountType = $cL['accountType'];
    $managed += $cL['managedBy'] + $cL['managedByNew'];
}
$cR->free();

$response['countFound'] = $accounts;
$response['managedBy'] = $managed;
$response['accountType'] = $accountType;
ajaxSuccess($response);