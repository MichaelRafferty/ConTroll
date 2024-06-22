<?php
//  receipt.php - library of modules related building registration receipts

// trans_receipt - given a transaction number build a receipt
// This function returns all the data to make up a receipt and then calls 'reg_format_receipt' to actually format the receipt as plain text, HTML and email tables.
function trans_receipt($transid, $exhId = null, $regionYearId=null)
{
    $emails = [];
    $exhibitor = null;
    if ($transid == null) {

// get the transaction for this regionid
// now the space information for this regionYearId
        $spaceQ = <<<EOS
SELECT e.*, esp.includedMemberships, esp.additionalMemberships
FROM vw_ExhibitorSpace e
JOIN exhibitsSpaces s ON (s.id = e.spaceId)
JOIN exhibitsSpacePrices esp ON (s.id = esp.spaceId AND e.item_approved = esp.id)
JOIN exhibitsRegionYears ery ON (ery.id = s.exhibitsRegionYear)
JOIN exhibitsRegions er ON (ery.exhibitsRegion = er.id)
WHERE ery.id = ? and e.exhibitorId = ?;
EOS;
        $spaceR = dbSafeQuery($spaceQ, 'ii', array($regionYearId, $exhId));
        if ($spaceR == false || $spaceR->num_rows == 0) {
            $response['error'] = 'Unable to find any space for the receipt';
            ajaxSuccess($response);
            return;
        }

        $spaces = [];
        while ($space = $spaceR->fetch_assoc()) {
            if ($transid == null)
                $transid = $space['transid'];
            if ($exhId == null)
                $exhId = $space['exhibitorId'];
            $spaces[$space['spaceId']] = $space;
        }
        $spaceR->free();

// get the specific information allowed
        $regionYearQ = <<<EOS
SELECT er.id, name, description, ownerName, ownerEmail, includedMemId, additionalMemId, mi.price AS includedPrice, ma.price AS additionalPrice, ery.mailinFee
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON er.id = ery.exhibitsRegion
LEFT OUTER JOIN memList mi ON ery.includedMemId = mi.id
LEFT OUTER JOIN memList ma ON ery.additionalMemId = ma.id
WHERE ery.id = ?;
EOS;
        $regionYearR = dbSafeQuery($regionYearQ, 'i', array($regionYearId));
        if ($regionYearR == false || $regionYearR->num_rows != 1) {
            $response['error'] = 'Unable to find region record, get help';
            ajaxSuccess($response);
            return;
        }
        $region = $regionYearR->fetch_assoc();
        $regionYearR->free();
    } else { // check to see if there is an exhibitor space with this transid
        // get the transaction for this regionid
        // now the space information for this regionYearId
        $spaceQ = <<<EOS
SELECT e.*, esp.includedMemberships, esp.additionalMemberships, ery.id AS regionYearId
FROM vw_ExhibitorSpace e
JOIN exhibitsSpaces s ON (s.id = e.spaceId)
JOIN exhibitsSpacePrices esp ON (s.id = esp.spaceId AND e.item_approved = esp.id)
JOIN exhibitsRegionYears ery ON (ery.id = s.exhibitsRegionYear)
JOIN exhibitsRegions er ON (ery.exhibitsRegion = er.id)
WHERE e.transid = ?;
EOS;
        $spaceR = dbSafeQuery($spaceQ, 'i', array($transid));
        $spaces = [];
        while ($space = $spaceR->fetch_assoc()) {
            if ($regionYearId == null)
                $regionYearId = $space['regionYearId'];
            $spaces[$space['spaceId']] = $space;
        }
        $spaceR->free();

        // now fetch the region info
        if ($regionYearId != null) {
            $regionYearQ = <<<EOS
SELECT er.id, name, description, ownerName, ownerEmail, includedMemId, additionalMemId, mi.price AS includedPrice, ma.price AS additionalPrice, ery.mailinFee
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON er.id = ery.exhibitsRegion
LEFT OUTER JOIN memList mi ON ery.includedMemId = mi.id
LEFT OUTER JOIN memList ma ON ery.additionalMemId = ma.id
WHERE ery.id = ?;
EOS;
            $regionYearR = dbSafeQuery($regionYearQ, 'i', array($regionYearId));
            if ($regionYearR == false || $regionYearR->num_rows != 1) {
                $response['error'] = 'Unable to find region record, get help';
                ajaxSuccess($response);
                return;
            }
            $region = $regionYearR->fetch_assoc();
            $regionYearR->free();
        } else {
            $region = null;
        }
    }

    if ($exhId != null) {
        // get current exhibitor information
        $exhibitorQ = <<<EOS
SELECT e.id, exhibitorName, exhibitorEmail, exhibitorPhone, website, description, addr, addr2, city, state, zip, country, 
       contactEmail, contactName, contactPhone, ey.mailin, exRY.exhibitorNumber
FROM exhibitors e
JOIN exhibitorYears ey ON e.id = ey.exhibitorId
JOIN exhibitorRegionYears exRY ON ey.id = exRY.exhibitorYearId
JOIN exhibitsRegionYears ery ON exRY.exhibitsRegionYearId = ery.id
WHERE e.id=? AND ery.id = ?;
EOS;
        $exhibitorR = dbSafeQuery($exhibitorQ, 'ii', array($exhId, $regionYearId));
        if ($exhibitorR == false || $exhibitorR->num_rows != 1) {
            $response['error'] = 'Unable to find your exhibitor record';
            ajaxSuccess($response);
            return;
        }
        $exhibitor = $exhibitorR->fetch_assoc();
        $exhibitorR->free();
        if ($exhibitor['exhibitorEmail']) {
            if (!in_array($exhibitor['exhibitorEmail'], $emails))
                $emails[] = $exhibitor['exhibitorEmail'];
        }
        if ($exhibitor['contactEmail']) {
            if (!in_array($exhibitor['contactEmail'], $emails))
                $emails[] = $exhibitor['contactEmail'];
        }
    }

    //// get the transaction information
    $transQ = <<<EOS
SELECT id, conid, perid, newperid, userid, create_date, DATE_FORMAT(create_date, '%W %M %e, %Y %h:%i:%s %p') as create_date_str,
       complete_date, DATE_FORMAT(complete_date, '%W %M %e, %Y %h:%i:%s %p') as complete_date_str,
       price, couponDiscount, paid, withtax, tax, type, notes, change_due, coupon, notes
FROM transaction
WHERE id = ?;
EOS;

    $transR = dbSafeQuery($transQ, 'i', array($transid));
    if ($transR === false || $transR->num_rows != 1) {
        RenderErrorAjax('Transaction not found');
        exit();
    }

    $transL = $transR->fetch_assoc();
    $conid = $transL['conid'];
    $userid = $transL['userid'];
    $type = $transL['type'];

    $response = [];
    $response['transid'] = $transid;
    $response['type'] = $type;
    $response['userid'] = $userid;
    $response['transaction'] = $transL;

    $payorL = [];
    //// get the payor information involved in this transaction
    if ($exhibitor != null) {
        $payorL = [ 'tablename' => 'exhibitor', 'id' => $exhibitor['id'], 'pid' => 'e-' . $exhibitor['id'], 'last_name' => $exhibitor['exhibitorName'],
            'first_name' => '', 'middle_name' => '', 'suffix' => '', 'email_addr' => $exhibitor['exhibitorEmail'], 'phone' => $exhibitor['exhibitorPhone'],
            'badge_name' => $exhibitor['contactName'], 'address' => $exhibitor['addr'], 'addr_2' => $exhibitor['addr2'], 'city' => $exhibitor['city'],
            'state' => $exhibitor['state'], 'zip' => $exhibitor['state'], 'country' => $exhibitor['country'] ];
        $payor = null;
    } else  if ($transL['perid'] > 0) {
        $payorSQL = <<<EOS
SELECT 'perinfo' AS tablename, id, CONCAT('p-', id) AS pid, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, address, addr_2, city, state, zip, state, country
FROM perinfo WHERE id = ?;
EOS;
        $payor = $transL['perid'];
    } else if ($transL['newperid']) {
        $payorSQL = <<<EOS
SELECT 'newperson' AS tablename, id, CONCAT('n-', id) AS pid, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, address, addr_2, city, state, zip, state, country
FROM newperson WHERE id = ?;
EOS;
        $payor = $transL['newperid'];
    } else {
        $payor = null;
    }
    if ($payor) {
        $payorR = dbSafeQuery($payorSQL, 'i', array($payor));
        $payorL = $payorR->fetch_assoc();
    }

    $response['payor'] = $payorL;
    $memSQL = <<<EOS
SELECT CASE WHEN r.perid IS NOT NULL THEN CONCAT('p-', r.perid) ELSE CONCAT('n-', r.newperid) END AS pid, r.*,
    m.label, m.shortname, m.memCategory, m.memType, m.memAge, m.price AS fullprice
FROM reg r
JOIN memLabel m ON (r.memId = m.id)
WHERE r.create_trans = ? OR r.complete_trans = ?
ORDER BY 1,2;
EOS;

    $memR = dbSafeQuery($memSQL, 'ii', array($transid, $transid));
    $memberships = [];
    while ($memL = $memR->fetch_assoc()) {
        $memberships[$memL['pid']][] = $memL;
    }
    $response['memberships'] = $memberships;

    //// now all the people mentioned in those memberships
    $peopleSQL = <<<EOS
SELECT DISTINCT CASE WHEN r.perid IS NOT NULL THEN CONCAT('p-', p.id) ELSE CONCAT('n-', n.id) END AS pid,
    r.perid, r.newperid,
    CASE WHEN r.perid IS NOT NULL THEN p.first_name ELSE n.first_name END AS first_name,
    CASE WHEN r.perid IS NOT NULL THEN p.last_name ELSE n.last_name END AS last_name,
    CASE WHEN r.perid IS NOT NULL THEN p.middle_name ELSE n.middle_name END AS middle_name,
    CASE WHEN r.perid IS NOT NULL THEN p.suffix ELSE n.suffix END AS suffix,
    CASE WHEN r.perid IS NOT NULL THEN p.badge_name ELSE n.badge_name END AS badge_name,
    CASE WHEN r.perid IS NOT NULL THEN p.email_addr ELSE n.email_addr END AS email_addr
FROM reg r
LEFT OUTER JOIN perinfo p ON (r.perid = p.id)
LEFT OUTER JOIN newperson n ON (r.newperid = n.id)
WHERE r.create_trans = ? OR r.complete_trans = ?
ORDER BY 1
EOS;
    $peopleR = dbSafeQuery($peopleSQL, 'ii', array($transid, $transid));
    $people = [];
    $people[$payorL['pid']] = $payorL;
    while ($peopleL = $peopleR->fetch_assoc()) {
        $people[$peopleL['pid']] = $peopleL;
        if (!in_array($peopleL['email_addr'], $emails))
            $emails[] = $peopleL['email_addr'];
    }
    $response['people'] = $people;

    // now get all payments
    if ($exhibitor != null) {
        $paySQL = <<<EOS
SELECT p.*
FROM payments p
WHERE transid = ?
ORDER BY id;
EOS;
        $payR = dbSafeQuery($paySQL, 'i', array($transid));
    } else {
        $paySQL = <<<EOS
SELECT DISTINCT p.*
FROM reg r
JOIN payments p ON (r.create_trans = p.transid OR r.complete_trans = p.transid)
WHERE r.create_trans = ? OR r.complete_trans = ?
ORDER BY id;
EOS;
        $payR = dbSafeQuery($paySQL, 'ii', array($transid, $transid));
    }
    $payments = [];
    while ($payL = $payR->fetch_assoc()) {
        $payments[] = $payL;
    }
    $response['payments'] = $payments;

    // next, get all coupons used
    $couponSQL = <<<EOS
    SELECT DISTINCT c.*
    FROM reg r
    JOIN transaction t ON (t.id = r.id)
    JOIN coupon c ON (t.coupon = c.id)
    WHERE r.create_trans = ? OR r.complete_trans = ? AND t.coupon IS NOT NULL 
    ORDER BY id;
EOS;

    $couponR = dbSafeQuery($couponSQL, 'ii', array($transid, $transid, ));
    $coupons = [];
    while ($couponL = $couponR->fetch_assoc()) {
        $coupons[] = $couponL;
    }
    $response['coupons'] = $coupons;
    if (count($emails) > 0)
        $response['emails'] = $emails;

    if ($exhibitor)
        $response['exhibitor'] = $exhibitor;

    if ($spaces)
        $response['spaces'] = $spaces;

    if ($region)
        $response['region'] = $region;

    return reg_format_receipt($response);
}

