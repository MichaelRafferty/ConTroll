<?php
// library AJAX Processor: exhibitorsSpacePayment.php
// ConTroll Registration System
// Author: Syd Weinstein
// process the payment of space and memberships from the exhibitor tab of controll
//     if payment type != offline credit card - create order and payment information in credit card syste,
//     if payment succeeds, create/update all the elements in the database.
require_once '../lib/base.php';
require_once '../../lib/tax.php';
require_once '../../lib/log.php';
require_once('../../lib/cc__load_methods.php');
require_once '../../lib/receipt.php';
require_once('../../lib/exhibitorArtistInventoryEmail.php');
require_once('../../lib/email__load_methods.php');
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'exhibitor';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid=$con['id'];
$cc = get_conf('cc');

$required = getConfValue('reg', 'required', 'addr');
$response['conid'] = $conid;

$log = get_conf('log');
logInit($log['vendors']);

if (!array_key_exists('orderData', $_POST)) {
    $response['error'] = 'Invalid calling sequence';
    ajaxSuccess($response);
    exit();
}

$data = json_decode($_POST['orderData'], true);
if (array_key_exists('nonce', $_POST) && $_POST['nonce'] == 'c') {
    $results = $data['results'];
    cleanRegs($results['badges'], $results['transid']);
    ajaxSuccess(array('status'=>'success', 'message'=>'Payment canceled'));
    exit;
}

// check for amtDue to see if it's > 0 and if so check for prow
if (array_key_exists('amtDue', $_POST)) {
    $amtDue = $_POST['amtDue'];
} else {
    $amtDue = null;
}

if ($amtDue != null && $amtDue > 0) {
    if (!(array_key_exists('prow', $_POST))) {
        $response['error'] = 'Invalid calling sequence';
        ajaxSuccess($response);
        exit();
    }
}

if (array_key_exists('prow', $_POST)) {
    $prow = $_POST['prow'];
} else {
    $prow = null;
}

$crow = null;   // common code, no change processed in this routine
$desc='';

if (array_key_exists('portalType', $_POST))
    $portalType = $_POST['portalType'];
else
    $portalType = 'exhibits';

if (array_key_exists('location_controllexhibits', $cc)) {
    $ccLocation = $cc['location_controllexhibits'];
} else if (array_key_exists('location_' . $portalType, $cc)) {
    $ccLocation = $cc['location_' . $portalType];
} else if (array_key_exists('location', $cc)) {
    $ccLocation = $cc['location'];
} else {
    $ccLocation = 'Unknown';
}

$error_msg = '';
$status_msg = '';

$results = $data['results'];
$orderRtn = $data['orderRtn'];
$orderResults = $data['orderResults'];
$spaces = $results['spaces'];
$badgeResults = $results['badges'];
$badges = $results['formbadges'];
$transId = $results['transid'];
$exhibitor = $results['vendor'];
$regionYearId = $orderResults['regionYearId'];
$region = $orderResults['region'];
$eryID = $orderResults['eryID'];
$specialRequests = $results['specialrequests'];
$results['nonce'] = $_POST['nonce'];
$transid = $results['transid'];
$buyer = $orderResults['buyer'];
$preTaxAmt = $orderRtn['preTaxAmt'];
$taxAmt = $orderRtn['taxAmt'];
$orderId = $orderRtn['orderId'];
$order = $orderRtn['order'];
$results['buyer'] = $buyer;
$totprice = $results['totalAmt'];

if (array_key_exists('spacePrice', $_POST))
    $spacePrice = $_POST['spacePrice'];
else
    $spacePrice = 0;

if (array_key_exists('payment_type', $_POST))
    $paymentType = $_POST['payment_type'];
else
    $paymentType = 'other';

if (array_key_exists('pay-desc', $_POST))
    $payDesc = $_POST['pay-desc'];
else
    $payDesc = 'Administrator Payment';

