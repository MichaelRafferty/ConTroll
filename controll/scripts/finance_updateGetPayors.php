<?php
// Finance Plan related updates/gets
// Driven by ajax_request_action
//      getPayorPlans = just get the plan data
//      cancelPlan = cancel the plan in id and return the updated data for all plans
require_once "../lib/base.php";
require_once '../../lib/paymentPlans.php';
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'finance';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$user_perid = $authToken->getPerid();
if (!$user_perid) {
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
switch ($action) {
    case 'getPayorPlans':
        // nothing special to do here
        break;

    case 'cancelPlan':
        if (!array_key_exists('cancelId', $_POST)) {
            $response['error'] = 'Request Error';
            ajaxSuccess($response);
            exit();
        }
        $cancelId = $_POST['cancelId'];
        // check if status of this plan is 'active'
        $checkQ = <<<EOS
SELECT status
FROM payorPlans
WHERE id = ?;
EOS;
        $checkR = dbSafeQuery($checkQ, 'i', array($cancelId));
        if ($checkR === false) {
            $response['error'] = "Error: Unable to get status of plan to cancel";
            ajaxSuccess($response);
            exit();
        }
        $checkStatus = $checkR->fetch_row()[0];
        $checkR->free();
        if ($checkStatus != 'active') {
            $response['error'] = "Error: Cannot cancel plan $cancelId, as it's status of $checkStatus is not active";
            ajaxSuccess($response);
            exit();
        }
        // cancel the plan
        // step 1 - mark all items in that plan as 'unpaid' (uses planid index for speed)
        $updReg = <<<EOS
UPDATE reg
SET status = 'unpaid'
WHERE status = 'plan' AND planId = ?;
EOS;
        $numUpdated = dbSafeCmd($updReg, 'i', array($cancelId));
        if ($numUpdated === false) {
            $response['error'] = "Error: Cannot cancel plan $cancelId, unable to change the memberships from 'plan' to 'unpaid', seek assistance.";
            ajaxSuccess($response);
            exit();
        }
        $updPlan = <<<EOS
UPDATE payorPlans
SET status = 'cancelled'
WHERE id = ?;
EOS;
        $numCancelled = dbSafeCmd($updPlan, 'i', array($cancelId));
        if ($numCancelled === false) {
            $response['error'] = "Error: Cannot cancel plan $cancelId, marked memberships to unpaid, but cannot change the status of the plan, seek assistance.";
            ajaxSuccess($response);
            exit();
        }
        $response['success'] = "$numUpdated registrations changed from 'plan' to 'unpaid' and $numCancelled plan(s) cancelled.";
        break;

    default:
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
        WHEN pi.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT_WS(' ', pi.first_name, pi.middle_name, pi.last_name, pi.suffix), ' +', ' '))
        ELSE TRIM(REGEXP_REPLACE(CONCAT_WS(' ', ni.first_name, ni.middle_name, ni.last_name, ni.suffix), ' +', ' '))
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
    $response['error'] = 'Plan query error, seek assistance';
    ajaxError($response);
}

$payorPlans = [];
while ($row = $payorR->fetch_assoc()) {
    $payorPlans[] = $row;
}
$payorR->free();
$numRows = count($payorPlans);

if (!array_key_exists('success', $response))
    $response['success'] = "$numRows Payor Plans Found";
$response['payorPlans'] = $payorPlans;
$response['paymentPlans'] = getPlanConfig();

ajaxSuccess($response);
