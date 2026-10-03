<?php
function getEmailBody($transid, $owner, $coupon, $planRec, $amount, $planPayment = 0): array {
    $condata = get_con();
    $con = get_conf('con');
    $testsite = getConfValue('portal', 'test') == 1;

    $currency = getConfValue('con', 'currency', 'USD');
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

    $body = 'Dear ' . trim($owner['first_name'] . ' ' . $owner['last_name']) . ",\n\n" .
        'Thank you for paying via the registration portal for ' . $condata['label'] . "!\n\n";
    $bodyHtml = '<p>Dear ' . trim($owner['first_name'] . ' ' . $owner['last_name']) . ",</p>\n" .
        '<p>Thank you for paying via the registration portal for ' . $condata['label'] . "!</p>\n";

    if ($testsite) {
        $body .= "This email was sent as part of testing.\n\n";
        $bodyHtml .= "<p class='warn'>This email was sent as part of testing.</p>\n";
    }

    $body .= "Your Transaction number is $transid.\n";
    $bodyHtml .= "<p>Your Transaction number is $transid.</p>\n";

    if ($planRec != '') {
        $num = $planRec['numPayments'];
        $days = $planRec['daysBetween'];
        if ($planRec != null && $planPayment == 0) {
            if (array_key_exists('name', $planRec)) {
                $name = $planRec['name'];
            } else {
                $planData = $planRec['plan'];
                $name = $planData['name'];
            }
            if (array_key_exists('paymentAmt', $planRec)) {
                $pmtAmt = $planRec['paymentAmt'];
                $body .= "This payment is part of the $name payment plan, and you have agreed to make $num payments, one every $days days for " .
                    $dolfmt->formatCurrency((float)$pmtAmt, $currency) . " each.\n";
                $bodyHtml .= "<p>This payment is part of the $name payment plan, and you have agreed to make $num payments, one every $days days for " .
                    $dolfmt->formatCurrency((float)$pmtAmt, $currency) . " each.</p>\n";
            } else {
                $body .= "This payment is part of the $name payment plan, and you have agreed to make $num payments, one every $days days.\n";
                $bodyHtml .= "<p>This payment is part of the $name payment plan, and you have agreed to make $num payments, one every $days days.</p>\n";
            }
        }
    }

    if ($coupon != null && $planPayment == 0) {
        $body .= 'A coupon of type ' . $coupon['code'] . ' (' . $coupon['name'] . ') was applied to this transaction';
        $bodyHtml .= '<p>A coupon of type ' . $coupon['code'] . ' (' . $coupon['name'] . ') was applied to this transaction';
        if ($coupon['discount'] > 0) {
            $txt = ' for a savings of ' . $dolfmt->formatCurrency((float)$coupon['totalDiscount'], $currency);
            $body .= $txt;
            $bodyHtml .= $txt;
        }
        $body .= "\n";
        $bodyHtml .= "</p>\n";
    }

    if ($planPayment != 1) {
        $body .= "Your card was charged " . $dolfmt->formatCurrency((float)$amount, $currency) . " for this transaction\n\n";
        $bodyHtml .= "<p>Your card was charged " . $dolfmt->formatCurrency((float)$amount, $currency) . " for this transaction</p>\n";
    } else {
        $body .= "Your card was charged " . $dolfmt->formatCurrency((float)$amount, $currency) . " for this plan payment" .
            " and your remaining balance due is " . $dolfmt->formatCurrency((float) $planRec['balanceDue'], $currency) . "\n\n";
        $bodyHtml .= '<p>Your card was charged ' . $dolfmt->formatCurrency((float)$amount, $currency) . ' for this plan payment' .
            ' and your remaining balance due is ' . $dolfmt->formatCurrency((float)$planRec['balanceDue'], $currency) . "</p>\n";
    }

    $receipt = trans_receipt($transid);
    $body .= $receipt['receipt'];
    $bodyHtml .= $receipt['receipt_tables'];

    $body .= '\nPlease contact ' . $con['regemail'] . ' with any questions and we look forward to seeing you at ' . $condata['label'] . ".\n";
    $bodyHtml .= '<p>Please contact ' . $con['regemail'] . ' with any questions and we look forward to seeing you at ' . $condata['label'] . ".</p>\n";

    $body .=
        'For hotel information and directions please see ' . $con['hotelwebsite'] . "\n" .
        'Click ' . $con['policy'] . ' for the ' . $con['policytext'] . ".\n" .
        'For more information about ' . $con['conname'] . ' please email ' . $con['infoemail'] . ".\n" .
        'For questions about ' . $con['conname'] . ' Registration, email ' . $con['regemail'] . ".\n" .
        $con['conname'] . " memberships are not refundable. For details and questions about transfers $rollovers, please see The Registration Policies Page.\n";


    $bodyHtml .=
        '<ul><li>For hotel information and directions please see ' . $con['hotelwebsite'] . "</li>\n" .
        '<li>Click <a href="' . $con['policy'] . '">'  . $con['policy'] . '</a> for the ' . $con['policytext'] . ".</li>\n" .
        '<li>For more information about ' . $con['conname'] . ' please email <a href="mailto:' . $con['infoemail'] . '">' .
            $con['infoemail'] . "</a></li>\n" .
        '<li>For questions about ' . $con['conname'] . ' Registration, email <a href="mailto:' . $con['regemail'] . '">' .
            $con['regemail'] . "</a></li>\n</ul>\n" .
        '<p>' . $con['conname'] .
            " memberships are not refundable. For details and questions about transfers $rollovers, please see The Registration Policies Page.</p>\n";

    return array($body, $bodyHtml);
}