if (array_key_exists('pay-ccauth', $_POST))
    $ccAuth = $_POST['pay-ccauth'];
else
    $ccAuth = null;

if (array_key_exists('cancelOrderId', $_POST))
    $cancelOrderId = $_POST['cancelOrderId'];
else
    $cancelOrderId = null;

$exhId = $_POST['exhibitorId'];
$eyID = $_POST['exhibitorYearId'];
$source = 'controll-exhibitor';

$curLocale = locale_get_default();
$dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

if ($totprice > 0) {
    load_cc_procs();

    $change = 0;
    // now process the payment itself
    switch ($prow['type']) {
        case 'cash':
            $externalType = 'OTHER';
            $nonce = 'EXTERNAL';
            $desc = 'CASH';
            if ($crow)
                $change = -$crow['amt'];
            break;
        case 'online':
            $nonce = $prow['nonce'];
            break;
        case 'discount':
            $desc = 'disc: ';
            break;
        case 'credit':
            $externalType = 'CARD';
            // set stuff to bypass cc call
            $desc = $prow['desc'];
            $rtn['amount'] = $totprice;
            $rtn['paymentType'] = 'credit';
            $rtn['preTaxAmt'] = $preTaxAmt;
            $rtn['taxAmt'] = $taxAmt;
            $rtn['paymentId'] = null;
            $rtn['url'] = null;
            $rtn['rid'] = null;
            $rtn['auth'] = $prow['ccauth'];
            $rtn['payment'] = null;
            $rtn['last4'] = null;
            $rtn['txTime'] = date_create()->format('Y-m-d H:i:s');
            $rtn['status'] = 'COMPLETED';
            $rtn['transId'] = $transid;
            $rtn['category'] = 'reg';
            $rtn['description'] = $prow['desc'];
            $rtn['source'] = $source;
            $rtn['nonce'] = $externalType;
            break;
        case 'check':
            $externalType = 'CHECK';
            $nonce = 'EXTERNAL';
            $desc = 'Chk No: ' . $prow['checkno'];
            break;
    }

    if ($prow['type'] != 'credit') {
        if ($desc == '')
            $desc = $prow['desc'];
        else
            $desc = mb_substr($desc . '/' . $prow['desc'], 0, 64);

        $ccParam = array (
            'transid' => $transid,
            'counts' => 0,
            'price' => null,
            'badges' => null,
            'taxAmt' => $taxAmt,
            'taxes' => $orderRtn['taxes'],
            'preTaxAmt' => $preTaxAmt,
            'total' => $totprice,
            'orderId' => $orderId,
            'nonce' => $nonce,
            'coupon' => null,
            'externalType' => $externalType,
            'desc' => $desc,
            'source' => $source,
            'change' => $change,
            'locationId' => $ccLocation,
        );

        //log requested badges
        logWrite(array ('type' => 'online', 'con' => $con['conname'], 'trans' => $transid, 'results' => $ccParam));
        $rtn = cc_payOrder($ccParam, $buyer, true);
        if ($rtn === null) {
            ajaxSuccess(array ('error' => 'Credit card not approved'));
            exit();
        }
    }

    $approved_amt = $rtn['amount'];
    $type = $rtn['paymentType'];
    $preTaxAmt = $rtn['preTaxAmt'];
    $taxAmt = $rtn['taxAmt'];
    $paymentId = $rtn['paymentId'];
    $receiptUrl = $rtn['url'];
    $receiptNumber = $rtn['rid'];
    $paymentType = $rtn['paymentType'];
    $auth = $rtn['auth'];
    $payment = $rtn['payment'];
    $last4 = $rtn['last4'];
    $txTime = $rtn['txTime'];
    $status = $rtn['status'];
    $transId = $rtn['transId'];
    $category = $rtn['category'];
    $description = $rtn['description'];
    $source = $rtn['source'];
    $nonce = $rtn['nonce'];
    if ($nonce == 'EXTERNAL')
        $nonceCode = $ccParam['externalType'];
    else
        $nonceCode = $nonce;
    $complete = round($approved_amt,2) == round($totprice,2);
}