// reg_format_receipt - format a receipt in HTML and Text formats
function reg_format_receipt($data) {
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);
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
    $title_payor_name = 'unknown';
    $title_email = '';
    // Receipt Title:
    $receipt = "Receipt for payment to $conlabel\n$receipt_date\n";
    $receipt_html = <<<EOS
<div class="container-fluid border border-primary border-4">
    <div class="row">
        <div class="col-sm-12">
            <h2>Receipt for payment to $conlabel</h2>
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
<tr><td colspan="3"><h2>Receipt for payment to $conlabel</h2></td></tr>
<tr><td colspan="3">$receipt_date</td></tr>
EOS;

    // Payor Info
    $type = $data['type'];
    $payor = $data['payor'];
    $payor_name = $payor['first_name'];
    if (mb_strlen($payor['middle_name']) > 0)
        $payor_name .= ' ' . $payor['middle_name'];
    if (mb_strlen($payor['last_name']) > 0)
        $payor_name .= ' ' . $payor['last_name'];
    if (mb_strlen($payor['suffix']) > 0)
        $payor_name .= ', ' . $payor['suffix'];
    $payor_name = trim($payor_name);
    $master_tid = $master_transaction['id'];
    if (array_key_exists('exhibitor', $data)) {
        $title_payor_name = $data['exhibitor']['exhibitorName'];
        $title_email = $data['exhibitor']['exhibitorEmail'];
    } else {
        $title_payor_name = $payor_name;
        $title_email = $payor['email_addr'];
    }

    $response['payor_name'] = $title_payor_name;
    $response['payor_email'] = $title_email;

    switch ($type) {
        case 'website':
            $receipt .= "By: $title_payor_name, Via: Online Registration Website, Transaction: $master_tid\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-12">
            By: $payor_name, Via: Online Registration Website, Transaction: $master_tid
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">By: $payor_name, Via: Online Registration Website, Transaction: $master_tid</td></tr>
EOS;

            break;
        case 'vendor':
        case 'artist':
        case 'exhibitor':
            $receipt .= "By: $title_payor_name, Via: $type portal, Transaction: $master_tid\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-12">
            By: $title_payor_name, Via: $type portal, Transaction: $master_tid
        </div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">By: $title_payor_name, Via: $type portal, Transaction: $master_tid</td></tr>
EOS;

            break;
        case 'atcon':
            $cashier = $master_transaction['userid'];
            $receipt .= "By: $title_payor_name, Via: On-Site Registration, Cashier: $cashier, Transaction: $master_tid\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-12">
            By: $title_payor_name, Via: On-Site Registration Cashier: $cashier, Transaction: $master_tid
        </div>
    </div>
EOS;
            break;
        default: // reg_control receipts (registration, badgelist, people, etc.)
            $cashier = $master_transaction['userid'];
            $receipt .= "By: $title_payor_name, Via: Registration Staff Member: $cashier, Transaction: $master_tid\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-12">
            By: $title_payor_name, Via: Registration Staff Member: $cashier, Transaction: $master_tid
        </div>
    </div>
 EOS;
            $receipt_tables .= <<<EOS
<tr><td colspan="3">By: $title_payor_name, Via: Registration Staff Member: $cashier, Transaction: $master_tid</td></tr>
EOS;
            break;
    }

    $receipt .= "\nMemberships:\n";
    $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h3>Memberships:</h3>
        </div>
    </div>
