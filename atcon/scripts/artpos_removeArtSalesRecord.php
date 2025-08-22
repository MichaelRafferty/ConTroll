<?php

// library AJAX Processor: artpos_getArt.php
// ConTroll Registration System
// Author: Syd Weinstein
// Remove an unpaid art sales record from the database with paid = 0, if the item is 'checked in' status.

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];

if (!array_key_exists('action', $_POST) || $_POST['action'] != 'deleteUnpaid' ) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('artsales', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// delete a unpaid  artSales Record, these are ones not assigned to the user directly (becoming quicksale, or a print)
if (!(array_key_exists('artSalesId', $_POST) && array_key_exists('perid', $_POST))) {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$delSQL = <<<EOS
DELETE FROM artSales
WHERE
    id = ? AND perid = ? AND IFNULL(paid, 0) = 0
;
EOS;

    $delAllSQL = <<<EOS
DELETE FROM artSales
WHERE
    perid = ? AND IFNULL(paid, 0) = 0
;
EOS;

$artSalesId = $_POST['artSalesId'];
$perid = $_POST['perid'];
$response = [];
if ($artSalesId > 0) {
    $rowsDeleted = dbSafeCmd($delSQL, 'ii', array ($artSalesId, $perid));
} else {
    $rowsDeleted = dbSafeCmd($delAllSQL, 'i', array ($perid));
}
if ($rowsDeleted > 0)
    $response['message'] = "$rowsDeleted artSales record(s) deleted";
ajaxSuccess($response);
