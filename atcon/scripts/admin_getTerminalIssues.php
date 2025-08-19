<?php
// library AJAX Processor: admin_getTerminalIssues.php
// Balticon Registration System
// Author: Syd Weinstein
// refresh the list of terminal payments not yet completed/cancelled

require_once('../lib/base.php');

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
if ($ajax_request_action != 'issues') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$issueSQL = <<<EOS
SELECT t.id, t.paymentId, t.paymentStatus, t.checkoutId, t.create_date, t.complete_date, t.perid, t.userid, t.withtax, t.paid, 
       t.type, t.orderId, t.lastUpdate, TIMESTAMPDIFF(MINUTE, t.lastUpdate, NOW()) as minutes,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' ')) AS fullName,
       y.id AS payTableId, y.status AS cardStatus, y.paymentId AS cardPaymentId
FROM transaction t
JOIN perinfo p ON t.perid = p.id
LEFT OUTER JOIN payments y ON t.id = y.transid
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
$issueR->free();
$response['issues'] = $issues;
$response['success'] = true;
$response['message'] = $response['rows'] . " payment issues found";
ajaxSuccess($response);
