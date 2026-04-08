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

if (!array_key_exists('perid', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}
$con = get_conf('con');
$conid = $con['id'];
$perid = $_POST['perid'];
$response['conid'] = $conid;
$response['perid'] = $perid;
if (array_key_exists('limitConid', $_POST)) {
    $limitConid = $_POST['limitConid'];
    if ($limitConid > ($conid + 1))
        $limitConid = $conid + 1;
    if ($limitConid < $conid - 20)
        $limitConid = $conid;
} else {
    $limitConid = $conid;
}
$response['limitConid'] = $limitConid;

$bQ = <<<EOS
SELECT r.*, m.label
FROM reg r
JOIN memLabel m ON r.memId = m.id
WHERE r.perid = ? AND r.conid = ?;
EOS;
$bR = dbSafeQuery($bQ, 'ii', array($perid, $limitConid));
if ($bR === false) {
    $response['error'] = 'Database error retrieving memberships';
    ajaxSuccess($response);
    exit();
}
$memberships = [];
while ($bL = $bR->fetch_assoc()) {
    $memberships[] = $bL;
}
$bR->free();
$response['memberships'] = $memberships;

$response['query']=$bQ;

ajaxSuccess($response);
