<?php
    require_once("../../lib/paymentPlans.php");

//  receipt.php - library of modules related building receipts

// spaceGetPaymentTrans
//      from space lists, given a exhibitorId and a regionYearId, return the transactions(s) for those spaces
function spaceGetPaymentTrans($exhid, $regionYearId) : array {
    $transactions = [];
    // find the space records for a given exhibitor and a particular region for this year

    $spaceQ = <<<EOS
SELECT S.transid
FROM exhibitorSpaces S
JOIN exhibitorRegionYears R ON R.id = S.exhibitorRegionYear
JOIN exhibitorYears Y ON Y.id = R.exhibitorYearId
WHERE Y.exhibitorId = ? AND R.exhibitsRegionYearId = ? AND transid IS NOT NULL
ORDER BY S.transid;
EOS;
    $spaceR = dbSafeQuery($spaceQ,'ii', array($exhid, $regionYearId));
    if ($spaceR === false) {
        return $transactions;
    }
    while ($transId =$spaceR->fetch_row()[0]) {
        $transactions[] = $transId;
    }
    $spaceR->free();

    return $transactions;
}

// from reg record, get transaction (complete preferred as that is payment)
function regReceipt($regid) : array | null {
    $transId = null;
    $regQ = <<<EOS
SELECT IFNULL(complete_trans, create_trans) as transid
FROM reg
WHERE id = ?;
EOS;
    $regR = dbSafeQuery($regQ,'i', array($regid));
    if ($regR === false) {
        return null;
    }
    if ($regR->num_rows > 0) {
        $transId = $regR->fetch_row()[0];
    }
    $regR->free();

    if ($transId != null)
        return trans_receipt($transId);

    return null;
}

// from payor plan, get transactions
function payorPlanGetPaymentTrans($payorPlanId) : array  {
    $transactions = [];

    // first the down payment
    $planQ = <<<EOS
SELECT createTransaction
FROM payorPlans
WHERE id = ? AND createTransaction IS NOT NULL
EOS;
    $planR = dbSafeQuery($planQ,'i', array($payorPlanId));
    if ($planR === false) {
        return $transactions;
    }
    if ($planR->num_rows > 0) {
        $transactions[] =$planR->fetch_row()[0];
    }
    $planR->free();

    $planQ = <<<EOS
SELECT transactionId
FROM payorPlanPayments
WHERE payorPlanId = ? AND transactionId IS NOT NULL
ORDER BY transactionId;
EOS;

    $planR = dbSafeQuery($planQ,'i', array($payorPlanId));
    if ($planR === false) {
        return $transactions;
    }
    while ($transId =$planR->fetch_row()[0]) {
        $transactions[] = $transId;
    }
    $planR->free();

    return $transactions;
}

// trans_receipt - given a transaction number build a receipt
// Fetches all the data to make up a receipt and then calls 'reg_format_receipt' to actually format the receipt as plain text, HTML and email tables.
function trans_receipt($transid) {
    // find all the items attached to this transaction id for payment:
    // possible items:
    //      memberships (online reg, portal, controll/registration, atcon)
    //      payment plan initial payment (portal)
    //      payment plan payments (portal, eventually registration, finance)
    //      space payments including fees (exhibitor portals, controll/exhibitors)
    //      art payments (atcon/artpos)
    // payment types to handle
    //      credit
    //      cash
    //      check
    //      coupon
    //      discount
    //      refund (future?)
    //      other

    //$transid = 4268 ;// test art item
    // items gathered
    $response = [];     // return associative array of all the data
    $emails = [];       // people mentioned in the data
    $payments = [];     // payment records
    $memberships = [];  // memberships
    $plans = [];        // payment plans
    $planPayments = []; // payment plan payments
    $spaces = [];       // exhibitor spaces
    $art = [];          // art sales

    $planId = -1;       // placeholder to see if there is a plan for membership match

    // get the transaction information
    $transQ = <<<EOS
SELECT t.*, DATE_FORMAT(create_date, '%W, %M %e, %Y %h:%i:%s %p') as create_date_str,
       DATE_FORMAT(complete_date, '%W, %M %e, %Y %h:%i:%s %p') as complete_date_str,
       c.name AS couponName, c.couponType, c.discount AS couponDiscount, c.code AS couponCode,
    CASE 
        WHEN p.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' '))
        WHEN n.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT_WS(' ', n.first_name, n.middle_name, n.last_name, n.suffix), ' +', ' '))
        ELSE 'Unknown'
    END AS fullName,
    CASE 
        WHEN p.id IS NOT NULL THEN p.badge_name
        WHEN n.id IS NOT NULL THEN n.badge_name
        ELSE ''
    END AS badge_name,
    CASE 
        WHEN p.id IS NOT NULL THEN p.badgeNameL2
        WHEN n.id IS NOT NULL THEN n.badgeNameL2
        ELSE ''
    END AS badgeNameL2,
    CASE 
        WHEN p.id IS NOT NULL THEN p.first_name
        WHEN n.id IS NOT NULL THEN n.first_name
        ELSE ''
    END AS first_name,
    CASE 
        WHEN p.id IS NOT NULL THEN p.last_name
        WHEN n.id IS NOT NULL THEN n.last_name
        ELSE ''
    END AS last_name,
    CASE
        WHEN p.id IS NOT NULL THEN p.email_addr
        WHEN n.id IS NOT NULL THEN n.email_addr
        ELSE ''
    END AS email_addr,
        CASE
        WHEN p.id IS NOT NULL THEN p.address
        WHEN n.id IS NOT NULL THEN n.address
        ELSE ''
    END AS address,
    CASE
        WHEN p.id IS NOT NULL THEN p.addr_2
        WHEN n.id IS NOT NULL THEN n.addr_2
        ELSE ''
    END AS addr_2,
    CASE
        WHEN p.id IS NOT NULL THEN p.city
        WHEN n.id IS NOT NULL THEN n.city
        ELSE ''
    END AS city,
    CASE
        WHEN p.id IS NOT NULL THEN p.state
        WHEN n.id IS NOT NULL THEN n.state
        ELSE ''
    END AS state,
    CASE
        WHEN p.id IS NOT NULL THEN p.zip
        WHEN n.id IS NOT NULL THEN n.zip
        ELSE ''
    END AS zip,
    CASE
        WHEN p.id IS NOT NULL THEN p.country
        WHEN n.id IS NOT NULL THEN n.country
        ELSE ''
    END AS country,
    CASE
        WHEN p.id IS NOT NULL THEN 'perinfo'
        WHEN n.id IS NOT NULL THEN 'newperson'
        ELSE ''
    END AS tablename