// extract the values needed for the payment
if ($prow != null) {
    if ($paymentType == 'check') {
        $desc = 'Check No: ' . $_POST['pay-checkno'] . ', ' . $payDesc;
    } else {
        $desc = $payDesc;
    }

    // now the main payment
    if ($taxAmt > 0)
        $taxes = $orderRtn['taxes'];
    else
        $taxes = [];

    [$taxFields, $taxSql, $taxStr, $taxValues] = buildTaxInsert($taxes);
    if ($taxFields != '')
        $taxFields = ", $taxFields";
    if ($taxSql != '')
        $taxSql = ", $taxSql";

    if ($paymentType == 'credit') {
        $txnQ = <<<EOS
INSERT INTO payments(transid, type,category, description, source, pretax, tax, amount, time, cc_approval_code, cashier, 
    cc, nonce, cc_txn_id, txn_time, receipt_url, receipt_id, userPerid, status, ccPaymentId $taxFields)
VALUES (?,?,?,?,'cashier',?,?,?,now(),?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? $taxSql);
EOS;
        $typestr = 'isssdddsissssssiss' . $taxStr;
        $values = array ($transId, $paymentType, $category, $description, $preTaxAmt, $taxAmt, $approved_amt, $auth, null,
            $last4, $nonceCode, $paymentId, $txTime, $receiptUrl, $receiptNumber, $buyer, $status, $paymentId);
        $txnid = dbSafeInsert($txnQ, $typestr, array_merge($values, $taxValues));
        $approved_amt = $rtn['amount'];
    } else {
        $txnQ = <<<EOS
INSERT INTO payments (transid, type, category, description, source, pretax, tax, amount, time, nonce, cc_approval_code, txn_time, userPerid,
                      ccPaymentId $taxFields)
VALUES (?,?,?,?,?,?,?,?,NOW(),?,?,NOW(),?,? $taxSql);
EOS;
        $typestr = 'issssdddssis'. $taxStr;
        $values = array ($transid, $paymentType, $category, $desc, $source, $totprice, 0, $totprice, 'admin', $ccAuth,
            $authToken->getPerid(), $paymentId);
        $txnid = dbSafeInsert($txnQ, $typestr, array_merge($values, $taxValues));
        $approved_amt = $totprice;
    }
    if ($txnid == false) {
        $error_msg .= "Insert of payment failed\n";
    } else {
        $status_msg .= "Payment for " . $dolfmt->formatCurrency($totprice, 'USD') . " processed<br/>\n";
    }

    // update the transaction with the taxes and order id
    $taxes = $orderRtn['taxes'];
    [$taxSql, $taxStr, $taxValues] = buildTaxUpdate($taxes);
    $upT = <<<EOS
UPDATE transaction
SET price = ?, tax = ?, withTax = ?, couponDiscountCart = ?, orderId = ?, paymentStatus = 'ORDER', orderDate = now(), $taxSql
WHERE id = ?;
EOS;
    $preTaxAmt = $orderRtn['preTaxAmt'];
    $taxAmt = $orderRtn['taxAmt'];
    $withTax = $orderRtn['totalAmt'];
    $valArray = array($preTaxAmt, $taxAmt, $withTax, 0, $orderRtn['orderId']);
    $typeStr = 'dddds' . $taxStr . 'i';
    $valArray = array_merge($valArray, $taxValues);
    $valArray[] = $transid;

    $numUpd = dbSafeCmd($upT, $typeStr, $valArray);
} else {
    $approved_amt = $totprice;
    $taxes = array ();
}
$results['approved_amt'] = $approved_amt;

// update the other records with the payment information
// Transaction
$txnUpdate = 'UPDATE transaction SET ';
if (round($approved_amt,2) == round($totprice,2)) {
    $txnUpdate .= 'complete_date=current_timestamp(), ';
}

