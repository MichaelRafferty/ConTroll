<?php

// library AJAX Processor: artpos_updateInventory.php
// ConTroll Registration System
// Author: Syd Weinstein
// Update fields in artItems based on atcon's artpos inlineInventory

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];

if (!(check_atcon('artsales', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$atcon = get_conf('atcon');
if (array_key_exists('inlineInventory', $atcon))
    $inlineInventory = $atcon['inlineInventory'];
else
    $inlineInventory = 1;

if ($inlineInventory != 1) {
    $message_error = 'No inventory permission.';
    RenderErrorAjax($message_error);
}

if (!(array_key_exists('ajax_request_action', $_POST) && $_POST['ajax_request_action'] == 'inlineUpdate')) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(array_key_exists('perid', $_POST) && array_key_exists('user_id', $_POST) && array_key_exists('item', $_POST) &&
    array_key_exists('updates', $_POST))) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$userId = $_POST['user_id'];
$currentPerson = $_POST['perid'];
try {
    $item = json_decode($_POST['item'], true, 512, JSON_THROW_ON_ERROR);
    $updates = json_decode($_POST['updates'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

// process the updates in the updates array against the item.
$updQ = <<<EOS
UPDATE artItems
SET 
EOS;
$typestr = '';
$values = [];
foreach ($updates as $update) {
    $updQ .= $update['field'] . ' =  ?,';
    $typestr .= 's';
    $values[] = $update['value'];
}
$updQ = mb_substr($updQ, 0, mb_strlen($updQ) - 1);
$updQ .= "\nWHERE id = ?;\n";
$typestr .= 'i';
$values[] = $item['id'];

$numUpd = dbSafeCmd($updQ, $typestr, $values);
if ($numUpd === false) {
    $response['error'] = 'Invalid Sql update statement';
} else if ($numUpd == 0) {
    $response['warn'] = 'Nothing updated';
    $response['item'] = $item;
} else {
    $response['message'] = "Art Item Updated";
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
WHERE A.id = ?;
EOS;
    $paramTypes = 'i';
    $paramArray = array($item['id']);
    $itemR = dbSafeQuery($itemQ, $paramTypes, $paramArray);
    if ($itemR === false) {
        $response['error'] = 'Update Query Error, seek assistance';
        ajaxSuccess($response);
        return;
    }
    $response['num_rows'] = $itemR->num_rows;
    if ($itemR->num_rows == 0) {
        $response['status'] = 'error';
        $response['error'] = 'Error: No Matching Art to retrieve from update';
    } else {
        $items = [];
        while ($itemL = $itemR->fetch_assoc()) {
            $items[] = $itemL;
        }
        $response['item'] = $items[0];
    }
}

$response['perid'] = $currentPerson;
$response['updates'] = $updates;

ajaxSuccess($response);
