<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'exhibitor';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('exhibitorId', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}
$con = get_conf('con');
$conid = $con['id'];
$exhibitorId = $_POST['exhibitorId'];
$response['conid'] = $conid;
$response['exhibitorId'] = $exhibitorId;

$bQ = <<<EOS
SELECT 999999999 AS historyId, 'current' AS historyDate, e.*
FROM exhibitors e
WHERE id = ?
UNION SELECT *
FROM exhibitorsHistory
WHERE id = ?
ORDER BY historyId desc
EOS;
$bR = dbSafeQuery($bQ, 'ii', array($exhibitorId, $exhibitorId));
if ($bR === false) {
    $response['error'] = 'Database error retrieving memberships';
    ajaxSuccess($response);
    exit();
}
$history = [];
while ($bL = $bR->fetch_assoc()) {
    $history[] = $bL;
}
$bR->free();
$response['history'] = $history;
$response['query']=$bQ;
$response['message'] = count($history) . ' record(s) retrieved';

ajaxSuccess($response);