$txnUpdate .= 'paid=?,  ccPaymentId = ?, paymentStatus = ? WHERE id=?;';
$txnU = dbSafeCmd($txnUpdate, 'dssi', array($approved_amt, $rtn['paymentId'], $rtn['status'], $transid));
if ($txnU != 1) {
    $error_msg .= "Unable to mark transaction completed\n";
}

// reg (badge)
$regQ = "UPDATE reg SET paid=price, status='paid', complete_trans=? WHERE create_trans=?;";
$numrows = dbSafeCmd($regQ, 'ii', array($transid, $transid));

// vendor_space
$vendorUQ = <<<EOS
UPDATE exhibitorSpaces
SET item_purchased = ?, price=?, paid=?, transid = ?, membershipCredits = 0, time_purchased = now()
WHERE id = ?
EOS;
foreach ($spaces as $id => $space) {
    $num_rows = dbSafeCmd($vendorUQ, 'iddii', array($space['item_approved'], $space['approved_price'], $space['approved_price'], $transid, $space['id']));
    if ($num_rows == 0) {
        $error_msg .= "Unable to mark " . $space['name']  . " space purchased\n";
    } else {
        $status_msg .= "Space " . $space['name'] . " marked purchased<br/>\n";
    }
}

// assign exhibitor id and the agent if its null
// rule: if exhibitor is mailin, use largest exhibitor number + 1 that is greater than mailin base.
//      if exhibitor is not mailin, use largest exhibitor number = 1 that is greater than atcon base and less that mailin base (if mailin base is != atconbase)
$exNumQ = <<<EOS
SELECT IFNULL(exRY.exhibitorNumber, 0) AS exhibitorNumber, exRY.id, agentPerid, agentNewperson, mailin
FROM exhibitorRegionYears exRY
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
WHERE conid = ? and exhibitorId = ? and exRY.exhibitsRegionYearId = ?
EOS;
$exNumR = dbSafeQuery($exNumQ, 'iii', array($conid, $exhibitor['exhibitorId'], $regionYearId));
if ($exNumR == false || $exNumR->num_rows == 0) {
    $error_msg .= "Unable to retrieve existing exhibitor number<br/>\n";
}
$exNumL = $exNumR->fetch_assoc();
$exRYid = $exNumL['id'];
$exhNum = $exNumL['exhibitorNumber'];
$exPerid = $exNumL['agentPerid'];
$exNewPerson = $exNumL['agentNewperson'];
$exMailin = $exNumL['mailin'];
$exNumR->free();

// first the agent
if ($exMailin == 'N') {
    if (array_key_exists('agent', $_POST))
        $agent = $_POST['agent'];
    else
        $agent = 'first';

    $perid = null;
    $newperid = null;
    $agentRequest = null;
    if ($agent == 'first') {
        if (count($badges) > 0) {
            $perid = $badges[0]['perid'];
            $newperid = $badges[0]['newperid'];
        } else {
            $perid = $exhibitor['perid'];
            $newperid = $exhibitor['newperid'];
        }
    } else if ($agent == 'self') {
        $agentRequest = 'Assign me as my own agent please.';
    } else if ($agent = 'request') {
        $agentRequest = $_POST['agent_request'];
    } else {
        if (str_starts_with($agent, 'p'))
            $perid = substr($agent, 1);
        else
            $newperid = substr($agent, 1);
    }

    if ($perid == null && $newperid == null && $agentRequest == null) {
        $perid = $exhibitor['perid'];
        $newperid = $exhibitor['newperid'];
    }
    $updAgent = <<<EOS
UPDATE exhibitorRegionYears
SET agentPerid = ?, agentNewperson = ?, agentRequest = ?
WHERE id = ?;
EOS;
    $num_rows = dbSafeCmd($updAgent, 'iisi', array($perid, $newperid, $agentRequest, $exRYid));

    // update the master agents if needed
    if ($exhibitor['perid'] == null && $exhibitor['newperid'] == null) {
        $updMaster = <<<EOS
UPDATE exhibitors
SET perid = ?, newperid = ?
WHERE id = ?;
EOS;
        $num_rows = dbSafeCmd($updMaster, 'iii', array($perid, $newperid, $exhibitor['exhibitorId']));
    }
}

