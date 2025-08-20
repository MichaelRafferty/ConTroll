<?php

// library AJAX Processor: artpos_stats.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve statistics about number of active customers, # who need to pay, and # who need to be released

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$atcon = get_conf('atcon');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['stats']) {
    $stats = $_POST['stats'];
}
if (!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// load stats
$inlineInventory = getConfValue('atcon', 'inlineinventory', 1);
$allowBid = $inlineInventory == 1 ? '' : " AND a.status != 'BID'";
$activeQ = <<<EOS
SELECT a.bidder AS perid, TRIM(CONCAT_WS(' ', p.first_name, p.last_name)) AS name, COUNT(*) AS items
FROM artItems a
LEFT OUTER JOIN artSales s ON a.id = s.artid
JOIN perinfo p ON p.id = a.bidder
WHERE conid = ? AND (s.status IS NULL OR s.status != 'Purchased/Released')$allowBid
GROUP BY a.bidder, TRIM(CONCAT_WS(' ', p.first_name, p.last_name)) ;
EOS;

$activeR = dbSafeQuery($activeQ, 'i', array($conid));
$active_customers = [];
while ($activeL = $activeR->fetch_assoc()) {
    $active_customers[] = $activeL;
}
$response['active_customers'] = $active_customers;
$activeR->free();

$needPayQ = <<<EOS
SELECT s.perid, TRIM(CONCAT_WS(' ', p.first_name, p.last_name)) AS name, COUNT(*) AS items
FROM artItems a
JOIN artSales s ON a.id = s.artid
JOIN perinfo p ON p.id = s.perid
WHERE s.amount > s.paid AND a.conid= ?
GROUP BY s.perid, TRIM(CONCAT_WS(' ', p.first_name, p.last_name));
EOS;

$needPayR = dbSafeQuery($needPayQ, 'i', array($conid));
$need_pay = [];
while ($needPayL = $needPayR->fetch_assoc()) {
    $need_pay[] = $needPayL;
}
$response['need_pay'] = $need_pay;
$needPayR->free();

$needReleaseQ = <<<EOS
SELECT s.perid, TRIM(CONCAT_WS(' ', p.first_name, p.last_name)) AS name, COUNT(*) AS items
FROM artItems a
JOIN artSales s ON a.id = s.artid
JOIN perinfo p ON p.id = s.perid
WHERE s.amount = s.paid AND a.conid= ? AND a.status IN ('Sold Bid Sheet', 'Sold at Auction', 'Quicksale/Sold')
GROUP BY s.perid, TRIM(CONCAT_WS(' ', p.first_name, p.last_name));
EOS;

$needReleaseR = dbSafeQuery($needReleaseQ, 'i', array($conid));
$need_release = [];
while ($needReleaseL = $needReleaseR->fetch_assoc()) {
    $need_release[] = $needReleaseL;
}
$response['need_release'] = $need_release;
$needReleaseR->free();

ajaxSuccess($response);
