<?php
    function getEmailBody($transid, $owner, $memberships, $planRec, $rid, $url, $amount, $planPayment = 0): string {
    $condata = get_con();
    $ini = get_conf('reg');
    $con = get_conf('con');

    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }

    $dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);
    $body = 'Dear ' . trim($owner['first_name'] . ' ' . $owner['last_name']) . ",\n\n";
    $body .= 'Thank you for paying via the registration portal for ' . $condata['label'] . "!\n\n";

    if ($ini['test'] == 1) {
        $body .= "This email was send as part of testing.\n\n";
    }

    $body .= "Your Transaction number is $transid and Receipt number is $rid.\n";

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
        }
        $body .= "This payment is part of the $name payment plan, and you have agreed to make $num payments, one every $days days for " .
            $dolfmt->formatCurrency((float) $pmtAmt, $currency) . " each.\n";
    }

    if (array_key_exists('code', $owner) && $owner['code'] != null) {
        $body .= 'A coupon of type ' . $owner['code'] . ' (' . $owner['name'] . ') was applied to this transaction';
        if ($owner['couponDiscount'] > 0)
            $body .= ' for a savings of ' . $owner['couponDiscount'];
        $body .= "\n";
    }

    if ($planPayment != 1) {
        $body .= "Your card was charged " . $dolfmt->formatCurrency((float)$amount, $currency) . " for this transaction" .
            "\n\nThe following memberships were involved in this payment:\n\n";


        $fullnames = [];
        foreach ($memberships as $membership) {
            // portalPurchase sets the modified flag to true on all regs changed by this payment, and false to all the others.
            if (array_key_exists('modified', $membership) && $membership['modified'] == true) {
                if (array_key_exists($membership['fullname'], $fullnames))
                    continue;
                $body .= '     * ' . $membership['fullname'] . ' (' . $membership['label'] . ")\n\n";

                $fullnames[$membership['fullname']] = 1;
            }
        }
    } else {
        $body .= "Your card was charged " . $dolfmt->formatCurrency((float)$amount, $currency) . " for this plan payment" .
            " and your remaining balance due is " . $dolfmt->formatCurrency((float) $planRec['balanceDue'], $currency) . "\n\n";
    }

    if ($url != '') {
        $body .= "Your credit card receipt is available at $url\n\n";
    } else {
        $body .= "You will receive a separate email with credit card receipt details.\n\n";
    }

    $body .= 'Please contact ' . $con['regemail'] . ' with any questions and we look forward to seeing you at ' . $condata['label'] . ".\n";

    $body .=
        'For hotel information and directions please see ' . $con['hotelwebsite'] . "\n" .
        'Click ' . $con['policy'] . ' for the ' . $con['policytext'] . ".\n" .
        'For more information about ' . $con['conname'] . ' please email ' . $con['infoemail'] . ".\n" .
        'For questions about ' . $con['conname'] . ' Registration, email ' . $con['regemail'] . ".\n" .
        $con['conname'] . " memberships are not refundable. For details and questions about transfers and rollovers to future conventions, please see The Registration Policies Page.\n";

    return $body;
}

function getNoChargeEmailBody($transid, $owner, $memberships): string {
    $condata = get_con();
    $ini = get_conf('reg');
    $con = get_conf('con');

    $body = 'Dear ' . trim($owner['first_name'] . ' ' . $owner['last_name']) . ",\n\n";
    $body .= 'Thank you for registering for ' . $condata['label'] . "!\n\n";

    if ($ini['test'] == 1) {
        $body .= "This email was send as part of testing.\n\n";
    }

    $body .= "Your Transaction number is $transid\n";
    if (array_key_exists('code', $owner) && $owner['code'] != null) {
        $body .= 'A coupon of type ' . $owner['code'] . ' (' . $owner['name'] . ') was applied to this transaction';
        if ($owner['couponDiscount'] > 0)
            $body .= ' for a savings of ' . $owner['couponDiscount'];
        $body .= "\n";
    }

    $body .= "and as there is no charge for this transaction, this is your receipt.\n\n" .
        "\n\nThe following memberships were involved in this transaction:\n\n";

    $fullnames = [];
    foreach ($memberships as $membership) {
        // portalPurchase sets the modified flag to true on all regs changed by this payment, and false to all the others.
        if ($membership['modified'] == true) {
            if (array_key_exists($membership['fullname'], $fullnames))
                continue;
            $body .= '     * ' . $membership['fullname'] . ' (' . $membership['label'] . ")\n\n";

            $fullnames[$membership['fullname']] = 1;
        }
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
