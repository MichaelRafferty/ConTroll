<?php
//  purchase.php - library of modules related to performing a purchase of memberships

// load purchase data: mtypes, prices, coupon, rules
    function loadPurchaseData($conid, $couponCode, $couponSerial) {
        $prices = array ();
        $memId = array ();
        $counts = array ();
        $discounts = array ();
        $primary = array ();
        $map = array ();
        $coupon = null;

        $priceQ = <<<EOQ
SELECT m.id, m.memGroup, m.label, m.shortname, m.price, m.memCategory
FROM memLabel m
WHERE
    m.conid=?
    AND m.online = 'Y'
    AND startdate <= CURRENT_TIMESTAMP()
    AND enddate > CURRENT_TIMESTAMP()
;
EOQ;
        $mtypes = array ();
        $priceR = dbSafeQuery($priceQ, 'i', array ($conid));
        while ($priceL = $priceR->fetch_assoc()) {
            $mtypes[$priceL['id']] = $priceL;
        }

// get the coupon data, if any
        if ($couponCode !== null) {
            $result = load_coupon_data($couponCode, $couponSerial);
            if ($result['status'] == 'error') {
                ajaxSuccess($result);
                exit();
            }
            $coupon = $result['coupon'];
            if (array_key_exists('mtypes', $result))
                $mtypes = $result['mtypes'];
            //web_error_log("coupon:");
            //var_error_log($coupon);
        }

// now apply the price discount to the array
        if ($coupon !== null) {
            $mtypes = apply_coupon_data($mtypes, $coupon);
        }

// compute the count of each member type, and the
        foreach ($mtypes as $id => $mbrtype) {
            $map[$mbrtype['id']] = $mbrtype['id'];
            $prices[$mbrtype['id']] = $mbrtype['price'];
            $memId[$mbrtype['id']] = $mbrtype['id'];
            $counts[$mbrtype['id']] = 0;
            $isprimary = isPrimary($mbrtype);
            if ($coupon !== null) {
                $discounts[$mbrtype['id']] = $mbrtype['discount'];
                if ($coupon['memId'] == $mbrtype['id']) {  // ok this is a forced primary
                    $isprimary = true;                     // need a statement here, as combining the if's gets difficult
                }
            }
            $primary[$mbrtype['id']] = $isprimary;
        }

        $data = [
            'prices' => $prices,
            'mtypes' => $mtypes,
            'memId' =>  $memId,
            'discounts' => $discounts,
            'primary' => $primary,
            'counts' => $counts,
            'map' => $map,
            'coupon' => $coupon,
        ];
        return $data;
    }

    // ccompute Purchase Totals
    function computePurchaseTotals(&$coupon, $badges, $primary, $counts, $prices, $map, $discounts) {
        $num_primary = 0;
        $total = 0;

// compute the pre-discount total to see if the javascript passed prediscount total is correct
        foreach ($badges as $badge) {
            if (!isset($badge) || !isset($badge['memType'])) {
                continue;
            }
            if (array_key_exists($badge['memType'], $counts)) {
                if ($primary[$badge['memType']]) {
                    $num_primary++;
                }
                $total += $prices[$badge['memType']];
                $counts[$badge['memType']]++;
            }
        }


// now figure out if coupon applies
        $apply_discount = coupon_met($coupon, $total, $num_primary, $map, $counts);


        $total = 0;
        $preDiscount = 0;
        $totalDiscount = 0;
        $maxMbrDiscounts = 0;
        $discount = 0;
        if ($coupon != null) {
            if (array_key_exists('maxMemberships', $coupon)) {
                $maxMbrDiscounts = $coupon['maxMemberships'] != null ? $coupon['maxMemberships'] : 999999;
            }
        }
        $origMaxMbrDiscounts = $maxMbrDiscounts;

// check that we got valid total from the post before anything is inserted into the database, the empty rows are deleted badges from the site
        foreach ($badges as $badge) {
            if (!isset($badge) || !isset($badge['memType'])) {
                continue;
            }
            if (array_key_exists($badge['memType'], $counts)) {
                $price = $prices[$badge['memType']];
                $preDiscount += $price;
                if ($apply_discount && $primary[$badge['memType']]) {
                    if ($maxMbrDiscounts > 0) {
                        $price -= $discounts[$badge['memType']];
                        $maxMbrDiscounts--;
                        $totalDiscount += $discounts[$badge['memType']];
                    }
                }
                $total += $price;
            }
        }
        if ($apply_discount) {
            $discount = apply_overall_discount($coupon, $total);
            $total -= $discount;
            $totalDiscount += $discount;
        }

        $total = round($total, 2);
        $data = array(
            'total' => $total,
            'totalDiscount' => $totalDiscount,
            'discount' => $discount,
            'origMaxMbrDiscounts' => $origMaxMbrDiscounts,
            'preDiscount' => $preDiscount,
            'maxMbrDiscounts' => $maxMbrDiscounts,
            'applyDiscount' => $apply_discount,
            );

        return $data;
    }