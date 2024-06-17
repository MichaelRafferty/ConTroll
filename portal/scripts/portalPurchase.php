<?php
require_once('../lib/base.php');
require_once('../lib/getAccountData.php');
require_once('../lib/portalEmails.php');
require_once('../../lib/paymentPlans.php');
require_once('../../lib/log.php');
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/email__load_methods.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$condata = get_con();
$conid=$condata['id'];
$conf = get_conf('con');
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$log = get_conf('log');
$ccauth = get_conf('cc');
load_cc_procs();
load_email_procs();
logInit($log['reg']);

$response['conid'] = $conid;

if (!(array_key_exists('action', $_POST) && array_key_exists('plan', $_POST) && array_key_exists('nonce', $_POST) && array_key_exists('amount', $_POST)  )) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(array_key_exists('id', $_SESSION) && array_key_exists('idType', $_SESSION))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$loginId = $_SESSION['id'];
$loginType = $_SESSION['idType'];

$plan = $_POST['plan'];
$newplan = $_POST['newplan'];
$nonce = $_POST['nonce'];
$amount = $_POST['amount'];
$planRec = $_POST['planRec'];
$existingPlan = $_POST['existingPlan'];

if ($planRec) {
    $totalAmtDue = $planRec['totalAmountDue'];
} else {
    $totalAmtDue = $amount;
}

if ($plan == 0 || $newplan == 1) {
    if (!array_key_exists('totalDue', $_SESSION)) {
        ajaxSuccess(array('status' => 'error', 'message' => 'No confirm payment amount.'));
        exit();
    }
    $totalDue = $_SESSION['totalDue'];
    if ($totalAmtDue != $totalDue) {
        ajaxSuccess(array('status' => 'error', 'message' => 'Improper payment amount.'));
        exit();
    }
}


// all the records are in the database, so lets charge the credit card...

if (array_key_exists('transId', $_SESSION)) {
    $transid = $_SESSION['transId'];
} else {
    $transid = getNewTransaction($conid, $loginType == 'p' ? $loginId : null, $loginType == 'n' ? $loginId : null);
}

// get this person
$info = getPersonInfo();

// compute the results array here
$totalDiscount = 0;
$coupon = null;
$counts=null;

$memberships = getAccountRegistrations($loginId, $loginType, $conid, $newplan ? 'unpaid' : 'plan');
$badgeResults = [];
foreach ($memberships as $membership) {
    $badgeResults[] = ['age' => $membership['memAge'], 'memId' => $membership['memId'], 'price' => $membership['price']];
}

$inPlan = whatMembershipsInPlan($memberships, $planRec);

$results = array(
    'custid' => "$loginType-$loginId",
    'transid' => $transid,
    'counts' => $counts,
    'price' => $totalAmtDue,
    'badges' => $badgeResults,
    'total' => $amount,
    'nonce' => $nonce,
    'coupon' => $coupon,
    'discount' => $totalDiscount,
);

//log requested badges
logWrite(array('con'=>$condata['name'], 'trans'=>$transid, 'results'=>$results, 'request'=>$memberships, 'inPlan' => $inPlan));
// end compute
if ($amount > 0) {
    $rtn = cc_charge_purchase($results, $ccauth, true);
    if ($rtn == null) {
        // note there is no reason cc_charge_purchase will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
        logWrite(array('con' => $condata['name'], 'trans' => $transid, 'error' => 'Credit card transaction not approved'));
        ajaxSuccess(array('status' => 'error', 'error' => 'Credit card not approved'));
        exit();
    }

//$tnx_record = $rtn['tnx'];
    logWrite(array('con' => $condata['name'], 'trans' => $transid, 'ccrtn' => $rtn));
    $num_fields = sizeof($rtn['txnfields']);
    $val = array();
    for ($i = 0; $i < $num_fields; $i++) {
        $val[$i] = '?';
    }
    $txnQ = 'INSERT INTO payments(time,' . implode(',', $rtn['txnfields']) . ') VALUES(current_time(),' . implode(',', $val) . ');';
    $txnT = implode('', $rtn['tnxtypes']);
    $txnid = dbSafeInsert($txnQ, $txnT, $rtn['tnxdata']);
    $approved_amt = $rtn['amount'];
} else {
    $approved_amt = 0;
    $rtn = array('url' => '', 'rid' => '');
}

