<?php
require_once('../lib/base.php');
require_once('../lib/getAccountData.php');
require_once('../lib/portalEmails.php');
require_once('../../lib/paymentPlans.php');
require_once('../../lib/purchase.php');
require_once('../../lib/coupon.php');
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

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');

$plan = $_POST['plan'];
$newplan = $_POST['newplan'];
$nonce = $_POST['nonce'];
$amount = $_POST['amount'];
$planRec = $_POST['planRec'];
$existingPlan = $_POST['existingPlan'];
$planPayment = $_POST['planPayment'];

// load the amount valies
if (array_key_exists('totalAmountDue', $_POST)) {
    $totalAmountDue = $_POST['totalAmountDue'];
} else {
    $totalAmountDue = $amount;
}
if (array_key_exists('couponDiscount', $_POST)) {
    $webCouponDiscount = $_POST['couponDiscount'];
} else {
    $webCouponDiscount = 0;
}
if (array_key_exists('preCoupomAmountDue', $_POST)) {
    $preCoupomAmountDue = $_POST['preCoupomAmountDue'];
} else {
    $preCoupomAmountDue = $amount;
}

// and the coupon values
if (array_key_exists('couponCode', $_POST)) {
    $couponCode = $_POST['couponCode'];
} else {
    $couponCode = null;
}

if (array_key_exists('couponSerial', $_POST)) {
    $couponSerial = $_POST['couponSerial'];
} else {
    $couponSerial = null;
}

// all the records are in the database, so lets charge the credit card...

$transId = getSessionVar('transId');
if ($transId == null) {
    $transId = getNewTransaction($conid, $loginType == 'p' ? $loginId : null, $loginType == 'n' ? $loginId : null);
}

// get this person
$info = getPersonInfo($conid);

// compute the results array here
$coupon = null;
$counts = null;

$badges = getAccountRegistrations($loginId, $loginType, $conid, ($newplan || $planPayment == 0) ? 'unpaid' : 'plan');

if ($planPayment == 1 || $newplan == 1) {
    $inPlan = whatMembershipsInPlan($badges, $planRec);
} else {
    $inPlan = [];
}

// ok, the Portal data is now loaded, now deal with re-pricing things, based on the real tables
$data = loadPurchaseData($conid, $couponCode, $couponSerial);
$prices = $data['prices'];
$memId = $data['memId'];
$counts = $data['counts'];
$discounts = $data['discounts'];
$primary = $data['primary'];
$map = $data['map'];
$coupon = $data['coupon'];
$memCategories = $data['memCategories'];
$mtypes = $data['mtypes'];

//// $rules = $data['rules'];
//// TODO: load and apply rules checks here to $badges
$data = computePurchaseTotals($coupon, $badges, $primary, $counts, $prices, $map, $discounts, $mtypes, $memCategories);

$maxMbrDiscounts = $data['origMaxMbrDiscounts'];
$apply_discount = $data['applyDiscount'];
$preDiscount = $data['preDiscount'];
$total = $data['total'];
$totalDiscount = $data['totalDiscount'];

if ($totalAmountDue != $total) {
    error_log('bad total: post=' . $totalAmountDue . ', calc=' . $total);
    ajaxSuccess(array ('status' => 'error', 'error' => 'Unable to process, bad total sent to Server'));
    exit();
}

if ($coupon != null) {
    if ($webCouponDiscount != $totalDiscount) {
        error_log('bad coupon discount: post=' . $webCouponDiscount . ', calc=' . $totalDiscount);
        ajaxSuccess(array ('status' => 'error', 'error' => 'Unable to process, bad coupon data sent to Server'));
        exit();
    }
}

// now recompute the records in the badgeResults array

$results = array(
    'custid' => "$loginType-$loginId",
    'transid' => $transId,
    'counts' => $counts,
    'price' => $totalAmountDue,
    'tax' => 0,
    'pretax' => $totalAmountDue,
    'badges' => $badges,
    'total' => $amount,
    'nonce' => $nonce,
    'coupon' => $coupon,
    'discount' => $totalDiscount,
);

