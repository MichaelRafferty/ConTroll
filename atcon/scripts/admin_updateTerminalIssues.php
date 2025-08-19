<?php
// library AJAX Processor: admin_updateTerminalIssues.php
// ConTroll Registration System
// Author: Syd Weinstein
// Try to fix the transaction passed by querying the database and the credit card processor
// when done, refresh the list of terminal payments not yet completed/cancelled

require_once('../lib/base.php');
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/term__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$method = 'manager';
$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'update') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

if ($_POST && $_POST['transid']) {
    $transid = $_POST['transid'];
} else {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

// get the information for this transaction
$issueSQL = <<<EOS
SELECT t.id, t.paymentId, t.paymentStatus, t.checkoutId, t.create_date, t.complete_date, t.perid, t.userid, t.withtax, t.paid, 
       t.type, t.orderId, t.lastUpdate, TIMESTAMPDIFF(MINUTE, t.lastUpdate, NOW()) as minutes,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
       y.id AS payTableId, IFNULL(y.status, '') AS cardStatus, IFNULL(y.paymentId, '') AS cardPaymentId
FROM transaction t
JOIN perinfo p ON t.perid = p.id
LEFT OUTER JOIN payments y ON t.id = y.transid AND y.type NOT IN ('coupon', 'discount')
WHERE t.conid = ? AND t.id = ? AND (t.checkoutId IS NOT NULL AND IFNULL(t.paymentStatus,'') NOT IN ('COMPLETED', 'CANCELED')) 
   OR IFNULL(y.status,'') = 'APPROVED'
ORDER BY minutes DESC;
EOS;

$issueR = dbSafeQuery($issueSQL, 'ii', array($conid, $transid));
if ($issueR === false || $issueR->num_rows != 1) {
    $response['error'] = 'Query failed, seek assistance';
    ajaxSuccess($response);
    return;
}

$issue = $issueR->fetch_assoc();
$issueR->free();

$message = '';
// ok, the issue under question is in $issue, first look to see if we can change the credit card status
if ($issue['cardStatus'] != '' && $issue['cardStatus'] != 'COMPLETED' && $issue['cardPaymentId'] != '') {
    // it has a card payment id and a card status, it's just not 'COMPLETED', poll the payment record and if it's now completed
    $payment = cc_getPayment('issue', $issue['cardPaymentId'], true);
    if ($payment['paymentStatus'] == 'COMPLETED') {
        // it's now complete, update the payment record with the status and the receipt URL
        $updPaySQL = <<<EOS
UPDATE payments
SET receipt_url = ?, status = ?
WHERE id = ?;
EOS;
        $numUpd = dbSafeCmd($updPaySQL, 'ssi', array($payment['receipt_url'], $payment['status'], $issue['payTableId']));
        $message .= $numUpd . ' receipt url/card payment statuses updated<br/>';
    }
}

// now get the remaining issues
$issueSQL = <<<EOS
SELECT t.id, t.paymentId, t.paymentStatus, t.checkoutId, t.create_date, t.complete_date, t.perid, t.userid, t.withtax, t.paid, 
       t.type, t.orderId, t.lastUpdate, TIMESTAMPDIFF(MINUTE, t.lastUpdate, NOW()) as minutes,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
       y.id AS payTableId, y.status AS cardStatus, y.paymentId AS cardPaymentId
FROM transaction t
JOIN perinfo p ON t.perid = p.id
LEFT OUTER JOIN payments y ON t.id = y.transid AND y.type NOT IN ('coupon', 'discount')
WHERE t.conid = ? AND (t.checkoutId IS NOT NULL AND IFNULL(t.paymentStatus,'') NOT IN ('COMPLETED', 'CANCELED')) 
   OR IFNULL(y.status,'') = 'APPROVED'
ORDER BY minutes DESC;
EOS;

$issueR = dbSafeQuery($issueSQL, 'i', array($conid));
if ($issueR === false) {
    $response['error'] = 'Query failed, seek assistance';
    ajaxSuccess($response);
    return;
}

$issues = [];
$response['rows'] = $issueR->num_rows;
while ($issue = $issueR->fetch_assoc()) {
    $issues[] = $issue;
}
$message .= $response['rows'] . " payment issues found";
$issueR->free();
$response['issues'] = $issues;
$response['success'] = true;
$response['message'] = $message;
ajaxSuccess($response);