if ($loginType == 'p') {
    $pfield = 'perid';
} else {
    $pfield = 'newperid';
}

if ($newplan == 1) {
    // record the new plan
    $iQ = <<<EOS
    INSERT INTO payorPlans (planId, $pfield, initialAmt, nonPlanAmt, downPayment, minPayment, 
                            openingBalance, numPayments, daysBetween, payByDate, payType, reminders, 
                            createTransaction, balanceDue, updateBy)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
    
    EOS;
    $typestr = 'iidddddisssidi';
    $planData = $planRec['plan'];
    $valArray = array($planData['id'], $loginId, $planRec['totalAmountDue'], $planRec['nonPlanAmt'], $planRec['downPayment'], $planRec['paymentAmt'],
        $planRec['planAmt'], $planRec['numPayments'], $planRec['daysBetween'], $planData['payByDate'], $planData['payType'], $planData['reminders'],
        $transid, $planRec['balanceDue'], $loginId

    );
    $newPlanId = dbSafeInsert($iQ, $typestr, $valArray);
    if ($newPlanId == false || $newPlanId < 0) {
        logWrite(array("plan msg"=>"create of plan failed", "plan data" => $valArray ));
    } else {
        logWrite(array("plan id" => $newPlanId, 'plan data' => $valArray));
    }
    $planRec['payorPlanId'] = $newPlanId;
} else if ($plan == 1) {
    // update the plan for the payment
    $uQ = <<<EOS
UPDATE payorPlans SET balanceDue = balanceDue - ?, updateBy = ?
WHERE id = ? AND $pfield = ?;
EOS;
    $typestr = 'diii';
    $valArray = array($amount, $loginId, $existingPlan['id'], $loginId);
    $planUpd = dbSafeCmd($uQ, $typestr, $valArray);
    dbSafeCmd("UPDATE payorPlans SET status = 'paid' WHERE $pfield = ? AND balanceDue = 0 AND status = 'active';", 'i', array($loginId));

    // insert payment record
    $nR = dbSafeQuery("SELECT MAX(paymentNbr) FROM payorPlanPayments WHERE payorPlanId = ?;", 'i', array($existingPlan['id']));
    if ($nR == false || $nR->num_rows != 1) {
        $paymentNbr = 1;
    } else {
        $paymentNbr = $nR->fetch_row()[0] + 1;
    }
    $dueDate =  date_format(date_add(date_create($existingPlan['createDate']), date_interval_create_from_date_string(($paymentNbr * $existingPlan['daysBetween']) - 1 . ' days')),
        'Y-m-d');

    $iQ = <<<EOS
INSERT INTO payorPlanPayments(payorPlanId, paymentNbr, dueDate, payDate, planPaymentAmount, amount, paymentId, transactionId)
VALUES (?, ?, ?, NOW(), ?, ?, ?, ?);
EOS;
    $typestr = 'iisddii';
    $valArray = array($existingPlan['id'], $paymentNbr, $dueDate, $existingPlan['minPayment'], $amount, $txnid, $transid);
    $paymntkey = dbSafeInsert($iQ, $typestr, $valArray);
}

$txnUpdate = 'UPDATE transaction SET ';
$txnUpdate = 'UPDATE transaction SET ';
if ($approved_amt == $amount) {
    $txnUpdate .= 'complete_date=current_timestamp(), ';
}

$txnUpdate .= 'paid=?, couponDiscount = ? WHERE id=?;';
$txnU = dbSafeCmd($txnUpdate, 'ddi', array($approved_amt, $totalDiscount, $transid));
$rows_upd = 0;

