<?php
// items related to using payment Plans

function getPaymentPlans($includeAccount = false) : array {
    $data = [];
// get payment plan templates
    $plans = array();
    $QQ = <<<EOS
SELECT *
FROM paymentPlans
WHERE active = 'Y'
ORDER BY sortorder;
EOS;
    $QR = dbQuery($QQ);
    while ($row = $QR->fetch_assoc()) {
        if ($row['catList'] != null && $row['catList'] != '') {
            $row['catListArray'] = explode(',', $row['catList']);
        }
        if ($row['memList'] != null && $row['memList'] != '') {
            $row['memListArray'] = explode(',', $row['memList']);
        }
        if ($row['excludeList'] != null && $row['excludeList'] != '') {
            $row['excludeListArray'] = explode(',', $row['excludeList']);
        }
        if ($row['portalList'] != null && $row['portalList'] != '') {
            $row['portalListArray'] = explode(',', $row['portalList']);
        }
        $plans[$row['id']] = $row;
    }
    $QR->free();
    $data['plans'] = $plans;

    if ($includeAccount) {
        $accountId = getSessionVar('id');
        $accountType = getSessionVar('idType');
        if ($accountType == 'p') {
            $pfield = 'perid';
        } else {
            $pfield = 'newperid';
        }

        // the plans for this payor
        $QQ = <<<EOS
SELECT pp.*, p.name
FROM payorPlans pp
JOIN paymentPlans p on (pp.planId = p.id)
WHERE $pfield = ?;
EOS;
        $QR = dbSafeQuery($QQ, 'i', array($accountId));
        $payorPlans = array();
        while ($row = $QR->fetch_assoc()) {
            $payorPlans[$row['id']] = $row;
        }
        $QR->free();

        // and their payments to date
        $QQ = <<<EOS
SELECT pp.*, t.perid AS transactionPerid
FROM payorPlanPayments pp
JOIN payorPlans p ON p.id = pp.payorPlanId
JOIN transaction t ON t.id = pp.transactionId
WHERE p.$pfield = ?
ORDER BY payorPlanId, PaymentNbr;
EOS;

        $currentPlan = null;
        $currentPayments = array();
        $QR = dbSafeQuery($QQ, 'i', array($accountId));
        $numPayorPayments = 0;
        while ($row = $QR->fetch_assoc()) {
            if ($currentPlan != $row['payorPlanId']) {
                if ($currentPlan != null) {
                    $payorPlans[$currentPlan]['payments'] = $currentPayments;
                    $payorPlans[$currentPlan]['numPayorPayments'] = $numPayorPayments;
                    $currentPayments = array();
                    $numPayorPayments = 0;
                }
                $currentPlan = $row['payorPlanId'];
            }

            $currentPayments[$row['paymentNbr']] = $row;
            if ($row['transactionPerid'] == $payorPlans[$currentPlan]['perid'])
                $numPayorPayments++;
        }
        if ($currentPlan != null) {
            $payorPlans[$currentPlan]['payments'] = $currentPayments;
            $payorPlans[$currentPlan]['numPayorPayments'] = $numPayorPayments;
        }
        $data['payorPlans'] = $payorPlans;
    }

    return $data;
}

function getPlanConfig() : array {
    // get payment plan templates
    $plans = array();
    $QQ = <<<EOS
WITH planUsage AS (
    SELECT planId, count(*) AS uses
    FROM payorPlans
    GROUP BY planId
)
SELECT p.*, IFNULL(pu.uses, 0) AS uses
FROM paymentPlans p
LEFT OUTER JOIN planUsage pu ON (pu.planId = p.id)
WHERE active = 'Y'
ORDER BY sortorder;
EOS;
    $QR = dbQuery($QQ);
    while ($row = $QR->fetch_assoc()) {
        if ($row['catList'] != null && $row['catList'] != '') {
            $row['catListArray'] = explode(',', $row['catList']);
        }
        if ($row['memList'] != null && $row['memList'] != '') {
            $row['memListArray'] = explode(',', $row['memList']);
        }
        if ($row['excludeList'] != null && $row['excludeList'] != '') {
            $row['excludeListArray'] = explode(',', $row['excludeList']);
        }
        if ($row['portalList'] != null && $row['portalList'] != '') {
            $row['portalListArray'] = explode(',', $row['portalList']);
        }
        $plans[] = $row;
    }
    $QR->free();
    return $plans;
}

