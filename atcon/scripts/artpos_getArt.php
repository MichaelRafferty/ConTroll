<?php

// library AJAX Processor: artpos_getArt.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve artItem records for purchase

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$findType = null;
if (array_key_exists('findType', $_POST)) {
    $findType = $_POST['findType'];
}
if ($findType == null) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('artsales', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// find the data based on findType:
// load all artItem records that match the criteria
$findType = $_POST['findType'];
$artistNumber = null;
if (array_key_exists('artistNumber', $_POST)) {
    $artistNumber = $_POST['artistNumber'];
}
$pieceNumber = null;
if (array_key_exists('pieceNumber', $_POST)) {
    $pieceNumber = $_POST['pieceNumber'];
}

$unitNumber = 1;
if (array_key_exists('unitNumber', $_POST)) {
    $unitNumber = $_POST['unitNumber'];
}

$itemId = null;
if (array_key_exists('itemId', $_POST)) {
    $itemId = $_POST['itemId'];
}
$itemQ = null;

$region = getSessionVar('ARTPOSRegion');
if ($region == '' || $region == null)
    $region = '%';

$response['artistNumber'] = $artistNumber;
$response['pieceNumber'] = $pieceNumber;
$response['unitNumber'] = $unitNumber;
$response['itemId'] = $itemId;
$response['findType'] = $findType;
$response['region'] = $region;

$atcon = get_conf('atcon');
if (array_key_exists('inlineInventory', $atcon))
    $inlineInventory = $atcon['inlineInventory'];
else
    $inlineInventory = 1;

if ($inlineInventory != 1) {
    $statusExclude = "AND A.status NOT IN ('Entered','Not In Show')";
} else {
    $statusExclude = '';
}

if ($itemId != null && $itemId != '') {
    $itemQ = <<<EOS
SELECT A.*, s.id AS artSalesId, s.transid, s.amount, IFNULL(s.paid, 0.00) AS paid, s.quantity AS artSalesQuantity, s.unit, t.id AS create_trans,
       ex.artistName, ex.exhibitorName, exRY.exhibitorNumber, IFNULL(s.quantity, 1) AS purQuantity
FROM artItems A
JOIN exhibitorRegionYears exRY ON exRY.id = A.exhibitorRegionYearId
JOIN exhibitorYears exY ON exY.id = exRY.exhibitorYearId
JOIN exhibitors ex ON ex.id = exY.exhibitorId
JOIN exhibitsRegionYears eRY ON eRY.id = exRY.exhibitsRegionYearId
JOIN exhibitsRegions eR ON eR.id = eRY.exhibitsRegion
LEFT OUTER JOIN artSales s ON A.id = s.artid AND IFNULL(s.paid, 0) != IFNULL(s.amount, 0)
LEFT OUTER JOIN transaction t on s.transid = t.id AND t.price != t.paid
WHERE A.id = ? $statusExclude AND eR.shortname LIKE ?;
EOS;
    $paramTypes = 'is';
    $paramArray = array($itemId, $region);
    $response['queryType'] = 'code';
} else if ($artistNumber != null && $artistNumber != '') {
    if ($pieceNumber != null && $pieceNumber != '') {
        $itemQ = <<<EOS
SELECT A.*, s.id AS artSalesId, s.transid, s.amount, IFNULL(s.paid, 0.00) AS paid, s.quantity AS artSalesQuantity, s.unit, t.id AS create_trans,
       ex.artistName, ex.exhibitorName, exRY.exhibitorNumber, IFNULL(s.quantity, 1) AS purQuantity
FROM artItems A
JOIN exhibitorRegionYears exRY ON exRY.id = A.exhibitorRegionYearId
JOIN exhibitorYears exY ON exY.id = exRY.exhibitorYearId
JOIN exhibitors ex ON ex.id = exY.exhibitorId
JOIN exhibitsRegionYears eRY ON eRY.id = exRY.exhibitsRegionYearId
JOIN exhibitsRegions eR ON eR.id = eRY.exhibitsRegion
LEFT OUTER JOIN artSales s ON A.id = s.artid AND IFNULL(s.paid, 0) != IFNULL(s.amount, 0)
LEFT OUTER JOIN transaction t on s.transid = t.id AND t.price != t.paid
WHERE exRY.exhibitorNumber = ? AND A.item_key = ? AND exY.conid = ? $statusExclude AND eR.shortname LIKE ?;
EOS;
    $paramTypes = 'iiis';
    $paramArray = array($artistNumber, $pieceNumber, $conid, $region);
    $response['queryType'] = 'piece';
    } else {
        $itemQ = <<<EOS
SELECT A.*, ex.artistName, ex.exhibitorName, exRY.exhibitorNumber, s.id AS artSalesId, s.transid, s.amount, IFNULL(s.paid, 0.00) AS paid, 
       s.quantity AS artSalesQuantity, s.unit, t.id AS create_trans,
       exRY.exhibitorNumber, IFNULL(s.quantity, 1) AS purQuantity
FROM artItems A
JOIN exhibitorRegionYears exRY ON exRY.id = A.exhibitorRegionYearId
JOIN exhibitorYears exY ON exY.id = exRY.exhibitorYearId
JOIN exhibitors ex ON ex.id = exY.exhibitorId
JOIN exhibitsRegionYears eRY ON eRY.id = exRY.exhibitsRegionYearId
JOIN exhibitsRegions eR ON eR.id = eRY.exhibitsRegion
LEFT OUTER JOIN artSales s ON A.id = s.artid AND IFNULL(s.paid, 0) != IFNULL(s.amount, 0)
LEFT OUTER JOIN transaction t on s.transid = t.id AND t.price != t.paid
WHERE exRY.exhibitorNumber = ? AND exY.conid = ? $statusExclude AND eR.shortname LIKE ?;
EOS;
        $paramTypes = 'iis';
        $paramArray = array($artistNumber, $conid, $region);
        $response['queryType'] = 'artist';
    }
}

if ($itemQ == NULL) {
    $response['status'] = 'error';
    $response['error'] = 'Please enter at least and Artist Number or an Item Code Scan string';
} else {

    $itemR = dbSafeQuery($itemQ, $paramTypes, $paramArray);
    if ($itemR === false) {
        $response['error'] = 'Query Error, seek assistance';
        ajaxSuccess($response);
        return;
    }
    $response['num_rows'] = $itemR->num_rows;
    if ($itemR->num_rows == 0) {
        $response['status'] = "error";
        $response['error'] = "No Matching Art Found";
    } else {
        $items = [];
        while ($itemL = $itemR->fetch_assoc()) {
            $items[] = $itemL;
        }
        $response['items'] = $items;
        $response['status'] = "success";
        $response['message'] = $itemR->num_rows . " Matching Art Items Found";
    }
}

ajaxSuccess($response);
