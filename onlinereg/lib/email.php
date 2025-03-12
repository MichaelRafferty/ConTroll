<?php
require_once(__DIR__ . "/../../lib/db_functions.php");

function getEmailBody($transid, $totalDiscount) : string
{
    $condata = get_con();
    $ini = get_conf('reg');
    $con = get_conf('con');

    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }
    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

    if (array_key_exists('oneoff', $con)) {
        $oneoff = $con['oneoff'];
    } else {
        $oneoff = 0;
    }

    if ($oneoff != 1)
        $rollovers = "and rollovers to future conventions";
    else
        $rollovers = "";

    $ownerQ = <<<EOS
SELECT NP.first_name, NP.last_name, P.receipt_id as payid, T.complete_date, T.couponDiscountCart, T.paid, P.receipt_url AS url, C.code, C.name
FROM transaction T
JOIN newperson NP ON (NP.id=T.newperid)
JOIN payments P ON (P.transid=T.id)
LEFT OUTER JOIN coupon C ON (T.coupon = C.id)
WHERE T.id=?
;
EOS;
    $owner = dbSafeQuery($ownerQ, 'i', array($transid))->fetch_assoc();

    $body = trim($owner['first_name'] . " " . $owner['last_name']) . ",\n\n";
    $body .= "Thank you for registering for " . $condata['label'] . "!\n\n";

    if ($ini['test'] == 1) {
        $body .= "This email was sent as part of testing.\n\n";
    }

    $body .= "Your Transaction number is $transid and Receipt number is " . $owner['payid'] . "\n";

    if ($owner['code'] != null) {
        $body .= "A coupon of type " . $owner['code'] . " (" . $owner['name'] . ") was applied to this transaction";
        if ($totalDiscount > 0)
            $body .= " for a savings of " . $dolfmt->formatCurrency((float) $totalDiscount, $currency);
        $body .= "\n";
    }

    $body .= "Your card was charged " . $dolfmt->formatCurrency((float) $owner['paid'], $currency) . " for this transaction" .
        "\n\nMemberships have been created for:\n\n";

    $badgeQ = <<<EOS
SELECT NP.first_name, NP.last_name, M.label
FROM transaction T
JOIN reg R ON  (R.create_trans=T.id)
JOIN newperson NP ON (NP.id = R.newperid)
JOIN memLabel M ON (R.memID = M.id)
WHERE T.id= ?
EOS;

    $badgeR = dbSafeQuery($badgeQ, 'i', array($transid));

    while ($badge = $badgeR->fetch_assoc()) {
        $body .= "     * " . $badge['first_name'] . " " . $badge['last_name']
            . " (" . $badge['label'] . ")\n\n";
    }

    if ($owner['url'] != '') {
        $body .= "Your credit card receipt is available at " . $owner['url'] . "\n\n";
    } else {
        $body .= "You will receive a separate email with credit card receipt details.\n\n";
    }

    $body .= "Please contact " . $con['regemail'] . " with any questions and we look forward to seeing you at " . $condata['label'] . ".\n";

    $body .=
        "For hotel information and directions please see " . $con['hotelwebsite'] . "\n" .
        "Click " . $con['policy'] . " for the " . $con['policytext'] . ".\n" .
        "For more information about " . $con['conname'] . " please email " . $con['infoemail'] . ".\n" .
        "For questions about " . $con['conname'] . " Registration, email " . $con['regemail'] . ".\n" .
        $con['conname'] . " memberships are not refundable, except in case of emergency. For details and questions about transfers $rollovers, please see The Registration Policies Page.\n";

    return $body;
}

function getNoChargeEmailBody($results, $totalDiscount) : string
{
    $condata = get_con();
    $ini = get_conf('reg');
    $con = get_conf('con');

    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }
    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

    if (array_key_exists('oneoff', $con)) {
        $oneoff = $con['oneoff'];
    } else {
        $oneoff = 0;
    }

    if ($oneoff != 1)
        $rollovers = 'and rollovers to future conventions';
    else
        $rollovers = '';

    //  contents of results
    //  'transid' => $transid,
    //    'counts' => $counts,
    //    'price' => $total,
    //    'badges' => $badgeResults,
    //    'total' => $total,
    //    'nonce' => $nonce,
    //    'coupon' => $coupon,
    //    'discount' => $totalDiscount,

    $transid = $results['transid'];
    $ownerQ = <<<EOS
SELECT NP.first_name, NP.last_name, T.complete_date, T.couponDiscountCart, C.code, C.name
FROM transaction T
JOIN newperson NP ON (NP.id=T.newperid)
LEFT OUTER JOIN coupon C ON (T.coupon = C.id)
WHERE T.id=?
;
EOS;
    $owner = dbSafeQuery($ownerQ, 'i', array($transid))->fetch_assoc();

    $body = trim($owner['first_name'] . ' ' . $owner['last_name']) . ",\n\n";
    $body .= 'Thank you for registering for ' . $condata['label'] . "!\n\n";

    if ($ini['test'] == 1) {
        $body .= "This email was sent as part of testing.\n\n";
    }

    $body .= "Your Transaction number is $transid\n";
    if ($owner['code'] != null) {
        $body .= "A coupon of type " . $owner['code'] . " (" . $owner['name'] . ") was applied to this transaction";
        if ($totalDiscount > 0)
            $body .= " for a savings of " . $dolfmt->formatCurrency((float) $totalDiscount, $currency);
        $body .= "\n";
    }

     $body .= "and as there is no charge for this transaction, this is your receipt.\n\nMemberships have been created for:\n\n";

    $badgeQ = <<<EOS
SELECT NP.first_name, NP.last_name, M.label
FROM transaction T
JOIN reg R ON  (R.create_trans=T.id)
JOIN newperson NP ON (NP.id = R.newperid)
JOIN memLabel M ON (R.memID = M.id)
WHERE T.id= ?
EOS;

    $badgeR = dbSafeQuery($badgeQ, 'i', array($transid));

    while ($badge = $badgeR->fetch_assoc()) {
        $body .= '     * ' . $badge['first_name'] . ' ' . $badge['last_name']
            . ' (' . $badge['label'] . ")\n\n";
    }

    $body .= 'Please contact ' . $con['regemail'] . ' with any questions and we look forward to seeing you at ' . $condata['label'] . ".\n";

    $body .=
        'For hotel information and directions please see ' . $con['hotelwebsite'] . "\n" .
        'Click ' . $con['policy'] . ' for the ' . $con['policytext'] . ".\n" .
        'For more information about ' . $con['conname'] . ' please email ' . $con['infoemail'] . ".\n" .
        'For questions about ' . $con['conname'] . ' Registration, email ' . $con['regemail'] . ".\n" .
        $con['conname'] . " memberships are not refundable, except in case of emergency. For details and questions about transfers $rollovers, please see The Registration Policies Page.\n";

    return $body;
}
