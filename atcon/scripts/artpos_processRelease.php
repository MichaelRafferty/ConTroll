<?php

// library AJAX Processor: artpos_processRelease.php
// ConTroll Registration System
// Author: Syd Weinstein
// mark records as purchased release

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$perid = null;
if ($_POST && $_POST['perid']) {
    $perid = $_POST['perid'];
}

if ($perid == null) {
    $message_error = 'Calling Sequence Error';
    RenderErrorAjax($message_error);
    exit();
}
if (!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// processPayment
//  art: art items to mark purchased/released
//  new_payment: payment being added
//  pay_tid: current master transaction

$user_id = $_POST['user_id'];
if ($user_id != $_SESSION['user']) {
    ajaxError('Invalid credentials passed');
}

try {
    $art = json_decode($_POST['art'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

if (sizeof($art) <= 0) {
    ajaxError('No art to release');
    return;
}

$updArtItemsdSQL = <<<EOS
UPDATE artItems
SET status = 'purchased/released'
WHERE id = ?;
EOS;
$updArtSalesSQL = <<<EOS
UPDATE artSales
SET status = 'purchased/released'
WHERE id = ?;
EOS;
$typestr = 'i';

$num_rel = 0;
$num_remain = 0;
$num_art = 0;
foreach ($art as $row) {
    if ($row['released'] != true) {
        $num_remain++;
        continue;
    }

    $num_rel += dbSafeCmd($updArtSalesSQL, $typestr, array($row['artSalesId']));
    if ($row['type'] == 'art')
        $num_art += dbSafeCmd($updArtItemsdSQL, $typestr, array($row['id']));
}
$response['num_rel'] = $num_rel;
$response['num_remain'] = $num_remain;
$response['message'] = $num_rel . ' released, ' . $num_remain . ' remaining';
ajaxSuccess($response);
