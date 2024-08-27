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
        $accountId = getSessionVar('id');
        $accountType = getSessionVar('idType');
        if ($accountType == 'p') {
            $pfield = 'perid';
        } else {
            $pfield = 'newperid';
        }

        // the plans for this payor
        $QQ = <<<EOS
SELECT pp.*, p.name FROM payorPlans pp
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

function whatMembershipsInPlan($memberships, $computedPlan) {

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
function draw_customizePlanModal($from) {
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
function draw_payPlanModal($from) {
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