function whatMembershipsInPlan($memberships, $computedPlan) : array {

    if ($computedPlan == null) {
        $planData = null;
    } else {
        $planData = $computedPlan['plan'];
    }

    if ($planData == null) {
        foreach ($memberships as $key => $membership) {
            $memberships[$key]['inPlan'] = false;
        }
        return $memberships;
    }

    $memList = null;
    $catList = null;
    $excludeList = null;

    if ($planData['catList'] != null && $planData['catList'] != '') {
        $catList = explode(',', $planData['catList']);
    }

    if ($planData['memList'] != null && $planData['memList'] != '') {
        $memList = explode(',', $planData['memList']);
    }

    if ($planData['excludeList'] != null && $planData['excludeList'] != '') {
        $excludeList = explode(',', $planData['excludeList']);
    }

    foreach ($memberships as $key => $membership) {
        if ($membership['status'] != 'unpaid') {
            $memberships[$key]['inPlan'] = false;
            continue;
        }

        if ($excludeList != null && in_array($membership['memId'], $excludeList)) {
            $memberships[$key]['inPlan'] = false;
            continue;
        }

        if ($catList != null && in_array($membership['memCategory'], $catList)) {
            $memberships[$key]['inPlan'] = true;
            continue;
        }

        if ($memList != null && !in_array($membership['memId'], $memList)) {
            $memberships[$key]['inPlan'] = true;
            continue;
        }

        $memberships[$key]['inPlan'] = false;
    }

    return $memberships;
}