FROM transaction t
LEFT OUTER JOIN perinfo p ON p.id = t.perid
LEFT OUTER JOIN newperson n ON n.id = t.newperid
LEFT OUTER JOIN coupon c ON t.coupon = c.id
WHERE t.id = ?;
EOS;

    $transR = dbSafeQuery($transQ, 'i', array($transid));
    if ($transR === false || $transR->num_rows != 1) {
        RenderErrorAjax('Transaction not found');
        exit();
    }

    $transL = $transR->fetch_assoc();
    $transL['badgename'] = badgeNameDefault($transL['badge_name'], $transL['badgeNameL2'], $transL['first_name'], $transL['last_name']);
    $emails[] = $transL['email_addr'];

    $conid = $transL['conid'];
    $userid = $transL['userid'];
    $type = $transL['type'];

    $response['transid'] = $transid;
    $response['type'] = $type;
    $response['userid'] = $userid;
    $response['transaction'] = $transL;

    //// now the items that mention this transaction
    //      payments
    $payQ = <<<EOS
SELECT *
FROM payments
WHERE transid = ?
ORDER BY id;
EOS;
    $payR = dbSafeQuery($payQ,'i', array($transid));
    if ($payR === false) {
        RenderErrorAjax('Payment query error');
        exit();
    }
    while ($payR->fetch_assoc()) {
        $payments[] = $payR;
    }
    $payR->free();
    $response['payments'] = $transL;

    //      payment plan initial payment (portal)
    //      payor plan perid/newperid = transaction perid/newperid, so name info not needed
    $planQ = <<<EOS
SELECT pp.*, p.name, p.description, DATE_FORMAT(pp.createDate, '%W, %M %e, %Y %h:%i:%s %p') as createDateStr,
CASE
    WHEN pI.id IS NOT NULL THEN pI.email_addr
    WHEN nI.id IS NOT NULL THEN nI.email_addr
    ELSE ''
END AS email_addr
FROM payorPlans pp
JOIN paymentPlans p ON p.id = pp.planId
LEFT OUTER JOIN perinfo pI ON pp.perid = pI.id
LEFT OUTER JOIN newperson nI ON pp.newperid = nI.id
WHERE createTransaction = ?;
EOS;

    $planR = dbSafeQuery($planQ, 'i', array($transid));
    if ($planR === false) {
        RenderErrorAjax('Payment Plan query failed');
        exit();
    }
    while ($planL = $planR->fetch_assoc()) {
        $plans[] = $planL;
        $planId = $planL['id'];
        $emails[] = $planL['email_addr'];
    }
    $planR->free();
    $response['plans'] = $plans;

    //      payment plan payments (portal, eventually registration, finance)
    $planQ = <<<EOS
SELECT ppp.*, p.name, p.description, pp.createDate, pp.balanceDue, pp.id, pp.status, pp.payByDate, pp.planId, pp.daysBetween,
       pp.minPayment,pp.initialAmt, pp.openingBalance, DATE_FORMAT(pp.createDate, '%W, %M %e, %Y %h:%i:%s %p') as createDateStr,
    CASE
        WHEN pI.id IS NOT NULL THEN pI.email_addr
        WHEN nI.id IS NOT NULL THEN nI.email_addr
        ELSE ''
    END AS email_addr
FROM payorPlanPayments ppp
JOIN payorPlans pp ON pp.id = ppp.payorPlanId
JOIN paymentPlans p ON p.id = pp.planId
LEFT OUTER JOIN perinfo pI ON pp.perid = pI.id
LEFT OUTER JOIN newperson nI ON pp.newperid = nI.id
WHERE  ppp.transactionId = ?;
EOS;
    $planR = dbSafeQuery($planQ, 'i', array($transid));
    if ($planR === false) {
        RenderErrorAjax('Payment Plan Payments query failed');
        exit();
    }
    while ($planL = $planR->fetch_assoc()) {
        $planPayments[] = $planL;
        $planId = $planL['id'];
        $emails[] = $planL['email_addr'];
    }
    $planR->free();
    $response['planPayments'] = $planPayments;

    //      memberships (online reg, portal, controll/registration, atcon)
    $regQ = <<<EOS