if ($exhNum == 0) {
    $nextID = -1;
    if ($exhibitor['mailin'] == 'N') {
        if ($region['atconIdBase'] < $region['mailinIdBase']) {
            $nextIdQ = <<<EOS
SELECT MAX(exhibitorNumber)
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
WHERE exhibitorNumber is NOT NULL AND exhibitorNumber >= ? AND exhibitorNumber < ? AND conid = ? and exRY.exhibitsRegionYearId = ?;
EOS;
            $nextIDR = dbSafeQuery($nextIdQ, 'iiii', array($region['atconIdBase'], $region['mailinIdBase'], $conid, $regionYearId));
            if ($nextIDR == false || $nextIDR->num_rows == 0) {
                $nextID = $region['atconIdBase'] + 1;
            } else {
                $nextL = $nextIDR->fetch_row();
                $nextID = $nextL[0] == NULL ? $region['atconIdBase'] + 1 : $nextL[0] + 1;
            }
        } else if ($region['atconIdBase']) {
            $nextIdQ = <<<EOS
SELECT MAX(exhibitorNumber)
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
WHERE exhibitorNumber is NOT NULL AND exhibitorNumber >= ? AND conid = ? and exRY.exhibitsRegionYearId = ?;
EOS;
            $nextIDR = dbSafeQuery($nextIdQ, 'iii', array($region['atconIdBase'], $conid, $regionYearId));
            if ($nextIDR == false || $nextIDR->num_rows == 0) {
                $nextID = $region['atconIdBase'] + 1;
            } else {
                $nextL = $nextIDR->fetch_row();
                $nextID = $nextL[0] == NULL ? $region['atconIdBase'] + 1 : $nextL[0] + 1;
            }
        }
    }
    if ($nextID < 0) {
        $nextIdQ = <<<EOS
SELECT MAX(exhibitorNumber)
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
WHERE exhibitorNumber is NOT NULL AND exhibitorNumber >= ? AND conid = ? and exRY.exhibitsRegionYearId = ?;
EOS;
        $nextIDR = dbSafeQuery($nextIdQ, 'iii', array($region['mailinIdBase'], $conid, $regionYearId));
        if ($nextIDR == false || $nextIDR->num_rows == 0) {
            $nextID = $region['mailinIdBase'] + 1;
        } else {
            $nextL = $nextIDR->fetch_row();
            $nextID = $nextL[0] == NULL ? $region['mailinIdBase'] + 1 : $nextL[0] + 1;
        }
    }
    $updNum = <<<EOS
UPDATE exhibitorRegionYears
SET exhibitorNumber = ?
WHERE id = ?;
EOS;
    $numrows = dbSafeCmd($updNum, 'ii', array($nextID, $exRYid));
    if ($numrows != 1) {
        $error_msg .= "Unable to assign exhibitor number<br/>\n";
    } else {
        $status_msg .= "You have been assigned Exhibitor Number $nextID for this space.<br/>\n";
    }
}

// send the payment emails
load_email_procs();
// receipt only if the amount was > 0.
if ($approved_amt > 0) {
    loadCustomText('exhibitor', 'index', getConfValue('vendor', 'customtext', 'production'), false);
    $emails = paymentConfirm($results);
    $return_arr = send_email($region['ownerEmail'], array($exhibitor['exhibitorEmail'], $buyer['email']), $region['ownerEmail'], $region['name'] . ' Payment', $emails[0], $emails[1]);

    if (array_key_exists('error_code', $return_arr)) {
        $error_code = $return_arr['error_code'];
    } else {
        $error_code = null;
    }

    if (array_key_exists('email_error', $return_arr))
        $error_msg .= $return_arr['email_error'];
    else
        $status_msg .= "Payment Receipt Sent<br/>\n";

}