EOS;
    $receipt_tables .= <<<EOS
<tr><td colspan="3">&nbsp;</td> </tr>
<tr><td colspan="3"><h3>Memberships:</h3></td></tr>
EOS;

    // first output the payor
    $total = 0;
    $payor_pid = $payor['pid'];
    if (substr($payor_pid, 0, 1) != 'e') {
        foreach ($data['memberships'] as $pid => $list) {
            if ($payor_pid == $pid) {
                $list = $data['memberships'][$payor_pid];
                $subtotal = reg_format_mbr($data, $data['people'][$payor_pid], $list, $receipt, $receipt_html, $receipt_tables);
                $total += $subtotal;
            }
        }
    }

    // now all but the payor
    foreach ($data['memberships'] as $pid => $list) {
        if ($payor_pid == $pid)
        continue;

        $subtotal = reg_format_mbr($data, $data['people'][$pid], $list, $receipt, $receipt_html, $receipt_tables);
        $total += $subtotal;
    }

    // now exhibitor spaces if they exist
    if (array_key_exists('exhibitor', $data)) {
        $receipt .= "\nExhibitor Spaces:\n";
        $receipt_html .= <<<EOS
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h3>Exhibitor Spaces:</h3>
        </div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td colspan="3"><h3>Exhibitor Spaces:</h3></td></tr>
EOS;
        $exhibitor = $data['exhibitor'];
        $region = $data['region'];
        $exhibitor_sid = $exhibitor['id'];
        $exhibitor_number = $exhibitor['exhibitorNumber'];
        if ($exhibitor_number != null)
            $exhibitor_sid = "$exhibitor_number ($exhibitor_sid)";
        $exhibitor_name = $exhibitor['exhibitorName'];
        $regionName = $region['name'];
        $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-1">$exhibitor_sid</div>
        <div class="col-sm-6"> $regionName for $exhibitor_name</div>
    </div>
EOS;
        $receipt_tables .= "<tr><td>$exhibitor_sid</td><td>$regionName for $exhibitor_name</td><td></td></tr>\n";
        $receipt .= "$exhibitor_sid: $regionName for $exhibitor_name\n";

        foreach ($data['spaces'] as $space) {
            $spaceDesc = $space['purchased_description'];
            $spaceName = $space['name'];
            $total += $space['purchased_price'];
            $spacePrice = $dolfmt->formatCurrency((float) $space['purchased_price'], 'USD');
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-1"></div>
        <div class="col-sm-6">$spaceDesc in $spaceName</div>
        <div class="col-sm-2">$spacePrice</div>
    </div>
EOS;
            $receipt_tables .= "<tr><td></td><td>$spaceDesc in $spaceName</td><td>$spacePrice</td></tr>\n";
            $receipt .= "     $spaceDesc in $spaceName: $spacePrice\n";
        }

        if ($region['mailinFee'] > 0 && $exhibitor['mailin'] == 'Y') {
            $total += $region['mailinFee'];
            $fee = $dolfmt->formatCurrency((float) $region['mailinFee'], 'USD');
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-1"></div>
        <div class="col-sm-6">Mail In Fee</div>
        <div class="col-sm-2">$fee</div>
    </div>
EOS;
            $receipt_tables .= "<tr><td></td><td>Mail In Fee</td><td>$fee</td></tr>\n";
            $receipt .= "     Mail In Fee: $fee\n";
        }
    }

    // now the total due
    $price = $dolfmt->formatCurrency((float) $total, 'USD');
    $receipt .= "\nTotal Due:: $price\n";
    $receipt_html .= <<<EOS
    <div class="row mt-2">
        <div class="col-sm-7">Total Due:</div>
        <div class="col-sm-2">$price</div>
    </div>
