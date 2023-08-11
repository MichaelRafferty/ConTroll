<?php
require_once(__DIR__ . "/../../lib/db_functions.php");

function getEmailBody($transid) : string
{
    $condata = get_con();
    $ini = get_conf('reg');
    $con = get_conf('con');

    $ownerQ = <<<EOS
SELECT NP.first_name, NP.last_name, P.receipt_id as payid, T.complete_date, T.couponDiscount, T.paid, P.receipt_url AS url, C.code, C.name
FROM transaction T
JOIN newperson NP ON (NP.id=T.newperid)
JOIN payments P ON (P.transid=T.id)
LEFT OUTER JOIN coupon C ON (T.coupon = C.id)
WHERE T.id=?
;
EOS;
    $owner = fetch_safe_assoc(dbSafeQuery($ownerQ, 'i', array($transid)));

    $body = trim($owner['first_name'] . " " . $owner['last_name']) . ",\n\n";
    $body .= "Thank you for registering for " . $condata['label'] . "!\n\n";

    if ($ini['test'] == 1) {
        $body .= "This email was send as part of testing.\n\n";
    }

    $body .= "Your Transaction number is $transid and Receipt number is " . $owner['payid'] . "\n";

    if ($owner['code'] != null) {
        $body .= "A coupon of type " . $owner['code'] . "(" . $owner['nane'] . ") was applied to this transaction";
        if ($owner['couponDiscount'] > 0)
            $body .= " for a savings of " . $owner['couponDiscount'];
        $body .= "\n";
    }

    $body .= "Your card was charged " . $owner['paid'] . " for this transaction" .
        "\nMmemberships have been created for:\n\n";

    $badgeQ = <<<EOS
SELECT NP.first_name, NP.last_name, M.label
FROM transaction T
JOIN reg R ON  (R.create_trans=T.id)
JOIN newperson NP ON (NP.id = R.newperid)
JOIN memLabel M ON (R.memID = M.id)
WHERE T.id= ?
EOS;

    $badgeR = dbSafeQuery($badgeQ, 'i', array($transid));

    while ($badge = fetch_safe_assoc($badgeR)) {
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
        $con['conname'] . " memberships are not refundable, except in case of emergency. For details and questions about transfers and rollovers to future conventions, please see The Registration Policies Page.\n";

    return $body;
}

function getNoChangeEmailBody($results) : string
{
    $condata = get_con();
    $ini = get_conf('reg');
    $con = get_conf('con');

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
SELECT NP.first_name, NP.last_name, T.complete_date, T.couponDiscount, C.code, C.name
FROM transaction T
JOIN newperson NP ON (NP.id=T.newperid)
LEFT OUTER JOIN coupon C ON (T.coupon = C.id)
WHERE T.id=?
;
EOS;
    $owner = fetch_safe_assoc(dbSafeQuery($ownerQ, 'i', array($transid)));

    $body = trim($owner['first_name'] . ' ' . $owner['last_name']) . ",\n\n";
    $body .= 'Thank you for registering for ' . $condata['label'] . "!\n\n";

    if ($ini['test'] == 1) {
        $body .= "This email was send as part of testing.\n\n";
    }

    $body .= "Your Transaction number is $transid\n";
    if ($owner['code'] != null) {
        $body .= "A coupon of type " . $owner['code'] . "(" . $owner['name'] . ") was applied to this transaction";
        if ($owner['couponDiscount'] > 0)
            $body .= " for a savings of " . $owner['$couponDiscount'];
        $body .= "\n";
    }

     $body .= "and as there is no charge for this transaction, this is your receipt.\n\nIn response to your request memberships have been created for:\n\n";

    $badgeQ = <<<EOS
SELECT NP.first_name, NP.last_name, M.label
FROM transaction T
JOIN reg R ON  (R.create_trans=T.id)
JOIN newperson NP ON (NP.id = R.newperid)
JOIN memLabel M ON (R.memID = M.id)
WHERE T.id= ?
EOS;

    $badgeR = dbSafeQuery($badgeQ, 'i', array($transid));

    while ($badge = fetch_safe_assoc($badgeR)) {
        $body .= '     * ' . $badge['first_name'] . ' ' . $badge['last_name']
            . ' (' . $badge['label'] . ")\n\n";
    }

    $body .= 'Please contact ' . $con['regemail'] . ' with any questions and we look forward to seeing you at ' . $condata['label'] . ".\n";

    $body .=
        'For hotel information and directions please see ' . $con['hotelwebsite'] . "\n" .
        'Click ' . $con['policy'] . ' for the ' . $con['policytext'] . ".\n" .
        'For more information about ' . $con['conname'] . ' please email ' . $con['infoemail'] . ".\n" .
        'For questions about ' . $con['conname'] . ' Registration, email ' . $con['regemail'] . ".\n" .
        $con['conname'] . " memberships are not refundable, except in case of emergency. For details and questions about transfers and rollovers to future conventions, please see The Registration Policies Page.\n";

    return $body;
}
