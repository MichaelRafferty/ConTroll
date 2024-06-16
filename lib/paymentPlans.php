<?php
// items related to using payment Plans

function getPaymentPlans($includeAccount = false) {
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
        $accountId = $_SESSION['id'];
        $accountType = $_SESSION['idType'];
        if ($accountType == 'p') {
            $pfield = 'perid';
        } else {
            $pfield = 'newperid';
        }

        // the plans for this payor
        $QQ = <<<EOS
SELECT * FROM payorPlans
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
SELECT pp.*
FROM payorPlanPayments pp
JOIN payorPlans p ON p.id = pp.payorPlanId
WHERE p.$pfield = ?
ORDER BY payorPlanId, PaymentNbr;
EOS;

        $currentPlan = null;
        $currentPayments = array();
        $QR = dbSafeQuery($QQ, 'i', array($accountId));
        while ($row = $QR->fetch_assoc()) {
            if ($currentPlan != $row['payorPlanId']) {
                if ($currentPlan != null) {
                    $payorPlans[$currentPlan]['payments'] = $currentPayments;
                    $currentPayments = array();
                }
                $currentPlan = $row['payorPlanId'];
            }

            $currentPayments[$row['paymentNbr']] = $row;
        }
        if ($currentPlan != null)
            $payorPlans[$currentPlan]['payments'] = $currentPayments;

        $data['payorPlans'] = $payorPlans;
    }

    return $data;
}

function whatMembershipsInPlan($memberships, $plan) {
    $inPlan = [];
    $inplan[$plan == null ? '' : $plan] = true;
    if ($plan == null || $plan =='') {
        foreach ($memberships as $membership) {
            $inPlan[$membership['regId']] = false;
        }
        return $inPlan;
    }

    // get the plan info from the database
    $QQ = <<<EOS
SELECT *
FROM paymentPlans
WHERE active = 'Y' AND name = ?;
EOS;
    $QR = dbSafeQuery($QQ, 's', array($plan));
    if ($QR == false || $QR->num_rows != 1)
        return null;
    $planData = $QR->fetch_assoc();
    $QR->free();
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

    foreach ($memberships as $membership) {
        if ($membership['status'] != 'unpaid') {
            $inPlan[$membership['regId']] = false;
            continue;
        }

        if ($excludeList != null && in_array($membership['memId'], $excludeList)) {
            $inPlan[$membership['regId']] = false;
            continue;
        }

        if ($catList != null && in_array($membership['memCategory'], $catList)) {
            $inPlan[$membership['regId']] = true;
            continue;
        }

        if ($memList != null && !in_array($membership['memId'], $memList)) {
            $inPlan[$membership['regId']] = true;
            continue;
        }

        $inPlan[$membership['regId']] = false;
    }

    return $inPlan;
}