EOS;
    $receipt_tables .= <<<EOS
<tr><td colspan="2">Total Due</td><td>$price</td></tr>
EOS;

    // now for the payments/coupon section

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
        <div class="col-sm-2">Amount</div>
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
            $discount = $dolfmt->formatCurrency((float) $discount, 'USD');
            $receipt .= "Coupon: $name ($code): $discount\n";
            $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'>Coupon</div>
        <div class="col-sm-6">$name ($code)</div>
        <div class="col-sm-2">$discount</div>
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
        $amt = $dolfmt->formatCurrency((float)$amt, 'USD');

        if ($aprvl != '' && $cc != '')
            $aprvl = " (last 4: $cc, auth: $aprvl)";
        else if ($cc != '')
            $aprvl = ", last4: $cc";
        else
            $aprvl = " (auth: $aprvl)";

        $url = $pmt['receipt_url'];
        $receipt .= "$type, $desc$aprvl, $amt\n";
        $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-1'>$type</div>
        <div class="col-sm-6">$desc$aprvl</div>
        <div class="col-sm-2">$amt</div>
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
        $payment_total = $dolfmt->formatCurrency((float) $payment_total, 'USD');
        $receipt .= "\nTotal Payments: $payment_total\n";
        $receipt_html .= <<<EOS
    <div class='row'>
        <div class='col-sm-7'>Total Payments</div>
        <div class="col-sm-2">$payment_total</div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td colspan="2">Total Payments</td><td>$payment_total</td></tr>
