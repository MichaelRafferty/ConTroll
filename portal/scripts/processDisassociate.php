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

if (!array_key_exists('managedBy', $_POST)) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(array_key_exists('id', $_SESSION) && array_key_exists('idType', $_SESSION))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$personId = $_SESSION['id'];
$personType = $_SESSION['idType'];

if ($personType == 'p') {
    $personQ = <<<EOS
SELECT id, managedBy, NULL as managedByNew
FROM perinfo
WHERE id = ?;
EOS;
    $personU = <<<EOS
UPDATE perinfo
SET managedBy = NULL
WHERE id = ?;
EOS;
} else {
    $personQ = <<<EOS
SELECT id, managedBy, managedByNew
FROM newperson
WHERE id = ?;
EOS;
    $personU = <<<EOS
UPDATE newperson
SET managedBy = NULL, managedByNew = NULL
WHERE id = ?;
EOS;
}
$personR = dbSafeQuery($personQ, 'i', array($personId));
if ($personR === false || $personR->num_rows < 1) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Invalid Login Session, Please log out and back in again, if it still fails, seek assistance.'));
    exit();
}

$personL = $personR->fetch_assoc();
$personR->free();

if ($personL['managedBy'] == NULL && $personL['managedByNew'] == NULL) {
    ajaxSuccess(array('status'=>'warn', 'message'=>'Your account is currently not managed.'));
    exit();
}

$num_rows = dbSafeCmd($personU, 'i', array($personId));
if ($num_rows == 1) {
    ajaxSuccess(array('status'=>'success', 'message'=>'Your account has been changed to unmanaged.'));
} else {
    ajaxSuccess(array('status'=>'success', 'message'=>'Your account already was unmanaged.'));
}
