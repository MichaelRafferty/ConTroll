<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'reg_staff';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}


$user_perid = $authToken->getPerid();
if (!$user_perid) {
    ajaxError('Invalid credentials passed');
    return;
}

if (!isset($_POST) || !isset($_POST['donateList']) ||  !isset($_POST['action']) || $_POST['action'] != 'donate') {
    $response['error'] = "Invalid Parameters";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('source', $_POST)) {
    $message_error = 'Source Missing';
    RenderErrorAjax($message_error);
    exit();
}
$source = $_POST['source'];

$con = get_conf('con');
$conid = $con['id'];

$donateList = $_POST['donateList'];

// build string of items to cancel, cannot use '?' prepared notation for an IN clause
$inString = '';
foreach ($donateList as $id) {
    if (is_numeric($id)) {
        $inString .= $id . ',';
    }
}

if ($inString == '') {
    $response['error'] = 'No items to donate';
    ajaxSuccess($response);
    exit();
}

$inString = substr($inString, 0, -1);
$noteMsg = "$user_perid changed the status to donated";
// update the status to donated
$updQ = <<<EOS
UPDATE reg
SET status = 'donated', updatedBy = ?
WHERE id IN ($inString);
EOS;

$num_upd = dbSafeCmd($updQ, 'i', array($user_perid));
if ($num_upd === false || $num_upd < 0) {
    $response['error'] = "Error running $updQ on $inString";
} else {
    $response['success'] = "$num_upd registrations changed";
    // insert a reg note for the successful action
    $insQ = <<<EOS
INSERT INTO regActions(userid, source, regid, action, notes)
VALUES (?, ?, ?, ?, ?);
EOS;
    $typestr = 'isiss';
    foreach ($donateList as $regId) {
        $paramarray = array($user_perid, $source, $regId, 'notes', $noteMsg);
        $new_history = dbSafeInsert($insQ, $typestr, $paramarray);
    }
}

ajaxSuccess($response);