EOS;
    }

    // now for the disclaimers at the bottom
    // general disclaimer for all reg items
    // Needs to be added

    // exhibitor disclaimer
    if (array_key_exists('exhibitor', $data)) {
        $vc = get_conf('vendor');
        if (array_key_exists('pay_disclaimer', $vc)) {
            $vdisc = $vc['pay_disclaimer'];
            if ($vdisc != '') {
                $path = "../config/$vdisc";
                if (!file_exists($path))
                    $path = "../" . $path;
                if (!file_exists($path))
                    $path = '../' . $path;
                if (file_exists($path)) {
                    $vdisc = file_get_contents($path);
                    if ($vdisc) {
                        $receipt .= "\n\n$vdisc\n";
                        $receipt_html .= <<<EOS
<div class='row mt-4'>
        <div class='col-sm-12'>
            <p>$vdisc</p>
        </div>
    </div>
EOS;
                        $receipt_tables .= <<<EOS
<tr><td colspan="3"><p>$vdisc</p></td></tr>
EOS;
                    }
                }
            }
        }
    }

    $coninfo = get_conf('con');
    if (array_key_exists('endtext', $coninfo)) {
        $endtext = $coninfo['endtext'];
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
function reg_format_mbr($data, $person, $list, &$receipt, &$receipt_html, &$receipt_tables) {
    $curLocale = locale_get_default();
    $dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);
    // first the name:
    $name = trim($person['first_name']);
    if (mb_strlen($person['middle_name']) > 0)
        $name .= ' ' . trim($person['middle_name']);
    if (mb_strlen($person['last_name']) > 0)
        $name .= ' ' . trim($person['last_name']);
    if (mb_strlen($person['suffix']) > 0)
        $name .= ', ' . trim($person['suffix']);
    if (mb_strlen($person['badge_name']) > 0)
        $name .= ' (' . trim($person['badge_name']) . ')';
    $name = trim($name);

    $receipt .= "\nMember: $name\n";
    $receipt_html .= <<<EOS
    <div class='row mt-1'>
        <div class='col-sm-12'>
            <h4><strong>Member:</strong> $name</h4>
        </div>
    </div>
EOS;
    $receipt_tables .= <<<EOS
<tr><td colspan="3"><h4><strong>Member:</strong> $name</h4></td></tr>
EOS;

    $subtotal = 0;
    // loop over their memberships
    foreach ($list AS $row) {
        $price = $dolfmt->formatCurrency((float) $row['price'], 'USD');
        $label = $row['label'];
        $id = $row['id'];
        $receipt .= "$id, $label: $price\n";
        $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-1">$id</div>
        <div class="col-sm-6">$label</div>
        <div class="col-sm-2">$price</div>
    </div>
EOS;
        $receipt_tables .= <<<EOS
<tr><td>$id</td><td>$label</td><td>$price</td></tr>
EOS;

        $subtotal += $row['price'];
    }
    $price = $dolfmt->formatCurrency((float) $subtotal, 'USD');
    $receipt .= "     Subtotal: $price\n";
    $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-1"></div>
        <div class="col-sm-6">Subtotal</div>
        <div class="col-sm-2">$price</div>
    </div>
EOS;
    $receipt_tables .= <<<EOS
<tr><td>&nbsp;</td><td>Subtotal</td><td>$price</td></tr>
EOS;

    return $subtotal;
}
