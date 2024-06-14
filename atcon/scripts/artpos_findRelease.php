<?php

// library AJAX Processor: artpos_findRelease.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve all art in the system to cehckout for a perid

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'findRelease') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('artsales', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// findRecord:
// load all perinfo/reg records matching the search string or unpaid if that flag is passed
$perid = $_POST['perid'];
$response['perid'] = $perid;

$findPersonQ = <<<EOS
SELECT p.id, first_name, middle_name, last_name, suffix, badge_name, email_addr, address, addr_2, city, state, zip, country, phone
FROM perinfo p
WHERE p.id=?;
EOS;
$response['findPersonQ'] = $findPersonQ;
$personR = dbSafeQuery($findPersonQ, 'i', array($perid));
$response['num_rows'] = $personR->num_rows;
if ($personR->num_rows == 0) {
    $response['status'] = "error";
    $response['error'] = "No Person Found";
} else if ($personR->num_rows == 1) {
    $response['person'] = $personR->fetch_assoc();
    $response['status'] = 'success';
    // now find any art for which is final and they are the high bidder
    $findArtQ = <<<EOS
SELECT a.id, a.item_key, a.title, a.type, a.status, a.location, a.quantity, a.original_qty, a.min_price, a.sale_price, a.final_price, a.material, a.bidder,
       s.id AS artSalesId, s.transid, s.amount, s.paid, s.unit, s.quantity AS purQuantity,
       exRY.exhibitorNumber, ex.artistName, ex.exhibitorName, false AS released
FROM artItems a
JOIN exhibitorRegionYears exRY ON a.exhibitorRegionYearId = exRY.id
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
JOIN exhibitors ex ON exY.exhibitorId = ex.id
JOIN artSales s ON a.id = s.artid                
WHERE s.perid = ? AND a.conid = ? AND s.amount = s.paid AND s.status != 'Purchased/Released'
EOS;
    $findArtR = dbSafeQuery($findArtQ, 'ii', array($perid, $conid));
    $art = [];
    while ($findArtL = $findArtR->fetch_assoc()) {
        // limit items to add
        $art[] = $findArtL;
    }
    $response['art'] = $art;
    $response['message'] = $findArtR->num_rows . ' art piece' . ($findArtR->num_rows == 1 ? '' : 's') . ' ready for release';
    $findArtR->free();
}

$personR->free();

ajaxSuccess($response);
