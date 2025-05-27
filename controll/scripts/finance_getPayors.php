<?php
global $db_ini;

require_once "../lib/base.php";
require_once '../../lib/paymentPlans.php';

$check_auth = google_init("ajax");
$perm = "finance";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('user_perid', $_SESSION)) {
    $user_perid = $_SESSION['user_perid'];
}
else {
    ajaxError('Invalid credentials passed');
    return;
}

$con = get_conf('con');
$conid=$con['id'];

if (!array_key_exists('ajax_request_action', $_POST)) {
    $response['error'] = 'Parameter Missing';
    ajaxSuccess($response);
    exit();
}
$action=$_POST['ajax_request_action'];
if ($action != 'payorPlans') {
    $response['error'] = 'Request Error';
    ajaxSuccess($response);
    exit();
}

$payorQ = <<<EOS
WITH pmts AS (
    SELECT payorPlanId, max(PaymentNbr) AS paymentNbr, count(payorPlanId) AS paymentsMade, max(payDate) AS lastPaymentDate
    FROM payorPlanPayments
    GROUP BY payorPlanId
), lastpmt AS (
    SELECT pmts.payorPlanId, pmts.paymentsMade, pmts.lastPaymentDate, lp.amount AS lastPaymentAmt
    FROM pmts
    JOIN payorPlanPayments lp ON lp.payorPlanId = pmts.payorPlanId AND lp.paymentNbr = pmts.paymentNbr
)
SELECT  pp.id, pp.planId, pp.conid, pp.perid, pp.newperid, pp.initialAmt, pp.nonPlanAmt, pp.downPayment, pp.minPayment, pp.finalPayment,
        pp.openingBalance, pp.numPayments, pp.daysBetween, pp.payByDate, UPPER(SUBSTRING(pp.payType, 1, 1)) AS payType, pp.reminders, pp.status, pp
        .createTransaction,
        pp.balanceDue, pp.createDate, pp.updateDate, pp.updateBy,
        p.name, l.paymentsMade, l.lastPaymentDate, l.lastPaymentAmt,
        CASE 
        WHEN pi.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT(pi.first_name, ' ', pi.middle_name, ' ', pi.last_name, ' ', pi.suffix), '  *', ' '))
        ELSE THEN TRIM(REGEXP_REPLACE(CONCAT(ni.first_name, ' ', ni.middle_name, ' ', ni.last_name, ' ', ni.suffix), '  *', ' '))
        END AS fullName
FROM payorPlans pp
JOIN paymentPlans p ON pp.planId = p.id
LEFT OUTER JOIN perinfo pi ON pp.perid = pi.id
LEFT OUTER JOIN newperson ni ON pp.newperid = ni.id
LEFT OUTER JOIN lastpmt l ON pp.id = l.payorPlanId
WHERE pp.conid = ?
ORDER BY pp.createDate;
EOS;

$payorR = dbSafeQuery($payorQ, 'i', array($conid));
if ($payorR === false) {
    $response['error'] = 'Plan query error, seek assistance';;
    ajaxError($response);
}

$payorPlans = [];
while ($row = $payorR->fetch_assoc()) {
    $payorPlans[] = $row;
}
$payorR->free();
$numRows = count($payorPlans);

$response['success'] = "$numRows Payor Plans Found";
$response['payorPlans'] = $payorPlans;
$response['paymentPlans'] = getPlanConfig();

ajaxSuccess($response);
?>
