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

validateLoginId();

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
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
if (array_key_exists('preCouponAmountDue', $_POST)) {
    $preCouponAmountDue = $_POST['preCouponAmountDue'];
} else {
    $preCouponAmountDue = $amount;
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
    $badges = whatMembershipsInPlan($badges, $planRec);
} else {
    foreach ($badges as $key => $badge) {
        $badges[$key]['inPlan'] = false;
    }
}

// ok, the Portal data is now loaded, now deal with re-pricing things, based on the real tables
$data = loadPurchaseData($conid, $couponCode, $couponSerial, $planPayment);
$prices = $data['prices'];
$memId = $data['memId'];
$counts = $data['counts'];
$discounts = $data['discounts'];
$primary = $data['primary'];
$map = $data['map'];
$coupon = $data['coupon'];
$memCategories = $data['memCategories'];
$mtypes = $data['mtypes'];

$rows_upd = 0;
$newPlanId = null;

//// $rules = $data['rules'];
//// TODO: load and apply rules checks here to $badges
$data = computePurchaseTotals($coupon, $badges, $primary, $counts, $prices, $map, $discounts, $mtypes, $memCategories);

$maxMbrDiscounts = $data['origMaxMbrDiscounts'];
$apply_discount = $data['applyDiscount'];
$preDiscount = $data['preDiscount'];
$total = $data['total'];
$totalDiscount = $data['totalDiscount'];

if ($planPayment == 0) {
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
} else {
    $totalAmountDue = $total;
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
    'newplan' => $newplan,
    'planRec' => $planRec,
    'planPayment' => $planPayment,
    'existingPlan' => $existingPlan,
);

//log requested badges
logWrite(array('con'=>$condata['name'], 'trans'=>$transId, 'results'=>$results, 'request'=>$badges));
$upT = <<<EOS
UPDATE transaction
SET price = ?, withTax = ?, couponDiscountCart = ?, tax = ?
WHERE id = ?;
EOS;
$rows_upd += dbSafeCmd($upT, 'ddddi', array($totalAmountDue, $totalAmountDue, $totalDiscount, 0, $transId));

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
if ($totalDiscount > 0) {
    // Insert the payment record for the coupon
    $ipQ = <<<EOS
INSERT INTO payments(transid, type, category, description, source, pretax, tax, amount, time, status) 
VALUES (?, 'coupon', 'reg', ?, 'online', ?, 0, ?, now(), 'APPLIED');
EOS;
    $couponDesc = $coupon['id'] . ':' . $coupon['code'] . ' - ' . $coupon['name'];
        $cpmtID = dbSafeInsert($ipQ, 'isdd', array($transId, $couponDesc, $totalDiscount, $totalDiscount));
    $coupon['totalDiscount'] = $totalDiscount;
}

if ($loginType == 'p') {
    $pfield = 'perid';
} else {
    $pfield = 'newperid';
}

