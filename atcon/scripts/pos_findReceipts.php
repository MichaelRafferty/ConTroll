<?php
// library AJAX Processor: pos_findReceipts.php
// ConTroll Registration System
// Author: Syd Weinstein
// Retrieve credit card receipts paid for by people in the cart

require_once '../lib/base.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_conf('con');
$controll = get_conf('controll');
$usePortal = $controll['useportal'];
$conid = intval($con['id']);
$ajax_request_action = '';

if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'findReceipts') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

// find receipts:
// load all credit card receipts based on the perid's in the cart
$cart = $_POST['cart_perinfo'];
$cart_perinfo = json_decode($cart, true);
// get all the regid's and perid's from the cart
$perids = [];
$regids = [];
foreach ($cart_perinfo as $value) {
    $perids[] = $value['perid'];
    foreach ($value['memberships'] as $membership) {
        $regids[] = $membership['regid'];
    }
}
$perids = array_unique($perids, SORT_NUMERIC);
$regids = implode(',', array_unique($regids, SORT_NUMERIC));

$recQ = <<<EOS
SELECT t.perid, p.transid, p.ccPaymentId, p.time, 
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', w.first_name, w.middle_name, w.last_name, w.suffix), ' +', ' ')) AS fullName
FROM reg r
JOIN transaction t ON t.id = r.complete_trans AND t.perid = r.perid
JOIN payments p ON t.id = p.transid
JOIN perinfo w ON w.id = t.perid
WHERE r.id IN ($regids) AND p.receipt_url LIKE 'https://%';
EOS;
$receipts = [];
$recR = dbQuery($recQ);
if ($recR !== false) {
    while ($recL = $recR->fetch_assoc()) {
        $receipts[] = $recL;
    }
    $recR->free();
}
$response['receipts'] = $receipts;

ajaxSuccess($response);
