<?php
//  coupon.php - library of modules related to finding/repricing coupon based orders

// retrieve the coupon data from the database
function load_coupon_data($couponCode): array
{
    $con = get_conf('con');

    // coupon code is required, as this works for a single specific coupon code
    if ($couponCode == null || $couponCode == '') {
        return array('status'=>'error', 'error'=>'coupon code required and not passed');
    }

    $couponQ = <<<EOS
SELECT c.id, c.oneUse, c.code, c.name, c.couponType, c.discount, c.memId, c.minMemberships, c.maxMemberships, c.minTransaction, c.maxTransaction, c.maxRedemption,
    count(t.id) AS redeemedCount, m.memAge, m.label,
    CASE WHEN c.startDate > now() THEN 'early' ELSE null END as start, 
    CASE WHEN c.endDate <= now() THEN 'expired' ELSE null END as end
FROM coupon c
LEFT OUTER JOIN memList m ON (c.memId = m.id)
LEFT OUTER JOIN transaction t ON (t.coupon = c.id and t.complete_date is not null)
WHERE c.conid = ? AND code = ? AND oneUse = 0 /*AND c.startDate <= now() AND c.endDate >= now()*/
GROUP BY c.id, c.oneUse, c.code, c.name, c.couponType, c.discount, c.memId, c.minMemberships, c.maxMemberships, c.minTransaction, c.maxTransaction, c.maxRedemption, m.memAge, m.label
ORDER BY c.startDate;
EOS;
    $res = dbSafeQuery($couponQ, 'is', array($con['id'], $couponCode));
    if ($res === false) {
        return array('status'=>'error', 'error'=>'Database Coupon Issue');
    }

// for now, I am not supporting one use coupons, we'll get to that in a moment
    if ($res->num_rows == 0) {
        return array('status'=>'error', 'error'=>'Error: Coupon not found');
    }

    $coupon = NULL;
    $ec = '';
    while ($l = fetch_safe_assoc($res)) {
        if ($l['start'] == null and $l['end'] == null) {
            $coupon = $l;
            $ec = '';
            break;
        }
        if ($l['start'] != null)
            $ec = 'Coupon has not started yet, starts ' . $l['startDate'];
        if ($l['end'] != null)
            $ec = 'Coupon is expired';
    }

    if ($ec != '') {
        return array('status'=>'error', 'error'=> $ec);
    }

    return array('status'=>'success', 'coupon'=> $coupon);
}

// apply coupon data to mytpe array
function apply_coupon_data($mtypes, $coupon) {
    foreach ($mtypes as $id => $mbrtype) {
        $primary = true; // if coupon is active, does this 'num' count toward min / max memberships
        $discount = 0;
        if ($mbrtype['price'] == 0 || ($mbrtype['memCategory'] != 'standard' && $mbrtype['memCategory'] != 'virtual')) {
            $discount = 0; // no discount if no coupon, price is 0 or its not a primary membership
            $primary = false;
        } else if ($coupon['couponType'] == '$off' || $coupon['couponType'] == '%off') {
            $discount = 0; // cart type memberships don't discount rows
        } else if ($coupon['memId'] == null || $coupon['memId'] == $mbrtype['id']) { // ok, we have a coupon type that applies to this row
            if ($coupon['couponType'] == 'price') {
                // set price for a specific membership type, set the discount to the difference between the real price and the 'coupon price'
                $discount = $mbrtype['price'] - $coupon['discount'];
            } else if ($coupon['couponType'] == '$mem') {
                // flat $ discount on the primary membership
                $discount = $coupon['discount'];
            } else if ($coupon['couponType'] == '%mem') {
                // % off primaary membership set price.
                $discount = $mbrtype['price'] * $coupon['discount'] / 100.0;
            }
            // if the discount is > than the price limit it to the price.
            if ($discount > $mbrtype['price']) {
                $discount = $mbrtype['price'];
            }
        }
        $mtypes[$id]['primary'] = $primary;
        $mtypes[$id]['discount'] = round($discount, 2);
        $mtypes[$id]['discountable'] = $discount > 0;
    }
    return $mtypes;
}

// coupon_met - are the minimumn purchase requirements of the coupon met?
function coupon_met(&$coupon, $total, $num_primary, $map, $counts) : bool
{
    if ($coupon == null)
        return false;

    $coupon['coupon_met'] = false;

    // check transaction limits
    if ($coupon['minTransaction'] !== null)
        if ($total < $coupon['minTransaction'])
            return false;

    if ($coupon['maxTransaction'] !== null)
        if ($total > $coupon['maxTransaction'])
            return false;

    // now check primary membership counts
    if ($coupon['memId'] != null)
        $checknum = $counts[$map[$coupon['memId']]];
    else
        $checknum = $num_primary;

    if ($coupon['minMemberships'])
        if ($checknum < $coupon['minMemberships'])
            return false;

    if ($coupon['maxMemberships'])
        if ($checknum > $coupon['maxMemberships'])
            return false;

    // all tests pass
    $coupon['coupon_met'] = true;
    return true;
}

// return the amount of the overall discount, if any, presumes that the discount provisions were met (stored in coupon field))
function apply_overall_discount($coupon, $total) {
    if ($coupon === null)
        return 0;

    if (!$coupon['coupon_met'])
        return 0;

    $code = $coupon['code'];
    if ($code == '$off') {
        return min($total, $coupon['discount']);
    }

    if ($code == '%off') {
        return round($total * $coupon['discount'] /100.0, 2);
    }

    return 0;
}
