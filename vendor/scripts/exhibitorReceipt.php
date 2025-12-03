<?php
require_once('../lib/base.php');
require_once('../../lib/receipt.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);
$conid=getConfValue('con', 'id');
$response['conid'] = $conid;

if (!isSessionVar('id')) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Session Failure'));
    exit;
}

$exhId = getSessionVar('id');

$response = array("post" => $_POST, "get" => $_GET);

// which space purchased
if (!array_key_exists('regionYearId', $_POST)) {
    ajaxError("invalid calling sequence");
    exit();
}
$regionYearId = $_POST['regionYearId'];

$response = null;
$transactions = spaceGetPaymentTrans($exhId, $regionYearId);
foreach ($transactions as $transaction) {
    $data = trans_receipt($transaction);
    if ($response == null)
        $response = $data;
    else {
        $response['receipt'] .= "\n\n" . $data['receipt'];
        $response['receipt_html'] .= "<br/>\n" . $data['receipt_html'];
        $response['receipt_tables'] .= "\n\n" . $data['receipt_tables'];
    }
}
ajaxSuccess($response);
