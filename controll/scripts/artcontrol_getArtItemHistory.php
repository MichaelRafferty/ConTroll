<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'art_control';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('action', $_POST) || $_POST['action'] != 'fetchHistory') {
    $response['error'] = 'Invalid Calling Sequence';
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('itemId', $_POST) && array_key_exists('artistNumber', $_POST))) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}
$itemId = $_POST['itemId'];
$artistNumber = $_POST['artistNumber'];
$response['itemId'] = $itemId;
$response['artistNumber'] = $artistNumber;

$bQ = <<<EOS
SELECT 999999999 AS historyId, id, item_key, title, type, status, location, quantity, original_qty, min_price, sale_price, final_price, 
    bidder, conid, artshow, time_updated, updatedBy, material, exhibitorRegionYearId, notes, now() AS historyDate, $artistNumber as artistNumber
FROM artItems
WHERE id = ?
UNION SELECT historyId, id, item_key, title, type, status, location, quantity, original_qty, min_price, sale_price, final_price, 
    bidder, conid, artshow, time_updated, updatedBy, material, exhibitorRegionYearId, notes, historyDate, $artistNumber as artistNumber
FROM artItemsHistory
WHERE id = ?
ORDER BY historyId desc
EOS;
$bR = dbSafeQuery($bQ, 'ii', array($itemId, $itemId));
if ($bR === false) {
    $response['error'] = 'Database error retrieving art items';
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

ajaxSuccess($response);
