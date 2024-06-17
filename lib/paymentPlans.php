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

function whatMembershipsInPlan($memberships, $computedPlan) {
    $inPlan = [];

    if ($computedPlan == null) {
        $inPlan[''] = true;
        $planData = null;
    } else {
        $planData = $computedPlan['plan'];
        $inPlan[$planData['name']] = true;
    }

    if ($planData == null) {
        foreach ($memberships as $membership) {
            $inPlan[$membership['regId']] = false;
        }
        return $inPlan;
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

//// payment plan modals
// drawCustomizePlanModal- main payment modal popup
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
                    <div class='row'>
                        <div class='col-sm-12' id='customizePlanMessageDiv'></div>
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