SELECT r.*, m.label, m.shortname, m.memCategory, m.memType, m.memAge, m.price AS fullprice, m.taxable, m.conid AS memConid,
    c.name AS couponName, c.couponType, c.discount AS couponDiscount, c.code AS couponCode,
    CASE 
        WHEN p.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), ' +', ' '))
        WHEN n.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT_WS(' ', n.first_name, n.middle_name, n.last_name, n.suffix), ' +', ' '))
        ELSE 'Unknown'
    END AS fullName,
    CASE 
        WHEN p.id IS NOT NULL THEN p.badge_name
        WHEN n.id IS NOT NULL THEN n.badge_name
        ELSE ''
    END AS badge_name,
    CASE 
        WHEN p.id IS NOT NULL THEN p.badgeNameL2
        WHEN n.id IS NOT NULL THEN n.badgeNameL2
        ELSE ''
    END AS badgeNameL2,
    CASE 
        WHEN p.id IS NOT NULL THEN p.first_name
        WHEN n.id IS NOT NULL THEN n.first_name
        ELSE ''
    END AS first_name,
    CASE 
        WHEN p.id IS NOT NULL THEN p.last_name
        WHEN n.id IS NOT NULL THEN n.last_name
        ELSE ''
    END AS last_name,
    CASE
        WHEN p.id IS NOT NULL THEN p.email_addr
        WHEN n.id IS NOT NULL THEN n.email_addr
        ELSE ''
    END AS email_addr,
    CASE
        WHEN p.id IS NOT NULL THEN p.address
        WHEN n.id IS NOT NULL THEN n.address
        ELSE ''
    END AS address,
    CASE
        WHEN p.id IS NOT NULL THEN p.addr_2
        WHEN n.id IS NOT NULL THEN n.addr_2
        ELSE ''
    END AS addr_2,
    CASE
        WHEN p.id IS NOT NULL THEN p.city
        WHEN n.id IS NOT NULL THEN n.city
        ELSE ''
    END AS city,
    CASE
        WHEN p.id IS NOT NULL THEN p.state
        WHEN n.id IS NOT NULL THEN n.state
        ELSE ''
    END AS state,
    CASE
        WHEN p.id IS NOT NULL THEN p.zip
        WHEN n.id IS NOT NULL THEN n.zip
        ELSE ''
    END AS zip,
    CASE
        WHEN p.id IS NOT NULL THEN p.country
        WHEN n.id IS NOT NULL THEN n.country
        ELSE ''
    END AS country,
        CASE
        WHEN p.id IS NOT NULL THEN 'perinfo'
        WHEN n.id IS NOT NULL THEN 'newperson'
        ELSE ''
    END AS tablename
FROM reg r
JOIN memLabel m ON m.id = r.memId
LEFT OUTER JOIN perinfo p ON p.id = r.perid
LEFT OUTER JOIN newperson n ON n.id = r.newperid
LEFT OUTER JOIN coupon c ON c.id = r.coupon
WHERE r.complete_trans = ? OR r.create_trans = ? OR r.planId = ?
ORDER BY perid, newperid, id;
EOS;
    $regR = dbSafeQuery($regQ,'iii', array($transid, $transid, $planId));
    if ($regR === false) {
        RenderErrorAjax('Membership query error');
        exit();
    }
    while ($regL = $regR->fetch_assoc()) {
        $regL['badgename'] = badgeNameDefault($regL['badge_name'], $regL['badgeNameL2'], $regL['first_name'], $regL['last_name']);
        $memberships[] = $regL;
        $emails[] = $regL['email_addr'];
    }
    $regR->free();
    $response['memberships'] = $memberships;

    //      space payments including fees (exhibitor portals, controll/exhibitors)
    $spaceQ = <<<EOS
SELECT S.id, S.time_purchased, S.item_purchased, S.price, S.paid,  sp.code, sp.description, sp.units, sp.price AS spacePrice,
       sp.includedMemberships, sp.additionalMemberships, s.name, s.description AS spaceDescription,
       er.name AS regionName, er.description AS regionDescription, ert.portalType, RY.exhibitorNumber, Y.exhibitorId,
       CONCAT('e-', Y.exhibitorId) AS pid, E.exhibitorName AS last_name, '' AS first_name, E.exhibitorName AS fullName,
       Y.contactName AS badge_name, Y.mailin, ery.mailinFee, E.exhibitorName AS badgeNameL2, E.exhibitorEmail AS email_addr, E.addr AS address,
       E.addr2, E.city, E.state, E.zip, E.country, 'exhibitor' AS tablename,
       E.artistName, E.exhibitorName
FROM exhibitorSpaces S
JOIN exhibitsSpacePrices sp ON sp.id = S.item_purchased
JOIN exhibitsSpaces s ON s.id = S.spaceId
JOIN exhibitsRegionYears ery ON ery.id = s.exhibitsRegionYear
JOIN exhibitsRegions er ON er.id = ery.exhibitsRegion
JOIN exhibitsRegionTypes ert ON ert.regionType = er.regionType
JOIN exhibitorRegionYears RY ON S.exhibitorRegionYear = RY.id
JOIN exhibitorYears Y ON Y.id = RY.exhibitorYearId
JOIN exhibitors E ON E.id = Y.exhibitorId
WHERE S.transid = ?
EOS;
    $spaceR = dbSafeQuery($spaceQ, 'i', array($transid));
    if ($spaceR === false) {
        RenderErrorAjax('Space query failed');
        exit();
    }
    while ($spaceL = $spaceR->fetch_assoc()) {
        $spaceL['badgename'] = badgeNameDefault($spaceL['badge_name'], $spaceL['badgeNameL2'], $spaceL['first_name'], $spaceL['last_name']);
        $spaces[] = $spaceL;
    }
    $spaceR->free();
    $response['spaces'] = $spaces;
    //      art sales (atcon/artpos)
    $artQ = <<<EOS
