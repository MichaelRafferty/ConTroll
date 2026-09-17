<?php
// library AJAX Processor: pos_buildOrder.php
// ConTroll Registration System
// Author: Syd Weinstein
// round order from cart for cash or non cash change to the order

require_once '../lib/base.php';
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_conf('con');
$conid = $con['id'];
$log = get_conf('log');
$cc = get_conf('cc');
load_cc_procs();
logInit($log['term']);

$response['conid'] = $conid;

if (!(array_key_exists('ajax_request_action', $_POST) && array_key_exists('pay_tid', $_POST) &&
    array_key_exists('orderId', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Parameter error - get assistance'));
    exit();
}

$action = $_POST['ajax_request_action'];
if ($action != 'roundOrder') {
    ajaxSuccess(array('status'=>'error', 'error'=>'Parameter error - get assistance'));
    exit();
}

// round the order Order
// pay_tid: current master transaction
// roundAmount: amount to round the order by
// orderId: existing order

$transId = $_POST['pay_tid'];
if ($transId <= 0) {
    ajaxError('No current transaction in process');
}

$roundAmount = $_POST['roundAmount'];
$orderId = $_POST['orderId'];
$source = 'atcon';


$rtn = cc_roundOrder($orderId, $roundAmount);
if ($rtn == null) {
    // note there is no reason cc_roundOrder will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
    labeled_logWrite('roundOrder-cc_roundOrder returned null',
        array ('con' => $con['label'], 'trans' => $transId, 'error' => 'Order unable to be created'));
    ajaxSuccess(array ('status' => 'error', 'error' => 'Order not built'));
    exit();
}

$response['rtn'] = $rtn;
$upT = <<<EOS
UPDATE transaction
SET rounding = ?
WHERE id = ?;
EOS;

$rows_upd = dbSafeCmd($upT, 'di', array($roundAmount, $transId));

labeled_logWrite('artpos_roundOrder-return', array('con' => $con['label'], 'trans' => $transId, 'rtn' => $rtn));
ajaxSuccess($response);
return;