if ($newplan == 1) {
    // record the new plan
    $iQ = <<<EOS
    INSERT INTO payorPlans (planId, conid, $pfield, initialAmt, nonPlanAmt, downPayment, minPayment, finalPayment,
                            openingBalance, numPayments, daysBetween, payByDate, payType, reminders, 
                            createTransaction, balanceDue, updateBy)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
    
    EOS;
    $typestr = 'iiiddddddiisssidi';
    $planData = $planRec['plan'];
    $valArray = array($planData['id'], $conid, $loginId, $planRec['totalAmountDue'], $planRec['nonPlanAmt'], $planRec['downPayment'], $planRec['paymentAmt'],
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
    $allocateAmt = $amount;
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

    // now allocate the payment to the items in the plan
    // $amount needs to be allocated across each item based on it's unpaid balances
    $bQ = <<<EOS
SELECT r.*, r.id as regId, m.conid AS mConid, m.memCategory, m.memType, m.memAge, m.label, m.price AS mPrice, m.startdate, m.enddate
FROM reg r
JOIN memList m ON (m.id = r.memId)
WHERE planId = ? AND status = 'plan';
EOS;
    $bR = dbSafeQuery($bQ, 'i', array($existingPlan['id']));
    if ($bR === false || $bR->num_rows == 0) {
        error_log("Error: unable to find the plan reg records for existing plan " . $existingPlan['id']);
    } else {
        $regs = [];
        $balance = 0;
        while ($row = $bR->fetch_assoc()) {;
            $row['inPlan'] = true;
            $balance += $row['price'] - ($row['paid'] + $row['couponDiscount']);
            $regs[] = $row;
        }
        $bR->free();

        if ($balance > 0) {
            $rows_upd += allocateBalance($allocateAmt, $regs, $conid, $existingPlan['id'], $transId, true );
        }
    }
}

$txnUpdate = 'UPDATE transaction SET ';
if ($approved_amt == $totalAmountDue) {
    $txnUpdate .= 'complete_date=current_timestamp(), ';
}

$txnUpdate .= 'paid=?, couponDiscountCart = ?, coupon = ? WHERE id=?;';
if ($totalDiscount > 0)
    $couponId = $coupon['id'];
else
    $couponId = null;
$txnU = dbSafeCmd($txnUpdate, 'ddii', array($approved_amt, $totalDiscount, $couponId, $transId));

$upgradedCnt = 0;
if ($amount > 0 && $planPayment != 1) {
    $balance = $approved_amt + $totalDiscount;
    // first all the out of plan ones
    $rows_upd += allocateBalance($balance, $badges, $conid, $newPlanId, $transId, false );

    // now all the in plan ones
    // figure out the percentage to apply to each
    $rows_upd += allocateBalance($balance, $badges, $conid, $newPlanId, $transId, true);
}
if ($totalAmountDue > 0) {
    $body = getEmailBody($transId, $info, $badges, $coupon, $planPayment == 1 ? $existingPlan : $planRec, $rtn['rid'], $rtn['url'], $amount, $planPayment);
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
INSERT INTO transaction (conid, perid, newperid, userid, price, couponDiscountCart, couponDiscountReg, paid, type)
VALUES (?, ?, ?, ?, 0, 0, 0, 0, 'regportal');
EOS;
    $transId = dbSafeInsert($iQ, 'iiii', array($conid, $perid, $newperid, $perid));
    setSessionVar('transId', $transId);
    return $transId;
}

// now all the in plan ones
// figure out the percentage to apply to each
function allocateBalance(&$balance, &$badges, $conid, $newPlanId, $transId, $planOnly) {
    // now all the in plan ones
    // figure out the percentage to apply to each
    $totalOwed = 0;
    $count = 0;
    $rows_upd = 0;
    $upgradedCnt = 0;

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

    $regU = 'UPDATE reg SET paid=paid + ?, couponDiscount = ?, complete_trans = ?, status = ?, planId = ? WHERE id=?;';
    foreach ($badges as $badge) {
        if (array_key_exists('inPlan', $badge) && $badge['inPlan'] == ($planOnly ? true : false)) {
            $count++;
            $totalOwed += $badge['price'] - ($badge['paid'] + $badge['couponDiscount']);
        }
    }
    if ($totalOwed > 0) {
        $ratio = $balance / $totalOwed;
    }
    else {
        $ratio = 1;
    }
    if ($ratio > 0.990)
        $ratio = 1; // deal with rounding errors
    $applied = 0;
    $planId = null;
    if ($planOnly == true) {
        if ($newPlanId != null) {
            $planId = $newPlanId;
        } else if (array_key_exists('planId', $badge)) {
            $planId = $badge['planId'];
        }
    }
    for ($idx = 0; $idx < count($badges); $idx++) {
        $badge = $badges[$idx];
        if (array_key_exists('inPlan', $badge) && $badge['inPlan'] == ($planOnly ? true : false)) {
            $applied++;
            $due = $badge['price'] - ($badge['paid'] + $badge['couponDiscount']);
            if ($applied == $count) // last row, give it all of the balance
                $paid_amt = ($due < ($balance + 0.01)) ? $due : $balance; //deal with rounding error
            else
                $paid_amt = ($ratio == 1) ? $due : round($ratio * $due, 2);
            if ($paid_amt > ($due - 0.01)) // deal with rounding error
                $paid_amt = $due; // just in case

            // only update those that were actually modified
            if ($badge['memCategory'] == 'upgrade' && $paid_amt <= $balance) {
                // ok this upgrade is now paid for, mark the old one upgraded
                // upgrades require a role to allow them to be bought based on the prior membership being in the cart, get the rule for this membership

                $mrR = dbSafeQuery($mrQ, 's', array ('%,' . $badge['memId'] . ',%'));
                if ($mrR !== false) {
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
            $regStatus = ($left < 0.01) ? 'paid' : ($planOnly ? 'plan' : 'unpaid');
            $rows_upd += dbSafeCmd($regU, 'ddisii', array (
                $paid_amt,
                $badge['couponDiscount'],
                ($left < 0.01) ? $transId : null,
                $regStatus,
                $planId,
                $badge['regId']
            ));
            $badges[$idx]['paid'] += $paid_amt;
            $badges[$idx]['status'] = $regStatus;
            $badges[$idx]['balance_due'] = $left < 0.01 ? 0 : $left;
            $badges[$idx]['modified'] = true;
        }
    }
    return $rows_upd;
}