//log requested badges
logWrite(array('con'=>$condata['name'], 'trans'=>$transId, 'results'=>$results, 'request'=>$badges, 'inPlan' => $inPlan));
// end compute
if ($amount > 0) {
    $rtn = cc_charge_purchase($results, $ccauth, true);
    if ($rtn == null) {
        // note there is no reason cc_charge_purchase will return null, it calls ajax returns directly and doesn't come back here on issues, but this is just in case
        logWrite(array('con' => $condata['name'], 'trans' => $transId, 'error' => 'Credit card transaction not approved'));
        ajaxSuccess(array('status' => 'error', 'error' => 'Credit card not approved'));
        exit();
    }

//$tnx_record = $rtn['tnx'];
    logWrite(array('con' => $condata['name'], 'trans' => $transId, 'ccrtn' => $rtn));
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
    INSERT INTO payorPlans (planId, $pfield, initialAmt, nonPlanAmt, downPayment, minPayment, finalPayment,
                            openingBalance, numPayments, daysBetween, payByDate, payType, reminders, 
                            createTransaction, balanceDue, updateBy)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
    
    EOS;
    $typestr = 'iiddddddiisssidi';
    $planData = $planRec['plan'];
    $valArray = array($planData['id'], $loginId, $planRec['totalAmountDue'], $planRec['nonPlanAmt'], $planRec['downPayment'], $planRec['paymentAmt'],
        $planRec['finalPaymentAmt'],
        $planRec['planAmt'], $planRec['numPayments'], $planRec['daysBetween'], $planData['payByDate'], $planData['payType'], $planData['reminders'],
        $transId, $planRec['balanceDue'], $loginId);
    $newPlanId = dbSafeInsert($iQ, $typestr, $valArray);
    if ($newPlanId == false || $newPlanId < 0) {
        logWrite(array("plan msg"=>"create of plan failed", "plan data" => $valArray ));
    } else {
        logWrite(array("plan id" => $newPlanId, 'plan data' => $valArray));
    }
    $planRec['payorPlanId'] = $newPlanId;
} else if ($planPayment == 1) {
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
    $valArray = array($existingPlan['id'], $paymentNbr, $dueDate, $existingPlan['minPayment'], $amount, $txnid, $transId);
    $paymntkey = dbSafeInsert($iQ, $typestr, $valArray);
}

$txnUpdate = 'UPDATE transaction SET ';
if ($approved_amt == $amount) {
    $txnUpdate .= 'complete_date=current_timestamp(), ';
}

$txnUpdate .= 'paid=?, couponDiscount = ? WHERE id=?;';
$txnU = dbSafeCmd($txnUpdate, 'ddi', array($approved_amt, $totalDiscount, $transId));
$rows_upd = 0;

$mrQ = <<<EOS
SELECT mRI.*
FROM memRules mR
JOIN memRuleItems mRI ON mR.name = mRI.name
WHERE CONCAT(',', mR.memList, ',') like ?;
EOS;

$upgradedUP = <<<EOS
UPDATE reg
SET status = 'upgraded'
WHERE conid = ? AND perid = ? AND memId = ? AND status = 'paid';
EOS;

    $upgradedUN = <<<EOS
UPDATE reg
SET status = 'upgraded'
WHERE conid = ? AND newperid = ? AND memId = ? AND status = 'paid';
EOS;