if ($amount > 0) {
    $regU = 'UPDATE reg SET paid=?, couponDiscount = ?, complete_trans = ?, status = ? WHERE id=?;';
    $balance = $approved_amt;
    // first all the out of plan ones
    foreach ($memberships as $membership) {
        if (!$inPlan[$membership['regId']]) {
            $paid_amt = $membership['price'] - ($membership['paid'] + $membership['couponDiscount']);
            $balance -= $paid_amt;
            $rows_upd += dbSafeCMD($regU, 'ddisi', array(
                $membership['price'] - $membership['couponDiscount'],
                $membership['couponDiscount'],
                $transid,
                'paid',
                $membership['regId']
            ));
        }
    }
    if ($balance > 0) {
        // now all the in plan ones
        // figure out the percentage to apply to each
        $totalOwed = 0;
        $count = 0;
        foreach ($memberships as $membership) {
            $count++;
            if ($inPlan[$membership['regId']]) {
                $totalOwed += $membership['price'] - ($membership['paid'] + $membership['couponDiscount']);
            }
        }
        $ratio = $balance / $totalOwed;
        if ($ratio > 0.990)
            $ratio = 1; // deal with rounding errors
        $applied = 0;
        foreach ($memberships as $membership) {
            $applied++;
            if ($inPlan[$membership['regId']]) {
                $due = $membership['price'] - ($membership['paid'] + $membership['couponDiscount']);
                if ($applied == $count) // last row, give it all of the balance
                    $paid_amt = $balance;
                else
                    $paid_amt = $ratio == 1 ? $due : round($ratio * $due, 2);
                if ($paid_amt > $due)
                    $paid_amt = $due; // just in case
                $balance -= $paid_amt;
                $left = $due - $paid_amt;
                $rows_upd += dbSafeCMD($regU, 'ddisi', array(
                    $paid_amt,
                    $membership['couponDiscount'],
                    $left < 0.01 ? $transid : null,
                    $left < 0.01 ? 'paid' : 'plan',
                    $membership['regId']
                ));
            }
        }
    }
}
if ($amount > 0) {
    $body = getEmailBody($transid, $info, $memberships, $plan, $newplan, $planRec, $rtn['rid'], $rtn['url'], $amount);
} else {
    $body = getNoChargeEmailBody($results, $info, $plan, $newplan, $planRec, $memberships);
}

$regconfirmcc = null;
if (array_key_exists('regconfirmcc', $conf)) {
    $regconfirmcc = trim($conf['regconfirmcc']);
    if ($regconfirmcc == '')
        $regconfirmcc = null;
}
$return_arr = send_email($conf['regadminemail'], trim($info['email_addr']), /* cc */ $regconfirmcc, $condata['label'] . ' Registration Portal Paymnet Receipt', $body, /* htmlbody */ null);

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}

if (array_key_exists('email_error', $return_arr)) {
    $error_msg = $return_arr['email_error'];
} else {
    $error_msg = null;
}

$response = array(
    'status' => $return_arr['status'],
    'url' => $rtn['url'],
    'data' => $error_msg,
    'email' => $return_arr,
    'trans' => $transid,
    'payorPlanId' => $planRec['payorPlanId'],
    'email_error' => $error_code,
    'rows_upd' => $rows_upd,
);

unset($_SESSION['transId']);
unset($_SESSION['totalDue']);
//var_error_log($response);
ajaxSuccess($response);
return;

function getNewTransaction($conid, $perid, $newperid) {
    $iQ = <<<EOS
INSERT INTO transaction (conid, perid, newperid, userid, price, couponDiscount, paid, type)
VALUES (?, ?, ?, ?, 0, 0, 0, 'regportal');
EOS;
    $transId = dbSafeInsert($iQ, 'iiii', array($conid, $perid, $newperid, $perid));
    $_SESSION['transId'] = $transId;
    return $transId;
}