SELECT s.*, i.status AS itemStatus, i.bidder, i.title, i.type, i.material, i.item_key, RY.exhibitorNumber, IFNULL(E.artistName, E.exhibitorName) AS artist
FROM artSales s
JOIN artItems i ON i.id = s.artId
JOIN exhibitorRegionYears RY ON i.exhibitorRegionYearId = RY.id
JOIN exhibitorYears Y ON Y.id = RY.exhibitorYearId
JOIN exhibitors E ON E.id = Y.exhibitorId
WHERE s.transId = ?;
EOS;
    $artR = dbSafeQuery($artQ, 'i', array($transid));
    if ($artR === false) {
        RenderErrorAjax('Art query failed');
        exit();
    }
    while ($artL = $artR->fetch_assoc()) {
        $art[] = $artL;
    }
    $artR->free();
    $response['art'] = $art;

    // if the payor is unknown, it's a space payment, before there is a payor (membership[0] created), update the info in TransL
    if (count($spaces) > 0) {
        if ($transL['fullName'] == 'unknown') {
            $fields = ['pid', 'tablenname', 'fullName', 'last_name', 'first_name', 'badge_name', 'badgeNameL2',
                'address', 'addr_2', 'city', 'state', 'zip', 'country'];
            foreach ($fields as $field) {
                $transL[$field] = $spaces[0][$field];
            }

        }
        $transL['mailinFee'] = $spaces[0]['mailinFee'];
        $transL['mailin'] = $spaces[0]['mailin'];
        $response['transaction'] = $transL;
    }

    $response['emails'] = array_unique($emails, SORT_STRING);
    return reg_format_receipt($response);
}

// reg_format_receipt - format a receipt in HTML and Text formats
// returns
//      $response['receipt'] = $receipt;
//      $response['receipt_html'] = $receipt_html;
//      $response['receipt_tables'] = $receipt_tables
// plus the calling $data as well
//
function reg_format_receipt($data) : array {
    $currency = getConfValue('con', 'currency', 'USD');
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);
    $memberSubtotal = 0;
    $artSubtotal = 0;
    $spaceSubtotal = 0;
    // ok, now there is all the data for the receipt
    // payor = who paid
    // people = details about people mentioned in the memberships
    // memberships = memberships purchased with expanded fields for memLabel
    // payments = payments made
    // coupons = coupons applied
    // transactions = transactions involved

    $response = $data;
    $master_transaction = $data['transaction'];
    // top lines of receipt - needs conlabel
    $condata = get_con();
    $conlabel = $condata['label'];
    $receipt_date = $master_transaction['complete_date'] ? "Completed on " . $master_transaction['complete_date_str'] : "Created on " . $master_transaction['create_date_str'];
    // Receipt Title:
    $receipt = "Receipt for payment to $conlabel\n$receipt_date\n";
    $receipt_html = <<<EOS
<div class="container-fluid border border-primary border-4">
    <div class="row">
        <div class="col-sm-12">
            <h1 class="size-h2"><strong>Receipt for payment to $conlabel</strong></h1>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            $receipt_date
        </div>
    </div>
EOS;
    $receipt_tables = <<<EOS