//// payment plan modals
// draw_customizePlanModal- main payment modal popup
function draw_customizePlanModal($from) : void  {
    ?>
    <div id='customizePlanModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='customizePlan' aria-hidden='true' style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='customizePlanTitle'>
                        <strong>Customize Payment Plan</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid' id="customizePlanBody">
                    </div>
                    <div class="container-fluid">
                        <div class='row'>
                            <div class='col-sm-12' id='customizePlanMessageDiv'></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex='10101'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='customizePlanSubmit' onClick='paymentPlans.makePlan("<?php echo $from; ?>")' tabindex='20002'>Create Plan and pay amount due today of ???</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// draw_payPlanModal- make a payment against a plan
function draw_payPlanModal($from) : void {
    ?>
    <div id='payPlanModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='payPlan' aria-hidden='true' style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='payPlanTitle'>
                        <strong>Make a Plan Payment</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid' id="payPlanBody">
                    </div>
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-12' id='payPlanMessageDiv'></div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex='10101'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='payPlanSubmit' onClick='paymentPlans.makePlanPayment("<?php echo $from; ?>")' tabindex='20102'>Make Plan Payment</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// computeNextPaymentDue - compute all the things you need to display the next payment due
function computeNextPaymentDue($payorPlan, $plans, $dolfmt, $currency) : array {
    $now = time();

    $planid = $payorPlan['planId'];
    $plan = $plans[$planid];
    if (array_key_exists('payments', $payorPlan) && array_key_exists('numPayorPayments', RpayorPlan) && $payorPlan['numPayorPayments'] > 0) {
        $payments = $payorPlan['payments'];
        $numPmts = $payorPlan['numPayorPayments'];
        $lastPayment = $payments[$numPmts];
        $lastPaidDate = date_format(date_create($lastPayment['payDate']), 'Y-m-d');
        // numPmts + 1 because we are looking for when the next payment (not the one that just got paid) is due.
        $nextPayDueDate = date_add(date_create($payorPlan['createDate']), date_interval_create_from_date_string((($numPmts + 1) * $payorPlan['daysBetween']) - 1 . ' days'));
        $nextPayDue = date_format($nextPayDueDate, 'Y-m-d');
        $minAmtNum = min((float)$payorPlan['minPayment'], (float)$payorPlan['balanceDue']);
        $minAmt = $dolfmt->formatCurrency($minAmtNum, $currency);
    } else {
        $numPmts = 0;
        $lastPayment = 'None';
        $lastPaidDate = 'None';
        $nextPayDueDate = date_add(date_create($payorPlan['createDate']), date_interval_create_from_date_string($payorPlan['daysBetween'] - 1 . ' days'));
        $nextPayDue = date_format($nextPayDueDate, 'Y-m-d');
        $minAmtNum = $payorPlan['minPayment'];
        $minAmt = $dolfmt->formatCurrency((float) $payorPlan['minPayment'], $currency);
    }
    if ($payorPlan['status'] != 'active') {
        $nextPayDue = '';
        $minAmt = '';
        $minAmtNum = 0;
        $dayPastDue = '';
        $numPmtsPastDue = '';
        $nextPayTimestamp = '';
    } else {
        $numPmtsPastDue = 0;
        $nextPayTimestamp = $nextPayDueDate->getTimestamp();
        if ($nextPayTimestamp < $now) { // past due
            $numPmtsPastDue = 1 + ceil(($now - $nextPayTimestamp) / (24 * 3600 * $payorPlan['daysBetween']));
            $minAmtNum = $numPmtsPastDue * $payorPlan['minPayment'];
            if ($minAmtNum > $payorPlan['balanceDue'])
                $minAmtNum = $payorPlan['balanceDue'];
            $minAmt = $dolfmt->formatCurrency((float)$minAmtNum, $currency);
        }
        $dayPastDue =  ($now - $nextPayTimestamp) / (24 * 60 * 60);
    }
    $dateCreated = date_format(date_create($payorPlan['createDate']), 'Y-m-d');
    $payByDate = date_format(date_create($plan['payByDate']), 'Y-m-d');
    $balanceDue = $dolfmt->formatCurrency((float) $payorPlan['balanceDue'], $currency);
    $initialAmt = $dolfmt->formatCurrency((float) $payorPlan['initialAmt'], $currency);

    $data['numPmts'] = $numPmts;
    $data['lastPayment'] = $lastPayment;
    $data['lastPaidDate'] = $lastPaidDate;
    $data['nextPayDueDate'] = $nextPayDueDate;
    $data['nextPayDue'] = $nextPayDue;
    $data['minAmt'] = $minAmt;
    $data['minAmtNum'] = $minAmtNum;
    $data['nextPayTimestamp'] = $nextPayTimestamp;
    $data['daysPastDue'] = $dayPastDue;
    $data['numPmtsPastDue'] = $numPmtsPastDue;
    $data['dateCreated'] = $dateCreated;
    $data['payByDate'] = $payByDate;
    $data['balanceDue'] = $balanceDue;
    $data['initialAmt'] = $initialAmt;
    return $data;
}

// this looks at the Balance due, num payments remaining, min payment, and payByDate
// and re-computes the min and final payments, as well as days between to keep the payment plan in balance.
// Priorities for adjustment:
//      adjust the days between to keep the plan paid off in the remaining period, but keeping within the range of 7-30
//          adjust the number of payments left to reduce this if needed to keep the time between payments at a minimum of 7 days
//          Note: payments made by someone other than payor (transaction id perid != payor perid) don't count as a payment against the plan
//              for this number of payments left calculation
//      change min payment to not be less than plan min payment, but be able to pay off the plan in the remaining payments

function recomputePaymentPlan($payorPlanId) : array {
    $response['payorPlanId'] = $payorPlanId;
    // first validate the current balance due and get the number of payments already made, and get the relevant plan parameters
    // fields:
    //      initialAmt = total amount of the purchase transaction
    //      nonPlanAmt = amount paid day 1 that is not elegible to be under the plan
    //      openingBalance = initialAmt - nonPlanAmt (the amount eligible for the plan)
    //      downPayment = amount paid day 1 that is from the elegible amount
    //      initial balanceDue = openingBalance - downPayment
    //      balanceDue us decreased due to each payment made, until paid off when it's 0
    $planQ = <<<EOS
SELECT planId, pp.perid, pp.newperid, initialAmt, nonPlanAmt, downPayment, minPayment, finalPayment, openingBalance, numPayments, daysBetween,
       balanceDue, payByDate, createTransaction, createDate, status, t.paid
FROM payorPlans pp
JOIN transaction t ON pp.createTransaction = t.id
WHERE pp.id = ?;
EOS;
    $payQ = <<<EOS
SELECT payorPlanId, paymentNbr, dueDate, payDate, planPaymentAmount, pp.amount, p.amount AS paymentAmt,
       t.paid AS transactionAmt, t.perid AS transactionPerid
FROM payorPlanPayments pp
JOIN payments p ON p.id = pp.paymentId
JOIN transaction t ON t.id = pp.transactionId
WHERE pp.payorPlanId = ?
ORDER BY payDate;
EOS;
    $regQ = <<<EOS
SELECT IFNULL(SUM(price - (paid +couponDiscount)), 0)
FROM reg
WHERE status = 'plan' AND planId = ?;
EOS;

    $payorR = dbSafeQuery($planQ, 'i', array ($payorPlanId));
    if ($payorR === false || $payorR->num_rows != 1) {
        return ['error' => 'No payor plan found.'];
    }
    $payorPlan = $payorR->fetch_assoc();
    $payorR->free();

    if ($payorPlan['status'] == 'paid') {
        return ['error' => 'Payor plan already paid.'];
    }

    $daysBetween = $payorPlan['daysBetween'];
    $minPayment = $payorPlan['minPayment'];
    $finalPayment = $payorPlan['finalPayment'];

    // get the outstanding unpaid
    $regR = dbSafeQuery($regQ, 'i', array($payorPlanId));
    if ($regR === false) {
        return ['error' => 'Error running reg query for outstanding regs still under plan'];
    }
    $outstandingBalance = $regR->fetch_row()[0];
    $regR->free();

    $payR = dbSafeQuery($payQ, 'i', array ($payorPlanId));
    if ($payR === false) {
        return ['error' => 'Error running payment query.'];
    }

    $totalPayments = 0;
    $totalPlanPayments = 0;
    $payments = [];
    $numPaymentsMade = $payR->num_rows;
    $numPayorPaymentsMade = 0;
    $payorPerid = $payorPlan['perid'];

    while ($row = $payR->fetch_assoc()) {
        $payments[] = $row;
        $totalPayments += $row['paymentAmt'];
        $totalPlanPayments += $row['amount'];
        if ($row['transactionPerid'] == $payorPerid)
            $numPayorPaymentsMade++;
    }
    $payR->free();

// now the data is loaded, lets check to see if there are any inconsistencies
    $warning = '';
    if ($totalPayments != $totalPlanPayments) {
        $warning .= "Warning: Total payments on plan of $totalPayments does not match sum of payment table of $totalPlanPayments.<br/>";
    }

    $initialAmt = $payorPlan['initialAmt'];
    $nonPlanAmt = $payorPlan['nonPlanAmt'];
    $openingBalance = $payorPlan['openingBalance'];
    if ($initialAmt - $nonPlanAmt != $openingBalance) {
        $warning .= "Warning: Initial Amount ($initialAmt) less Non Plan Amount ($nonPlanAmt) does not match opening balance ($openingBalance).<br/>";
    }
    $openingBalance = $initialAmt - $nonPlanAmt;
    $balanceDue = $payorPlan['balanceDue'];
    $financedAmount = $openingBalance - $payorPlan['downPayment'];
    $calcBalanceDue = $financedAmount - $totalPlanPayments;
    if ($calcBalanceDue != $balanceDue) {
        $warning .= "Warning: Calculated balance due ($calcBalanceDue) does not match balance due ($balanceDue), recasting plan.<br/>";
    }

    if ($calcBalanceDue != $outstandingBalance) {
        // need to re-do it as the current outstanding balance doesn't match what is really out there
        //TODO: this only counts regs right now, and not spaces
        $calcBalanceDue = $outstandingBalance;
    }

    if ($calcBalanceDue <= 0) {
        $warning .= "Warning: Plan is paid or over paid and not marked paid, seek assistance<br/>";
        return ['warn' => $warning];
    }
    $origNumPayments = $payorPlan['numPayments'];
    $remainingPayments = $origNumPayments - $numPayorPaymentsMade;

    if ($remainingPayments <= 1) {
        if ($calcBalanceDue == $payorPlan['finalPayment']) {
            $warning .= "Warning: no need to reprice plan, it is currently set to pay off with the next payment.";
            return ['warn' => $warning];
        }
        // force it to need payment in one more payment
        $upQ = <<<EOS
UPDATE payorPlans
SET finalPayment = ?, balanceDue = ?, minPayment = ?
WHERE id = ?;
EOS;
        $numUpd = dbSafeCmd($upQ, 'dddi', array ($calcBalanceDue, $calcBalanceDue, $calcBalanceDue, $payorPlanId));
        $response['success'] = "Plan recast to pay off on next payment with a payment of $calcBalanceDue.  $numUpd rows updated";
    } else if ($numPaymentsMade != $numPayorPaymentsMade || $calcBalanceDue != $balanceDue || $remainingPayments * $minPayment < $balanceDue) {
        // recast of plan needed, as someone else made a payment, or the balance due is incorrect

        // start with is there enough time to left to handle the number of remaining payments before payoff date
        $daysneeded = $remainingPayments * $daysBetween;
        $payoffDate = strtotime($payorPlan['payByDate']);
        $curDate = strtotime("now");
        $daysLeft = round(($payoffDate - $curDate) / 86400);
        $maxPaymentsLeft = $daysLeft / 7;
        if ($maxPaymentsLeft < 2) {
            $remainingPayments = 1;
            $finalPayment = $calcBalanceDue;
            $minPayment = $calcBalanceDue;
        } else if ($daysneeded > $daysLeft) {
            $minDays = 7 * $remainingPayments;
            if ($minDays > $daysLeft) {
                $remainingPayments = intval($maxPaymentsLeft);
            } else {
                $daysBetween = intval($daysLeft / $remainingPayments);
            }
            $minPayment = $calcBalanceDue / $remainingPayments;
            $finalPayment = $calcBalanceDue - (($remainingPayments -1) * $minPayment);
        } else if ($remainingPayments * $minPayment < $balanceDue) {
            if ($remainingPayments < 1) {
                $remainingPayments = 1;
                $finalPayment = $calcBalanceDue;
            }
            $minPayment = round($balanceDue / $remainingPayments, 2);
        }
        // now update the payment plan with the new values
        $numPayments = $numPayorPaymentsMade + $remainingPayments;
        if ($numPayments * $minPayment != $calcBalanceDue) {
            $finalPayment = $calcBalanceDue - (($numPayments - 1) * $minPayment);
        } else {
            $finalPayment = $minPayment;
        }
        $updQ = <<<EOS
UPDATE payorPlans
SET numPayments = ?, balanceDue = ?, finalPayment = ?, minPayment = ?, daysBetween = ?
WHERE id = ?;
EOS;
        $numUpd = dbSafeCmd($updQ, 'idddii', array($numPayments, $calcBalanceDue, $finalPayment, $minPayment, $daysBetween, $payorPlanId));
        $response['success'] = "Plan recast to $numPayments with a minimum payment of $minPayment,  $numUpd rows updated.";
    }
    if ($warning != '')
        $response['warning'] = $warning;

    return $response;
}
