<?php
//  purchase.php - library of modules related to performing a purchase of memberships

// load purchase data: mtypes, prices, coupon, rules
    function loadPurchaseData($conid, $couponCode, $couponSerial, $planPayment = 0) {
        $prices = array ();
        $memId = array ();
        $counts = array ();
        $discounts = array ();
        $primary = array ();
        $map = array ();
        $coupon = null;
        $memCategories = array();

        // membership information
        if ($planPayment > 0) {
            $priceQ = <<<EOQ
SELECT m.id, m.label, m.shortname, m.price, m.memCategory, m.memType, m.memAge, m.conid, m.glNum
FROM memLabel m
WHERE m.conid=? OR m.conid=?;
EOQ;
        } else {
            $priceQ = <<<EOQ
SELECT m.id, m.label, m.shortname, m.price, m.memCategory, m.memType, m.memAge, m.conid, m.glNum
FROM memLabel m
WHERE
    (m.conid=? OR m.conid=?)
    AND m.online = 'Y'
    AND startdate <= CURRENT_TIMESTAMP()
    AND enddate > CURRENT_TIMESTAMP()
;
EOQ;
        }
        $mtypes = array ();
        $priceR = dbSafeQuery($priceQ, 'ii', array ($conid, $conid + 1));
        while ($priceL = $priceR->fetch_assoc()) {
            $mtypes[$priceL['id']] = $priceL;
        }
        $priceR->free();

        $memCatQ = <<<EOQ
SELECT memCategory, onlyOne, standAlone, variablePrice, taxable
FROM memCategories
WHERE active = 'Y';
EOQ;
        $memCatR = dbQuery($memCatQ);
        while ($memCatL = $memCatR->fetch_assoc()) {
            $memCategories[$memCatL['memCategory']] = $memCatL;
        }
        $memCatR->free();

// get the coupon data, if any
        if ($couponCode != null && $couponCode != "") {
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
            $isprimary = isPrimary($mbrtype, $conid, 'coupon');
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
            'memCategories' => $memCategories,
        ];
        return $data;
    }

    // compute Purchase Totals
    function computePurchaseTotals(&$coupon, &$badges, $primary, $counts, $prices, $map, $discounts, $mtypes, $memCategories) : array {
        $num_primary = 0;
        $total = 0;
        $badgeCheckQ = <<<EOS
SELECT r.price, r.perid, r.updatedBy, u.perid
FROM reg r
LEFT OUTER JOIN user u ON r.updatedBy = u.perid
WHERE r.id = ? AND r.price = ? AND u.perid IS NOT NULL
UNION 
SELECT r.price, r.perid, r.updatedBy, u.perid
FROM regHistory r
LEFT OUTER JOIN user u ON r.updatedBy = u.perid
WHERE r.id = ? AND r.price = ? AND u.perid IS NOT NULL;
EOS;


// compute the pre-discount total to see if the javascript passed prediscount total is correct
        foreach ($badges as $key => $badge) {
            if (!isset($badge) || !isset($badge['memId'])) {
                continue;
            }
            $memId = $badge['memId'];
            if (array_key_exists($memId, $counts)) {
                $counts[$memId]++;
            }
            if ($primary[$memId]) {
                $num_primary++;
            }
            $mtype = $mtypes[$memId];
            // add gl code to badge list
            $badges[$key]['glNum'] = $mtype['glNum'];
            $memCategory = $memCategories[$mtype['memCategory']];
            $price = $prices[$memId];
            if (array_key_exists('price', $badge))
                $badgePrice = $badge['price'];
            else {
                $badgePrice = $price;
                $badges[$key]['price'] = $price;
            }

            if ($badgePrice != $price && $memCategory['variablePrice'] == 'N') {
                // we have a conflict, is this a legitimate price change by an admin or is this a cheat price from javascript hacking?
                $badgeCheckR = dbSafeQuery($badgeCheckQ, 'idid', array($badge['regId'], $badgePrice, $badge['regId'], $badgePrice));
                if ($badgeCheckR !== false) {
                    if ($badgeCheckR->num_rows > 0) {
                        logWrite(array('message' => 'Override of badge price accepted due to admin action causing it',
                                       'perid' => $badge['perid'], 'badgeid' => $badge['regId'], 'memId' => $memId,
                                       'memPrice' => $price, 'badgePrice' => $badgePrice));
                        $price = $badgePrice;
                        $badges[$key]['overRidePrice'] = $badgePrice;
                        $badgeCheckR->free();
                    } else {
                        logWrite(array('message' => 'Override of badge price failed due to no admin action detected to cause it',
                                       'perid' => $badge['perid'], 'badgeid' => $badge['regId'], 'memId' => $memId,
                                       'memPrice' => $price, 'badgePrice' => $badgePrice));
                       $badges[$key]['overRidePrice'] = $price;
                    }
                } else {
                    logWrite(array('message' => 'Override of badge price failed due query error',
                                   'perid' => $badge['perid'], 'badgeid' => $badge['regId'], 'memId' => $memId,
                                   'memPrice' => $price, 'badgePrice' => $badgePrice));
                    $badges[$key]['overRidePrice'] = $price;
                }
            }

            if ($memCategory['variablePrice'] == 'Y') {
                if ($price < $badge['price'])
                    $price = $badge['price'];
            }
            $total += $price;
        }

// now figure out if coupon applies
        $apply_discount = coupon_met($coupon, $total, $num_primary, $map, $counts);

        $total = 0;
        $preDiscount = 0;
        $totalDiscount = 0;
        $totalElibibleForDiscount = 0;
        $maxMbrDiscounts = 0;
        $discount = 0;
        $paid = 0;
        if ($coupon != null) {
            if (array_key_exists('maxMemberships', $coupon)) {
                $maxMbrDiscounts = $coupon['maxMemberships'] != null ? $coupon['maxMemberships'] : 999999;
            }
        }
        $origMaxMbrDiscounts = $maxMbrDiscounts;

// check that we got valid total from the post before anything is inserted into the database, the empty rows are deleted badges from the site
        foreach ($badges as $badge) {
            if (!isset($badge) || !isset($badge['memId'])) {
                continue;
            }
            $memId = $badge['memId'];

            $mtype = $mtypes[$memId];
            $memCategory = $memCategories[$mtype['memCategory']];
            $price = $prices[$memId];
            $badgePrice = $badge['price'];
            if ($badgePrice != $price) {
                if (array_key_exists('overRidePrice', $badge))
                    $price = $badge['overRidePrice'];
            }
            if ($memCategory['variablePrice'] == 'Y') {
                if ($price < $badge['price'])
                    $price = $badge['price'];
            }
            $preDiscount += $price;
            if ($apply_discount && $primary[$badge['memId']]) {
                if ($maxMbrDiscounts > 0) {
                    $price -= $discounts[$badge['memId']];
                    $maxMbrDiscounts--;
                    $totalDiscount += $discounts[$badge['memId']];
                    $totalElibibleForDiscount += $price;
                }
            }
            $total += $price;
            if (array_key_exists('paid', $badge))
                $paid += $badge['paid'];
        }
        if ($apply_discount) {
            $discount = apply_overall_discount($coupon, $totalElibibleForDiscount);
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
            'paid' => $paid,
            );

        return $data;
    }