<table>
<tr><td colspan="3"><h1 class="size-h2"><strong>Receipt for payment to $conlabel</strong></h1></td></tr>
<tr><td colspan="3">$receipt_date</td></tr>
EOS;

    // Payor Info
    $type = $data['type'];
    $master_tid = $master_transaction['id'];
    $title_payor_name = $master_transaction['fullName'];
    $title_payor_badge = strip_tags(str_replace('<br/>', '/', $master_transaction['badgename']));
    $title_email = $master_transaction['email_addr'];

    $response['payor_name'] = $title_payor_name;
    $response['payor_email'] = $title_email;

    switch ($type) {
        case 'website':
            $receipt .= "By: $title_payor_name ($title_payor_badge), Via: Online Registration Website, Transaction: $master_tid\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-12">
            By: $title_payor_name ($title_payor_badge), Via: Online Registration Website, Transaction: $master_tid
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">By: $title_payor_name ($title_payor_badge), Via: Online Registration Website, Transaction: $master_tid</td></tr>
EOS;

            break;
        case 'regportal':
            $receipt .= "By: $title_payor_name ($title_payor_badge), Via: Registration Portal, Transaction: $master_tid\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-12">
            By: $title_payor_name ($title_payor_badge), Via: Registration Portal, Transaction: $master_tid
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">By: $title_payor_name ($title_payor_badge), Via: Registration Portal, Transaction: $master_tid</td></tr>
EOS;

            break;
        case 'vendor':
        case 'artist':
        case 'exhibitor':
            $receipt .= "By: $title_payor_name ($title_payor_badge), Via: $type portal, Transaction: $master_tid\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-12">
            By: $title_payor_name ($title_payor_badge), Via: $type portal, Transaction: $master_tid
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">By: $title_payor_name ($title_payor_badge), Via: $type portal, Transaction: $master_tid</td></tr>
EOS;

            break;
        case 'atcon':
            $cashier = $master_transaction['userid'];
            $receipt .= "By: $title_payor_name ($title_payor_badge), Via: On-Site Registration, Cashier: $cashier, Transaction: $master_tid\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-12">
            By: $title_payor_name ($title_payor_badge), Via: On-Site Registration Cashier: $cashier, Transaction: $master_tid
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">By: $title_payor_name ($title_payor_badge), Via: On-Site Registration Cashier: $cashier, Transaction: $master_tid</td></tr>
EOS;
            break;
        default: // reg_control receipts (registration, badgelist, people, etc.)
            $cashier = $master_transaction['userid'];
            $receipt .= "By: $title_payor_name ($title_payor_badge), Via: Registration Staff Member: $cashier, Transaction: $master_tid\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-12">
            By: $title_payor_name ($title_payor_badge), Via: Registration Staff Member: $cashier, Transaction: $master_tid
        </div>
    </div>
 EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">By: $title_payor_name ($title_payor_badge), Via: Registration Staff Member: $cashier, Transaction: $master_tid</td></tr>
EOS;
            break;
    }

    // Section: New Payment Plan
    foreach ($data['plans'] as $plan) {
        $planType = $plan['name'];
        $createDate = $plan['createDateStr'];
        $nonPlanAmount = $dolfmt->formatCurrency((float)$plan['nonPlanAmt'], $currency);
        $planAmount = $dolfmt->formatCurrency((float)$plan['initialAmt'] - $plan['nonPlanAmt'], $currency);
        $openingBalance = $dolfmt->formatCurrency((float)$plan['openingBalance'], $currency);
        $downPayment = $dolfmt->formatCurrency((float)$plan['downPayment'], $currency);
        $nm1 = $plan['numPayments'] - 1;
        $paymentAmt = $dolfmt->formatCurrency((float)$plan['minPayment'], $currency);
        $finalAmt = $dolfmt->formatCurrency((float)$plan['finalPayment'], $currency);
        $payments = '';
        if ($nm1 > 0) {
            if ($nm1 == 1)
                $payments .= "$nm1 payment of $paymentAmt plus ";
            else
                $payments .= "$nm1 payments of $paymentAmt plus ";
        }
        $payments .= "a final payment of $finalAmt";
        $completeDate = $plan['payByDate'];
        $receipt .= <<<EOS

Down Payment on a new payment plan created $createDate:

Plan type: $planType
Amount of purchase not covered by the plan: $nonPlanAmount
Amount of purchase covered by the plan: $planAmount
Opening Balance: $openingBalance
Down Payment: $downPayment
Payments: $payments
Must complete payments by: $completeDate
EOS;
        $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h2 class="size-h3">Down Payment on a new payment plan created $createDate:</h2>
        </div>
    </div>
    <div class='row'><div class='col-sm-12'>Plan type: $planType</div></div>
    <div class='row'><div class='col-sm-12'>Amount of purchase not covered by the plan: $nonPlanAmount</div></div>
    <div class='row'><div class='col-sm-12'>Amount of purchase covered by the plan: $planAmount</div></div>
    <div class='row'><div class='col-sm-12'>Opening Balance: $openingBalance</div></div>
    <div class='row'><div class='col-sm-12'>Down Payment: $downPayment</div></div>
    <div class='row'><div class='col-sm-12'>Payments: $payments</div></div>
    <div class='row'><div class='col-sm-12'>Must complete payments by: $completeDate</div></div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><h2 class="size-h3">Down Payment on a new payment plan created $createDate:</h2></td></tr>
<tr><td colspan="3">Plan type: $planType</td></tr>
<tr><td colspan="3">Amount of purchase not covered by the plan: $nonPlanAmount</td></tr>
<tr><td colspan="3">Amount of purchase covered by the plan: $planAmount</td></tr>
<tr><td colspan="3">Opening Balance: $openingBalance</td></tr>
<tr><td colspan="3">Down Payment: $downPayment</td></tr>
<tr><td colspan="3">Payments: $payments</td></tr>
<tr><td colspan="3">Must complete payments by: $completeDate</td></tr>
EOS;
    }

    // payment on a payment plan
    foreach ($data['planPayments'] as $payment) {
        // $data['numPmts'] = $numPmts;
        //    $data['lastPayment'] = $lastPayment;
        //    $data['lastPaidDate'] = $lastPaidDate;
        //    $data['nextPayDueDate'] = $nextPayDueDate;
        //    $data['nextPayDue'] = $nextPayDue;
        //    $data['minAmt'] = $minAmt;
        //    $data['minAmtNum'] = $minAmtNum;
        //    $data['nextPayTimestamp'] = $nextPayTimestamp;
        //    $data['daysPastDue'] = $dayPastDue;
        //    $data['numPmtsPastDue'] = $numPmtsPastDue;
        //    $data['dateCreated'] = $dateCreated;
        //    $data['payByDate'] = $payByDate;
        //    $data['balanceDue'] = $balanceDue;
        //    $data['initialAmt'] = $initialAmt;
        $d = computeNextPaymentDue($payment, null, $dolfmt, $currency);
        $pmtNo = $payment['paymentNbr'];
        $name = $payment['name'];
        $createDate = $payment['createDateStr'];
        $amt = $dolfmt->formatCurrency((float)$payment['amount'], $currency);
        $curBal = $dolfmt->formatCurrency((float)$payment['balanceDue'], $currency);
        $status = $payment['status'];
        $nextDue = $d['nextPayDue'];
        $openingBalance = $dolfmt->formatCurrency((float)$payment['openingBalance'], $currency);
        $statusLine = $status == 'paid' ? 'Status: Paid' : "Next Payment Due: $nextDue";

        $receipt .= <<<EOS

Payment on a $name payment plan created $createDate:

Payment: $pmtNo
Opening Balance: $openingBalance
Payment Amount: $amt
Current Balance: $curBal
$statusLine
EOS;
        $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h2 class="size-h3">Payment on a $name payment plan created $createDate:</h2>
        </div>
    </div>
    <div class='row'><div class='col-sm-12'>Payment: $pmtNo</div></div>
    <div class='row'><div class='col-sm-12'>Opening Balance: $openingBalance</div></div>
    <div class='row'><div class='col-sm-12'>Payment Amount: $amt</div></div>
    <div class='row'><div class='col-sm-12'>Current Balance: $curBal</div></div>
    <div class='row'><div class='col-sm-12'>$statusLine</div></div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><h2 class="size-h3">Payment on a $name payment plan created $createDate:</h2></td></tr>
<tr><td colspan="3">Opening Balance: $openingBalance</td></tr>
<tr><td colspan="3">Payment Amount: $amt</td></tr>
<tr><td colspan="3">Opening Balance: $openingBalance</td></tr>
<tr><td colspan="3">Current Balance: $curBal</td></tr>
<tr><td colspan="3">Opening Balance: $statusLine</td></tr>
EOS;
        // now add memberships affected by this payment
        $receipt .= "\nMemberships affected:\n";
        $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h2 class="size-h3">Memberships affected:</h2>
        </div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><h2 class="size-h3">Memberships affected:</h2></td></tr>
EOS;

        $curNameLine = '';
        foreach ($data['memberships'] as $membership) {
            $nameLine = $membership['fullName'] . ' (' . strip_tags(str_replace('<br/>', '/', $master_transaction['badgename'])) . ')';
            if ($curNameLine != $nameLine) {
                $age = $membership['memAge'];
                $receipt .= "\nMember: $nameLine, $age\n";
                $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h3 class="size-h4">Member $nameLine, $age</h3>
        </div>
    </div>
EOS;
                $receipt_tables .= <<<EOS
<tr><td colspan="3"><h3 class="size-h4">Member $nameLine, $age</h3></td></tr>
EOS;
                $curNameLine = $nameLine;
            }
            $regid = $membership['id'];
            $label = $membership['label'];
            $status = $membership['status'];
            switch ($status) {
                case 'paid':
                    $statusField = 'Paid';
                    break;
                case 'unpaid':
                case 'plan':
                    $bal = $membership['price'] - ($membership['paid'] + $membership['couponDiscount']);
                    $statusField = "Balance due: " . $dolfmt->formatCurrency((float)$bal, $currency);
                    break;
                default:
                    $statusField = '';
            }
            $receipt .= "$regid    $label    $statusField\n";
            $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'>$regid</div>
        <div class='col-sm-4'>$label</div>
        <div class='col-sm-6'>$statusField</div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td>$regid</td><td>$label</td><td>$statusField</td></tr>
EOS;
        }
    }

    // space purchases
    $exhibitorId = -1;
    $spaceSubtotal = 0;
    foreach ($data['spaces'] as $space) {
        if ($exhibitorId != $space['exhibitorId']) {
            // new exhibitor, repeat the space info
            $regionName = $space['regionName'];
            $exhibitorName = $space['exhibitorName'];
            $artistName = $space['artistName'];
            if ($artistName != null && $artistName != '' && $artistName != $exhibitorName) {
                $displayName = "$artistName/$exhibitorName";
            } else {
                $displayName = $exhibitorName;
            }
            $exhibitorId = $space['exhibitorId'];
            $exhibitorNumber = $space['exhibitorNumber'];
            // output the header
            $receipt .= "\n\n$regionName spaces for $displayName, Exhibitor id $exhibitorId, $regionName number $exhibitorNumber\n";
            $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h2 class="size-h3">$regionName spaces for $displayName, Exhibitor id $exhibitorId, $regionName number $exhibitorNumber</h2>
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3"><h2 class="size-h3">$regionName spaces for $displayName, Exhibitor id $exhibitorId, $regionName number $exhibitorNumber</h2></td></tr>
EOS;
        }
        // now the actual row
        $spaceName = $space['name'];
        $spacePriceName = $space['description'];
        $spaceSubtotal += (float) $space['price'];
        $price = $dolfmt->formatCurrency((float)$space['price'], $currency);
        $receipt .= "$spaceName    $spacePriceName    $price\n";
        $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-3'>$spaceName</div>
        <div class='col-sm-6'>$spacePriceName</div>
        <div class='col-sm-2' style="text-align: right;">$price</div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td>$spaceName</td><td>$spacePriceName</td><td>$price</td></tr>
EOS;
    }
    if (array_key_exists('mailinFee', $master_transaction)) {
        $mailInFee = $master_transaction['mailinFee'];
        if ($mailInFee != null && $mailInFee > 0) {
            $spaceSubtotal += (float) $mailInFee;
            $mailInFee = $dolfmt->formatCurrency((float)$mailInFee, $currency);
            $receipt .= "$mailInFee    $regionName    $mailInFee\n";
            $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-2'>Mail In Fee</div>
        <div class='col-sm-7'>$regionName</div>
        <div class='col-sm-2' style="text-align: right;">$mailInFee</div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td>Mail In Fee</td><td>$regionName</td><td>$mailInFee</td></tr>
EOS;
        }
    }
    if ($spaceSubtotal > 0) {
        $subtotal = $dolfmt->formatCurrency((float)$spaceSubtotal, $currency);
        $receipt .= "Space Subtotal: $subtotal\n";
        $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'></div>
        <div class='col-sm-8'>Space Subtotal</div>
        <div class='col-sm-2' style="text-align: right;">$subtotal</div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td></td><td>Space Subtotal:</td><td>$subtotal</td></tr>
EOS;
    }

    // main membership area
    if (count($data['planPayments']) == 0) {
        // if its not a payment on a plan, show the memberships here
        if (count($data['memberships']) > 0) {
            $receipt .= "\nMemberships:\n";
            $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h2 class="size-h3">Memberships:</h2>
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><h2 class="size-h3">Memberships:</h2></td></tr>
EOS;

            // first output the payor
            $memberSubtotal = 0;
            $master_perid = $master_transaction['perid'];
            $master_newperid = $master_transaction['newperid'];
            $curNameLine = '';
            foreach ($data['memberships'] as $membership) {
                if (($membership['perid'] != null && $membership['perid'] == $master_perid) ||
                    ($membership['newperid'] != null && $membership['newperid'] == $master_newperid)) {
                    $memberSubtotal += reg_format_mbr($dolfmt, $currency, $membership, $curNameLine, $receipt, $receipt_html, $receipt_tables);
                }
            }

            // now all but the payor
            $curNameLine = '';
            foreach ($data['memberships'] as $membership) {
                if (!(($membership['perid'] != null && $membership['perid'] == $master_perid) ||
                    ($membership['newperid'] != null && $membership['newperid'] == $master_newperid))) {
                    $memberSubtotal += reg_format_mbr($dolfmt, $currency, $membership, $curNameLine, $receipt, $receipt_html, $receipt_tables);
                }
            }
        }
        if ($memberSubtotal > 0) {
            $subtotal = $dolfmt->formatCurrency((float)$memberSubtotal, $currency);
            $receipt .= "Membership Subtotal: $subtotal\n";
            $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'></div>
        <div class='col-sm-8'>Membership Subtotal</div>
        <div class='col-sm-2' style="text-align: right;">$subtotal</div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td></td><td>Membership Subtotal:</td><td>$subtotal</td></tr>
EOS;
        }
    }

    // art sales
    if (count($data['art']) > 0) {
        $receipt .= "\n\nArt Sales:\n";
        $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h2 class="size-h3">Art Sales:</h2>
        </div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td colspan="3"><h2 class="size-h3">Art Sales:</h2></td></tr>
EOS;
        $needHeader = true;
        foreach ($data['art'] as $art) {
            if ($art['type'] == 'art') {
                if ($needHeader) {
                    $receipt .= "\n\nArt Auction Items:\nItem id    Title/Artist    Type/Amount";
                    $receipt_html .= <<<EOS
    <div class='row mt-2'>
        <div class='col-sm-12'>
            <h3 class="size-h4">Art Auction Items:</h3>
        </div>
    </div>
     <div class='row'>
        <div class='col-sm-1'>Item id</div>
        <div class='col-sm-4'>Title</div>
        <div class='col-sm-3'>Artist</div>
        <div class='col-sm-1'>Type</div>
        <div class='col-sm-2'  style="text-align: right;">Amount</div>
    </div>
EOS;
                    $receipt_tables .= <<<EOS
<tr><td colspan="3"><h3 class="size-h4">Art Auction Items:</h3></td></tr>
<tr><td>Item Id</td><td>Title/Artist</td><td>Type/Amount</td></tr>
EOS;
                    $needHeader = false;
                }
                // now the actual row
                $itemId = $art['exhibitorNumber'] . '-' . $art['item_key'];
                $title = $art['title'];
                $artist = $art['artist'];
                $type = $art['status'] = 'Quicksale/Sold' ? 'QS' : 'BID';
                $price = $art['amount'];
                $artSubtotal += $price;
                $pricefmt = $dolfmt->formatCurrency((float)$price, $currency);
                $receipt .= "$itemId    $title/$artist    $type/$pricefmt\n";
                $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'>$itemId</div>
        <div class='col-sm-4'>$title</div>
        <div class='col-sm-3'>$artist</div>
        <div class='col-sm-1'>$type</div>
        <div class='col-sm-2' style="text-align: right;">$pricefmt</div>
    </div>
EOS;
                $receipt_tables .= <<<EOS
<tr><td>$itemId</td><td>$title/$artist</td><td>$type/$pricefmt</td></tr>
EOS;
            }
        }
        if ($artSubtotal > 0) {
            $subtotal = $dolfmt->formatCurrency((float)$artSubtotal, $currency);
            $receipt .= "Art Subtotal: $subtotal\n";
            $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'></div>
        <div class='col-sm-8'>Art Subtotal</div>
        <div class='col-sm-2' style="text-align: right;">$subtotal</div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td></td><td>Space Subtotal:</td><td>$subtotal</td></tr>
EOS;
        }
    }
    $total = $memberSubtotal + $spaceSubtotal + $artSubtotal;
    // now the total due
    $price = $dolfmt->formatCurrency((float)$total, $currency);
    $receipt .= "\nTotal Due: $price\n";
    $receipt_html .= <<<EOS
    <div class="row mt-2">
        <div class="col-sm-9">Total Due:</div>
        <div class="col-sm-2" style="text-align: right;">$price</div>
    </div>