$upgradedCnt = 0;
if ($amount > 0) {
    $regU = 'UPDATE reg SET paid=?, couponDiscount = ?, complete_trans = ?, status = ? WHERE id=?;';
    $balance = $approved_amt;
    // first all the out of plan ones
    for ($idx = 0; $idx < count($badges); $idx++) {
        $badge = $badges[$idx];
        if (!array_key_exists($badge['regId'], $inPlan) || !$inPlan[$badge['regId']]) {
            $paid_amt = min($balance, $badge['price'] - ($badge['paid'] + $badge['couponDiscount']));
            // only update those that were actually modified
            if (($paid_amt > 0) || ($badge['price'] == 0  && $badge['complete_trans'] == null)) {
                if ($badge['memCategory'] == 'upgrade' && $paid_amt <= $balance) {
                    // ok this upgrade is now paid for, mark the old one upgraded
                    // upgrades require a role to allow them to be bought based on the prior membership being in the cart, get the rule for this membership

                    $mrR = dbSafeQuery($mrQ, 's', array('%,' . $badge['memId'] . ',%'));
                    if ($mrR != false) {
                        if ($mrR->num_rows > 0) {
                            while ($rule = $mrR->fetch_assoc()) {
                                if ($rule['memList'] != null && $rule['memList'] != '') {
                                    $memIds = explode(',', $rule['memList']);
                                    foreach ($memIds as $memId) {
                                        $argPerid = null;
                                        $argNewPerid = null;
                                        if (array_key_exists('perid', $badge)) {
                                            $argPerid = $badge['perid'];
                                        }
                                        if (array_key_exists('newperid', $badge)) {
                                            $argNewPerid = $badge['newperid'];
                                        }
                                        if ($argPerid != null) {
                                            $upgradedCnt += dbSafeCmd($upgradedUP, 'iii', array ($conid, $argPerid, $memId));
                                        } else if ($argNewPerid != null) {
                                            $upgradedCnt += dbSafeCmd($upgradedUN, 'iii', array ($conid, $argNewPerid, $memId));
                                        }
                                    }
                                }
                            }
                        }
                        $mrR->free();
                    }
                }
                $rows_upd += dbSafeCmd($regU, 'ddisi', array (
                    $badge['price'] - $badge['couponDiscount'],
                    $badge['couponDiscount'],
                    $paid_amt <= $balance ? $transId : null,
                    $paid_amt <= $balance ? 'paid' : $badge['status'],
                    $badge['regId']
                ));
                $balance -= $paid_amt;
                $badges[$idx]['modified'] = true;
            }
        } else {
            $badges[$idx]['modified'] = false;
        }
    }
    if ($balance > 0) {
        // now all the in plan ones
        // figure out the percentage to apply to each
        $totalOwed = 0;
        $count = 0;
        foreach ($badges as $badge) {
            $count++;
            if (array_key_exists($badge['regId'], $inPlan) && $inPlan[$badge['regId']]) {
                $totalOwed += $badge['price'] - ($badge['paid'] + $badge['couponDiscount']);
            }
        }
        if ($totalOwed > 0) {
            $ratio = $balance / $totalOwed;
        } else {
            $ratio = 1;
        }
        if ($ratio > 0.990)
            $ratio = 1; // deal with rounding errors
        $applied = 0;
        for ($idx = 0; $idx < count($badges); $idx++) {
            $badge = $badges[$idx];
            $applied++;
            if (array_key_exists($badge['regId'], $inPlan) && $inPlan[$badge['regId']]) {
                $due = $badge['price'] - ($badge['paid'] + $badge['couponDiscount']);
                if ($applied == $count) // last row, give it all of the balance
                    $paid_amt = $balance;
                else
                    $paid_amt = $ratio == 1 ? $due : round($ratio * $due, 2);
                if ($paid_amt > $due)
                    $paid_amt = $due; // just in case

                // only update those that were actually modified
                if ($badge['memCategory'] == 'upgrade' && $paid_amt <= $balance) {
                    // ok this upgrade is now paid for, mark the old one upgraded
                    // upgrades require a role to allow them to be bought based on the prior membership being in the cart, get the rule for this membership

                    $mrR = dbSafeQuery($mrQ, 's', array ('%,' . $badge['memId'] . ',%'));
                    if ($mrR != false) {
                        if ($mrR->num_rows > 0) {
                            while ($rule = $mrR->fetch_assoc()) {
                                if ($rule['memList'] != null && $rule['memList'] != '') {
                                    $memIds = explode(',', $rule['memList']);
                                    foreach ($memIds as $memId) {
                                        $argPerid = null;
                                        $argNewPerid = null;
                                        if (array_key_exists('perid', $badge)) {
                                            $argPerid = $badge['perid'];
                                        }
                                        if (array_key_exists('newperid', $badge)) {
                                            $argNewPerid = $badge['newperid'];
                                        }
                                        if ($argPerid != null) {
                                            $upgradedCnt += dbSafeCmd($upgradedUP, 'iii', array ($conid, $argPerid, $memId));
                                        } else if ($argNewPerid != null) {
                                            $upgradedCnt += dbSafeCmd($upgradedUN, 'iii', array ($conid, $argNewPerid, $memId));
                                        }

                                    }
                                }
                            }
                        }
                        $mrR->free();
                    }
                }
                $balance -= $paid_amt;
                $left = $due - $paid_amt;
                $rows_upd += dbSafeCmd($regU, 'ddisi', array(
                    $paid_amt,
                    $badge['couponDiscount'],
                    $left < 0.01 ? $transId : null,
                    $left < 0.01 ? 'paid' : 'plan',
                    $badge['regId']
                ));
                $badges[$idx]['modified'] = true;
            }
        }
    }
}
if ($amount > 0) {
    $body = getEmailBody($transId, $info, $badges, $planRec, $rtn['rid'], $rtn['url'], $amount);
} else {
    $body = getNoChargeEmailBody($results, $info, $badges);
}

$regconfirmcc = null;
if (array_key_exists('regconfirmcc', $conf)) {
    $regconfirmcc = trim($conf['regconfirmcc']);
    if ($regconfirmcc == '')
        $regconfirmcc = null;
}
$return_arr = send_email($conf['regadminemail'], trim($info['email_addr']), /* cc */ $regconfirmcc, $condata['label'] . ' Registration Portal Payment Receipt', $body, /* htmlbody */ null);

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

if (is_array($planRec) && array_key_exists('payorPlanId', $planRec))
    $payorPlan = $planRec['payorPlanId'];
else
    $payorPlan = null;

$response = array(
    'status' => $return_arr['status'],
    'url' => $rtn['url'],
    'data' => $error_msg,
    'email' => $return_arr,
    'trans' => $transId,
    'payorPlanId' => $payorPlan,
    'email_error' => $error_code,
    'rows_upd' => $rows_upd,
);

unsetSessionVar('transId');
unsetSessionVar('totalDue');
//var_error_log($response);
ajaxSuccess($response);
return;

function getNewTransaction($conid, $perid, $newperid) {
    $iQ = <<<EOS
INSERT INTO transaction (conid, perid, newperid, userid, price, couponDiscount, paid, type)
VALUES (?, ?, ?, ?, 0, 0, 0, 'regportal');
EOS;
    $transId = dbSafeInsert($iQ, 'iiii', array($conid, $perid, $newperid, $perid));
    setSessionVar('transId', $transId);
    return $transId;
}
