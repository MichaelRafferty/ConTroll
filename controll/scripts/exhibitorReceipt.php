<?php
require_once('../lib/base.php');
require_once('../../lib/receipt.php');
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'exhibitor';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$conid=getConfValue('con', 'id');
$response['conid'] = $conid;

if (!isSessionVar('id')) {
    // get exhibitor id from post data
    if (!isset($_POST['exhibitorId'])) {
        ajaxError('invalid calling sequence');
        exit();
    }
    $exhId = $_POST['exhibitorId'];
} else {
    $exhId = getSessionVar('id');
}

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