EOS;
    $receipt_tables .= <<<EOS
<tr><td colspan="2">Total Due</td><td>$price</td></tr>
EOS;

    // now for the payments/coupon section
/*
    // if payments > 0, then output payments header
    if (count($data['payments']) > 0) {
        $receipt .= "\nPayments:\nType, Description/Code, Amount\n";
        $receipt_html .= <<<EOS
    <div class='row mt-2'>
        <div class='col-sm-12'>
            <h3>Payments:</h3>
        </div>
    </div>
    <div class='row mt-1'>
        <div class='col-sm-1'>Type</div>
        <div class="col-sm-6">Description/Code</div>
        <div class="col-sm-2" style="text-align: right;">Amount</div>
    </div>
EOS;
    }
    $receipt_tables .= <<<EOS
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><h3>Payments:</h3></td></tr>
<tr><td>Type</td><td>Description/Code</td><td>Amount</td></tr>
EOS;

    $payment_total = 0;
    // if only a coupon and no payments
    if ( count($data['coupons']) > 0) {
        $coupons = $data['coupons'];
        $plural = count($coupons) > 1 ? 's' : '';
        if (count($data['payments']) <= 0) {
            $receipt .= "\nCoupon$plural Applied:\n";
            $receipt_html .= <<<EOS
    <div class='row mt-2'>
        <div class='col-sm-12'>
            <h3>Coupon$plural Applied:</h3>
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><h3>Coupon$plural Applied:</h3></td></tr>
EOS;

        }
        foreach ($coupons as $coupon) {
            $name = $coupon['name'];
            $code = $coupon['code'];
            $id = $coupon['id'];
            $discount =  sum_coupon_discount($id, $data['memberships']);
            $payment_total += $discount;
            $discount = $dolfmt->formatCurrency((float) $discount, $currency);
            $receipt .= "Coupon: $name ($code): $discount\n";
            $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'>Coupon</div>
        <div class="col-sm-6">$name ($code)</div>
        <div class="col-sm-2" style="text-align: right;">$discount</div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td>Coupon</td><td>$name ($code)</td><td>$discount</td></tr>
EOS;
        }
    }

    // now loop over the payments
    foreach ($data['payments'] as $pmt) {
        $type = $pmt['type'];
        $desc = $pmt['description'];
        $amt = $pmt['amount'];
        $cc = $pmt['cc'];
        if ($cc === null)
            $cc = "";
        else
            $cc = mb_substr($cc, -4);
        $aprvl = $pmt['cc_approval_code'];
        if ($aprvl === null)
            $aprvl = '';
        else
            $aprcl = trim($aprvl);
        $url = $pmt['receipt_url'];

        $payment_total += $amt;
        $amt = $dolfmt->formatCurrency((float)$amt, $currency);

        if ($aprvl != '' && $cc != '')
            $aprvl = " (last 4: $cc, auth: $aprvl)";
        else if ($cc != '')
            $aprvl = ", last4: $cc";
        else if ($aprvl != '')
            $aprvl = " (auth: $aprvl)";

        $url = $pmt['receipt_url'];
        $receipt .= "$type, $desc$aprvl, $amt\n";
        $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'>$type</div>
        <div class="col-sm-6">$desc$aprvl</div>
        <div class="col-sm-2" style="text-align: right;">$amt</div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td>$type</td><td>$desc$aprvl</td><td>$amt</td></tr>
EOS;

        if ($url != null && $url != '') {
            $receipt .= "     $url\n";
            $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'></div>
        <div class="col-sm-auto">$url</div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td>&nbsp;</td><td colspan="2">$url</td></tr>
EOS;
        }
    }

    if ($payment_total > 0) {
        $payment_total = $dolfmt->formatCurrency((float) $payment_total, $currency);
        $receipt .= "\nTotal Payments: $payment_total\n";
        $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-7'>Total Payments</div>
        <div class="col-sm-2" style="text-align: right;">$payment_total</div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td colspan="2">Total Payments</td><td>$payment_total</td></tr>
EOS;
    }
*/
    // now for the disclaimers at the bottom
    // general disclaimer for all reg items
    // Needs to be added

    // exhibitor disclaimer
    if (count($data['spaces']) > 0) {
        loadCustomText('exhibitor', 'index', null, true);
        $portalName = ucfirst($data['spaces'][0]['portalType']);
        $disclaimer1 = returnCustomText('invoice/payDisclaimer', 'exhibitor/index/');
        $disclaimer2 = returnCustomText('invoice/payDisclaimer' . $portalName,'exhibitor/index/');
        if ($disclaimer1 != '' || $disclaimer2 != '') {
            $textDisclaimer = $disclaimer1;
            $htmlDisclaimer = $disclaimer1;
            if ($disclaimer1 != '' && $disclaimer2 != '') {
                $textDisclaimer .= PHP_EOL;
                $htmlDisclaimer .= "<br/>\n";
            }
            $textDisclaimer .= $disclaimer2;
            $htmlDisclaimer .= $disclaimer2;
            $receipt .= "\n\n$textDisclaimer\n";
            $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
           $htmlDisclaimer
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3"><p>$textDisclaimer</p></td></tr>
EOS;
        }
    }

    $endtext = getConfValue('con', 'endtext', '');
    if ($endtext != '') {
        $receipt .=  "\n\n$endtext\n";
        $receipt_html .= <<<EOS
<div class='row mt-4'>
        <div class='col-sm-12'>
            <p>$endtext</p>
        </div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td colspan="3"><p>$endtext</p></td></tr>
EOS;
    }

    // all done now
    $response['receipt'] = $receipt;
    $response['receipt_html'] = $receipt_html;
    $response['receipt_tables'] = $receipt_tables . "</table>\n";
    return $response;
}

