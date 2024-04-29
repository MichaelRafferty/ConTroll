<?php

// library AJAX Processor: artpos_stats.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve statistics about number of active customers, # who need to pay, and # who need to checkout

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

$activeQ = <<<EOS
SELECT COUNT(DISTINCT bidder)
FROM artItems
WHERE conid = ?;
EOS;

$activeR = dbSafeQuery($activeQ, 'i', array($conid));
$response['active_customers'] = $activeR->fetch_row()[0];
$activeR->free();

$needPayQ = <<<EOS
SELECT COUNT(DISTINCT perid)
FROM artItems a
JOIN artSales s ON a.id = s.artid
WHERE s.amount > s.paid AND a.conid= ?;
EOS;

$needPayR = dbSafeQuery($needPayQ, 'i', array($conid));
$response['need_pay'] = $needPayR->fetch_row()[0];
$needPayR->free();

$needCheckoutQ = <<<EOS
SELECT COUNT(DISTINCT perid)
FROM artItems a
JOIN artSales s ON a.id = s.artid
WHERE s.amount = s.paid AND a.conid= ? AND a.status IN ('Sold Bid Sheet','Sold at Auction', 'Quicksale/Sold');
EOS;

$needCheckoutR = dbSafeQuery($needCheckoutQ, 'i', array($conid));
$response['need_check'] = $needCheckoutR->fetch_row()[0];
$needCheckoutR->free();

ajaxSuccess($response);