function getNoChargeEmailBody($transid, $owner): array {
    $condata = get_con();
    $testsite = getConfValue('portal', 'test') == 1;
    $con = get_conf('con');

    if (array_key_exists('oneoff', $con)) {
        $oneoff = $con['oneoff'];
    } else {
        $oneoff = 0;
    }

    if ($oneoff != 1)
        $rollovers = 'and rollovers to future conventions';
    else
        $rollovers = '';

    $body = 'Dear ' . trim($owner['first_name'] . ' ' . $owner['last_name']) . ",\n\n" .
        'Thank you for paying via the registration portal for ' . $condata['label'] . "!\n\n";
    $bodyHtml = '<p>Dear ' . trim($owner['first_name'] . ' ' . $owner['last_name']) . ",</p>\n" .
        '<p>Thank you for paying via the registration portal for ' . $condata['label'] . "!</p>\n";

    if ($testsite) {
        $body .= "This email was sent as part of testing.\n\n";
        $bodyHtml .= "<p class='warn'>This email was sent as part of testing.</p>\n";
    }

    $body .= "Your Transaction number is $transid and as there is no charge for this transaction, this is your receipt.\n\n";
    $bodyHtml .= "<p>Your Transaction number is $transid and as there is no charge for this transaction, this is your receipt.</p>\n";

    $receipt = trans_receipt($transid);
    $body .= $receipt['receipt'];
    $bodyHtml .= $receipt['receipt_tables'];


    $body .= "\nPlease contact " . $con['regemail'] . ' with any questions and we look forward to seeing you at ' . $condata['label'] . ".\n";
    $bodyHtml .= '<p>Please contact ' . $con['regemail'] . ' with any questions and we look forward to seeing you at ' . $condata['label'] . ".</p>\n";

    $body .=
        'For hotel information and directions please see ' . $con['hotelwebsite'] . "\n" .
        'Click ' . $con['policy'] . ' for the ' . $con['policytext'] . ".\n" .
        'For more information about ' . $con['conname'] . ' please email ' . $con['infoemail'] . ".\n" .
        'For questions about ' . $con['conname'] . ' Registration, email ' . $con['regemail'] . ".\n" .
        $con['conname'] . " memberships are not refundable. For details and questions about transfers $rollovers, please see The Registration Policies Page.\n";


    $bodyHtml .=
        '<ul><li>For hotel information and directions please see ' . $con['hotelwebsite'] . "</li>\n" .
        '<li>Click <a href="' . $con['policy'] . '">'  . $con['policy'] . '</a> for the ' . $con['policytext'] . ".</li>\n" .
        '<li>For more information about ' . $con['conname'] . ' please email <a href="mailto:' . $con['infoemail'] . '">' .
        $con['infoemail'] . "</a></li>\n" .
        '<li>For questions about ' . $con['conname'] . ' Registration, email <a href="mailto:' . $con['regemail'] . '">' .
        $con['regemail'] . "</a></li>\n</ul>\n" .
        '<p>' . $con['conname'] .
        " memberships are not refundable. For details and questions about transfers $rollovers, please see The Registration Policies Page.</p>\n";

    return array($body, $bodyHtml);
}