// loop over all the regs and sum to total usage of a coupon id
function sum_coupon_discount($id, $memberships) {
    $discount = 0;
    foreach ($memberships as $pid => $list)  {
        foreach ($list as $item) {
            if ($item['coupon'] == $id)
                $discount += $item['couponDiscount'];
        }
    }
    return $discount;
}

// format a member block for the receipt
function reg_format_mbr($dolfmt, $currency, $membership, &$curNameLine, &$receipt, &$receipt_html, &$receipt_tables) {
    // see if we need a header:
    $nameLine = $membership['fullName'] . ' (' . strip_tags(str_replace('<br/>', '/', $membership['badgename'])) . ')';
    if ($curNameLine != $nameLine) {
        $age = $membership['memAge'];
        $receipt .= "\nMember: $nameLine, $age\n";
        $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h3 class="size-h4">Member $nameLine, $age</h3>
        </div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td colspan="3"><h3 class="size-h4">Member $nameLine, $age</h3></td></tr>
EOS;
        $curNameLine = $nameLine;
    }
    $id = $membership['id'];
    $label = $membership['label'];
    $price = $membership['price'];
    $pricefmt = $dolfmt->formatCurrency((float) $price, $currency);
    $receipt .= "$id, $label: $price\n";
    $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-1">$id</div>
        <div class="col-sm-8">$label</div>
        <div class="col-sm-2" style="text-align: right;">$pricefmt</div>
    </div>
EOS;
    $receipt_tables .= <<<EOS
<tr><td>$id</td><td>$label</td><td>$pricefmt</td></tr>
EOS;
    return $price;
}
