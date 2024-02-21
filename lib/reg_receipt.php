<?php
//  receipt.php - library of modules related building registration receipts

// trans_receipt - given a transaction number build a receipt
// This function returns all the data to make up a receipt and then calls 'reg_format_receipt' to actually format the receipt as plain text, HTML and email tables.
function trans_receipt($transid, $exhibitor=null, $spaces=null, $region=null)
{
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

    //// get memberships referenced by this transaction
    ///     can be directly via create_trans or complete_trans (memPeople)
    ///     or indirectly via people referenced in this transaction and their reg transactions

    $withTrans = <<<EOS
WITH directReg AS (
    SELECT DISTINCT perid, newperid
    FROM reg
    WHERE conid = ? AND (create_trans = ? OR complete_trans = ?)
), indirectReg AS (
    SELECT DISTINCT r.id
    FROM reg r
    JOIN directReg dr ON (dr.perid = r.perid OR dr.newperid = r.newperid)
    WHERE r.conid = ?
), transReg AS (
    SELECT DISTINCT id
    FROM indirectReg
), allTrans AS (
    SELECT DISTINCT create_trans AS transid
    FROM reg r
    JOIN transReg tr ON (tr.id = r.id)
    WHERE r.conid = ?
    UNION SELECT DISTINCT complete_trans AS transid
    FROM reg r
    JOIN transReg tr ON (tr.id = r.id)
    WHERE r.conid = ?
)
EOS;

    $memSQL = <<<EOS
$withTrans, allReg AS (
    SELECT DISTINCT r.id
    FROM reg r
    JOIN allTrans t ON (r.create_trans = t.transid OR r.complete_trans = t.transid)
    WHERE r.conid = ?
)
SELECT CASE WHEN r.perid IS NOT NULL THEN CONCAT('p-', r.perid) ELSE CONCAT('n-', r.newperid) END AS pid, r.*,
    m.label, m.shortname, m.memCategory, m.memType, m.memAge, m.price AS fullprice
FROM reg r
JOIN allReg al ON (r.id = al.id)
JOIN memLabel m ON (r.memId = m.id)
ORDER BY 1,2
EOS;

    $memR = dbSafeQuery($memSQL, 'iiiiiii', array($conid, $transid, $transid, $conid, $conid, $conid, $conid));
    $memberships = [];
    while ($memL = $memR->fetch_assoc()) {
        $memberships[$memL['pid']][] = $memL;
    }
    $response['memberships'] = $memberships;

    //// now all the people mentioned in those memberships
    $peopleSQL = <<<EOS
$withTrans, allReg AS (
    SELECT DISTINCT r.id, r.perid, r.newperid
    FROM reg r
    JOIN memLabel m ON (r.memId = m.id)
    JOIN allTrans t ON (r.create_trans = t.transid OR r.complete_trans = t.transid)
    WHERE r.conid = ?
)
SELECT DISTINCT
    CASE WHEN r.perid IS NOT NULL THEN CONCAT('p-', p.id) ELSE CONCAT('n-', n.id) END AS pid,
    r.perid, r.newperid,
    CASE WHEN r.perid IS NOT NULL THEN p.first_name ELSE n.first_name END AS first_name,
    CASE WHEN r.perid IS NOT NULL THEN p.last_name ELSE n.last_name END AS last_name,
    CASE WHEN r.perid IS NOT NULL THEN p.middle_name ELSE n.middle_name END AS middle_name,
    CASE WHEN r.perid IS NOT NULL THEN p.suffix ELSE n.suffix END AS suffix,
    CASE WHEN r.perid IS NOT NULL THEN p.badge_name ELSE n.badge_name END AS badge_name,
    CASE WHEN r.perid IS NOT NULL THEN p.email_addr ELSE n.email_addr END AS email_addr
FROM allReg r
LEFT OUTER JOIN perinfo p ON (r.perid = p.id)
LEFT OUTER JOIN newperson n ON (r.newperid = n.id)
ORDER BY 1
EOS;
    $peopleR = dbSafeQuery($peopleSQL, 'iiiiiii', array($conid, $transid, $transid, $conid, $conid, $conid, $conid));
    $people = [];
    while ($peopleL = $peopleR->fetch_assoc()) {
        $people[$peopleL['pid']] = $peopleL;
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
$withTrans
SELECT p.*
FROM payments p
JOIN allTrans t ON (p.transid = t.transid)
ORDER BY id;
EOS;
        $payR = dbSafeQuery($paySQL, 'iiiiii', array($conid, $transid, $transid, $conid, $conid, $conid));
    }
    $payments = [];
    while ($payL = $payR->fetch_assoc()) {
        $payments[] = $payL;
    }
    $response['payments'] = $payments;

    //// next, get all coupons used
    $couponSQL = <<<EOS
    $withTrans
    SELECT DISTINCT c.*
    FROM allTrans at
    JOIN transaction t ON (t.id = at.transid)
    JOIN coupon c ON (t.coupon = c.id)
    WHERE t.coupon IS NOT NULL
    ORDER BY id;
EOS;

    $couponR = dbSafeQuery($couponSQL, 'iiiiii', array($conid, $transid, $transid, $conid, $conid, $conid));
    $coupons = [];
    while ($couponL = $couponR->fetch_assoc()) {
        $coupons[] = $couponL;
    }
    $response['coupons'] = $coupons;

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
    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);
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
        $list = $data['memberships'][$payor_pid];
        $subtotal = reg_format_mbr($data, $data['people'][$payor_pid], $list, $receipt, $receipt_html, $receipt_tables);
        $total += $subtotal;
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
/* need to redo this part for the exhibitor spaces
        foreach ($data['exhibitors'] as $exhibitor) {
            $exhibitor_price = $exhibitor['price'];
            $total += $exhibitor_price;
            $exhibitor_price = $dolfmt->formatCurrency((float) $exhibitor['price'], 'USD');
            $exhibitor_sid = $exhibitor['id'];
            $exhibitor_area = $exhibitor['space_name'];
            $exhibitor_desc = $exhibitor['description'];
            $exhibitor_name = $exhibitor['exhibitor_name'];
            $receipt .= "$exhibitor_area, $exhibitor_desc, $exhibitor_name, $exhibitor_price\n";
            $receipt_html .= <<<EOS
    <div class="row">
        <div class="col-sm-1">$exhibitor_sid</div>
        <div class="col-sm-6">$exhibitor_desc in $exhibitor_area for $exhibitor_name</div>
        <div class="col-sm-2">$exhibitor_price</div>
    </div>
EOS;
            $receipt_tables .= <<<EOS
<tr><td>$exhibitor_sid</td><td>$exhibitor_desc in $exhibitor_area for $exhibitor_name</td><td>$exhibitor_price</td></tr>
EOS;

        }
*/
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
    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);
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