// artist inventory request
$return_arr = emailArtistInventoryReq($eryID, 'Payment');

if (array_key_exists('error_code', $return_arr)) {
    $error_code = $return_arr['error_code'];
} else {
    $error_code = null;
}

if (array_key_exists('email_error', $return_arr))
    $error_msg .= $return_arr['email_error'];
else
    $status_msg .= "Inventory Entry Request Sent<br/>\n";

ajaxSuccess(array(
    'status' => $error_msg != '' ? 'error' : 'success',
    'error' => $error_msg,
    'trans' => $transid,
    'message' => $status_msg,
));
return;

// space payment confirmation
function paymentConfirm($results) {
    $receipts = trans_receipt($results['transid']);

    $currency = getConfValue('con', 'currency', 'USD');
    $buyer = $results['buyer'];
    $region = $results['region'];

    $label = getConfValue('con', 'label', 'Unknown Convention');
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);

    // plain text version
    $body = 'Dear ' . $buyer['name'] . ":\n\n" .
        'Here is your receipt for payment of ' . $dolfmt->formatCurrency($results['approved_amt'], $currency) . ' for ' . $label .
        ' ' . $region['name'] . "\n\n" . $receipts['receipt'] . "\n\n" .
        'If you have any questions please contact the ' . $region['name'] . ' staff at ' . $region['ownerEmail'] . ".\n\nThank you\n";

    // html version
    $bodyHtml = '<p>Dear ' . $buyer['name'] . ":</p>\n" .
        '<p>Here is your receipt for payment of ' . $dolfmt->formatCurrency($results['approved_amt'], $currency) . ' for ' . $label .
        ' ' . $region['name'] . "</p>\n" . $receipts['receipt_tables'] .
        '<p>If you have any questions please contact the ' . $region['name'] . ' staff at ' . $region['ownerEmail'] . ".</p>\n<p>Thank you</p>\n";

    return array ($body, $bodyHtml);
}

// cleanup up on a credit card failure (order or payment)
function cleanRegs($badges, $transid) {
    $delReg = <<<EOS
DELETE FROM reg
WHERE id = ?;
EOS;

    $delInterests = <<<EOS
DELETE FROM memberInterests
WHERE newperid = ?;
EOS;

    $delPolicies = <<<EOS
DELETE FROM memberPolicies
WHERE newperid = ?;
EOS;

    $clrNewperson = <<<EOS
UPDATE newperson
SET transid = NULL
WHERE id = ?;
EOS;

    $clrTransaction = <<<EOS
UPDATE transaction
SET newperid = NULL
WHERE newperid = ?;
EOS;

    $delNewperson = <<<EOS
DELETE FROM newperson
WHERE id = ?;
EOS;

    $delTransaction = <<<EOS
DELETE FROM transaction
WHERE id = ?;
EOS;


// first the regs
    foreach ($badges as $badge) {
        $regId = $badge['regId'];
        // delete the reg entry
        $numDel = dbSafeCmd($delReg, 'i', array ($regId));
    }

    // now the newperid
    foreach ($badges as $badge) {
        if (array_key_exists('id', $badge)) {
            $newPerid = $badge['id'];
            // clear the newperson entry
            $numDel = dbSafeCmd($clrNewperson, 'i', array ($newPerid));
            $numDel = dbSafeCmd($clrTransaction, 'i', array ($newPerid));
            $numDel = dbSafeCmd($delInterests, 'i', array ($newPerid));
            $numDel = dbSafeCmd($delPolicies, 'i', array ($newPerid));

            // delete the newperson entry
            $numDel = dbSafeCmd($delNewperson, 'i', array ($newPerid));
        }
    }
    // delete the transaction
    $numDel = dbSafeCmd($delTransaction, 'i', array ($transid));
}
