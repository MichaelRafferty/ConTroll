<?php
    function getEmailBody($transid, $owner, $memberships, $coupon, $planRec, $rid, $url, $amount, $planPayment = 0): string {
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

    $body = 'Dear ' . trim($owner['first_name'] . ' ' . $owner['last_name']) . ",\n\n";
    $body .= 'Thank you for paying via the registration portal for ' . $condata['label'] . "!\n\n";

    if ($ini['test'] == 1) {
        $body .= "This email was sent as part of testing.\n\n";
    }

    $body .= "Your Transaction number is $transid and Receipt number is $rid.\n";

    if ($planRec != '') {
        $num = $planRec['numPayments'];
        $days = $planRec['daysBetween'];
        if ($planRec != null && $planPayment == 0) {
            if (array_key_exists('name', $planRec)) {
                $name = $planRec['name'];
            }
            else {
                $planData = $planRec['plan'];
                $name = $planData['name'];
            }
            if (array_key_exists('paymentAmt', $planRec)) {
                $pmtAmt = $planRec['paymentAmt'];
            }
            $body .= "This payment is part of the $name payment plan, and you have agreed to make $num payments, one every $days days for " .
                $dolfmt->formatCurrency((float)$pmtAmt, $currency) . " each.\n";
        }
    }

    if ($coupon != null && $planPayment == 0) {
        $body .= 'A coupon of type ' . $coupon['code'] . ' (' . $coupon['name'] . ') was applied to this transaction';
        if ($coupon['discount'] > 0)
            $body .= ' for a savings of ' . $dolfmt->formatCurrency((float) $coupon['totalDiscount'], $currency);
        $body .= "\n";
    }

    if ($planPayment != 1) {
        $body .= "Your card was charged " . $dolfmt->formatCurrency((float)$amount, $currency) . " for this transaction\n\n";

        if ($memberships && count($memberships) > 0) {
            $body .= "The following memberships were involved in this payment:\n\n";

            foreach ($memberships as $membership) {
                // portalPurchase sets the modified flag to true on all regs changed by this payment, and false to all the others.
                $body .= '     * ' . $membership['fullname'] . ' (' . $membership['label'] . ") for " .
                    $dolfmt->formatCurrency((float) $membership['price'], $currency);

                $due = $membership['price'] - ($membership['paid'] + $membership['couponDiscount']);
                if ($due > 0.01) {
                    $body .= ' with a balance due of ' . $dolfmt->formatCurrency($due, $currency);
                }

                $body .= "\n\n";
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
        $con['conname'] . " memberships are not refundable. For details and questions about transfers $rollovers, please see The Registration Policies Page.\n";

    return $body;
}

function getNoChargeEmailBody($transid, $owner, $memberships): string {
    $condata = get_con();
    $ini = get_conf('reg');
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

    $body = 'Dear ' . trim($owner['first_name'] . ' ' . $owner['last_name']) . ",\n\n";
    $body .= 'Thank you for registering for ' . $condata['label'] . "!\n\n";

    if ($ini['test'] == 1) {
        $body .= "This email was sent as part of testing.\n\n";
    }

    $body .= "Your Transaction number is $transid\n";
    if (array_key_exists('code', $owner) && $owner['code'] != null) {
        $body .= 'A coupon of type ' . $owner['code'] . ' (' . $owner['name'] . ') was applied to this transaction';
        if ($owner['couponDiscountCart'] > 0)
            $body .= ' for a savings of ' . $owner['totalDiscount'];
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
        $con['conname'] . " memberships are not refundable, except in case of emergency. For details and questions about transfers $rollovers, please see The Registration Policies Page.\n";

    return $body;
}
