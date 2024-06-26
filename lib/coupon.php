<?php
//  coupon.php - library of modules related to finding/repricing coupon based orders

// num_coupons = retrive the number of valid coupons active now
function num_coupons() {
    $con = get_conf('con');
    $couponQ = <<<EOS
SELECT COUNT(*) as num
FROM coupon 
WHERE conid = ?
AND startDate <= now() AND endDate > now();
EOS;
    $c = dbSafeQuery($couponQ, 'i', array($con['id']))->fetch_assoc();
    return $c['num'];
}

// retrieve list of coupons for atcon regs (not one-off)
function load_coupon_list() {
    $con = get_conf('con');
    $couponQ = <<<EOS
SELECT c.id, c.code, c.name
FROM coupon c
WHERE c.conid = ? AND c.oneUse = 0 AND c.startDate <= now() AND c.endDate > now()
ORDER BY c.code;
EOS;
    $res = dbSafeQuery($couponQ, 'i', array($con['id']));
    if ($res === false) {
        return array(-1, null); // count, array of coupons
    }
    $coupons = [];
    $num = $res->num_rows;
    while ($l = $res->fetch_assoc()) {
        $coupons[$l['id']] = $l;
    }
    return array($num, $coupons);
}

// retrieve the coupon data from the database
function load_coupon_data($couponCode, $serial = null): array
{
    $con = get_conf('con');

    // coupon code is required, as this works for a single specific coupon code
    if ($couponCode == null || $couponCode == '') {
        return array('status' => 'error', 'error' => 'coupon code required and not passed');
    }

    $couponQ = <<<EOS
SELECT c.id, c.oneUse, c.code, c.name, c.couponType, c.discount, c.oneUse, c.memId, c.minMemberships, c.maxMemberships, c.limitMemberships,
       c.minTransaction, c.maxTransaction, c.maxRedemption,
       count(t.id) AS redeemedCount, m.memAge, m.shortname, m.memGroup, m.label,
       CASE WHEN c.startDate > now() THEN 'early' ELSE null END as start, 
       CASE WHEN c.endDate <= now() THEN 'expired' ELSE null END as end,
       k.id as keyId, k.guid, k.usedBy
FROM coupon c
LEFT OUTER JOIN memLabel m ON (c.memId = m.id)
LEFT OUTER JOIN transaction t ON (t.coupon = c.id and t.complete_date is not null)
LEFT OUTER JOIN couponKeys k ON (k.couponId = c.id and (k.guid = ? || k.guid = ?))
WHERE  c.conid = ? AND ((c.code = ?) || (IFNULL(k.guid,'') = ?))
GROUP BY c.id, c.oneUse, c.code, c.name, c.couponType, c.discount, c.oneUse, c.memId, c.minMemberships, c.maxMemberships,
         c.minTransaction, c.maxTransaction, c.maxRedemption, m.memAge, m.label,
         k.id, k.guid, k.usedBy, c.startDate, c.endDate
ORDER BY c.startDate;
EOS;
    $res = dbSafeQuery($couponQ, 'ssiss', array($serial, $couponCode, $con['id'], $couponCode, $couponCode));
    if ($res === false) {
        return array('status' => 'error', 'error' => 'Database Coupon Issue');
    }

    if ($res->num_rows == 0) {
        return array('status' => 'error', 'error' => 'Error: Coupon not found');
    }

    $coupon = NULL;
    $ec = '';
    while ($l = $res->fetch_assoc()) {
        // this coupon is valid now as it returns Early, NULL, expired as values in the query
        if ($l['start'] == null and $l['end'] == null && $l['usedBy'] == null) {
            $coupon = $l;
            $ec = '';
            break;
        }
        if ($l['start'] != null)
            $ec = 'Coupon has not started yet, starts ' . $l['startDate'];
        if ($l['end'] != null)
            $ec = 'Coupon is expired';
        if ($l['usedBy'] != null)
            $ec = 'One use coupon has already been redeemed';
    }

    if ($ec != '')
        return array('status' => 'error', 'error' => $ec);

    if ($coupon['maxRedemption']) {
        if ($coupon['redeemedCount'] >= $coupon['maxRedemption'])
            return array('status' => 'error', 'error' => 'Coupon has already reached its maximum number of redemptions');
    }

    // if coupon contains a memId, make sure that memId is in list of things we can sell, refetch the mtype array
    $result = array('status' => 'success', 'coupon' => $coupon);
    if ($coupon['memId']) {
        $priceQ = <<<EOS
SELECT id, memGroup, label, shortname, 
       CASE WHEN id = ? THEN -1 ELSE sort_order END AS sort_order, 
       price, memAge, memCategory
FROM memLabel
WHERE
    conid=? 
    AND ((online = 'Y' AND startdate <= current_timestamp() AND enddate > current_timestamp()) OR (id = ?))
ORDER BY sort_order, price DESC
;
EOS;
        $priceR = dbSafeQuery($priceQ, 'iii', array($coupon['memId'], $con['id'], $coupon['memId']));
        while ($priceL = $priceR->fetch_assoc()) {
            $membershiptypes[] = $priceL;
        }
        $result['mtypes'] = $membershiptypes;
    }

    return $result;
}

// retrieve the coupon details for a specific id
function load_coupon_details($id): array
{
    $con = get_conf('con');

    // coupon code is required, as this works for a single specific coupon code
    if ($id == null) {
        return array('status' => 'error', 'error' => 'coupon id required and not passed');
    }

    $couponQ = <<<EOS
SELECT c.id, c.oneUse, c.code, c.name, c.couponType, c.discount, c.oneUse, c.memId, c.minMemberships, c.maxMemberships, c.limitMemberships,
       c.minTransaction, c.maxTransaction, c.maxRedemption,
       count(t.id) AS redeemedCount, m.memAge, m.shortname, m.memGroup, m.label,
       CASE WHEN c.startDate > now() THEN 'early' ELSE null END as start, 
       CASE WHEN c.endDate <= now() THEN 'expired' ELSE null END as end
FROM coupon c
LEFT OUTER JOIN memLabel m ON (c.memId = m.id)
LEFT OUTER JOIN transaction t ON (t.coupon = c.id and t.complete_date is not null)
WHERE c.conid = ? AND c.id = ?
GROUP BY c.id, c.oneUse, c.code, c.name, c.couponType, c.discount, c.oneUse, c.memId, c.minMemberships, c.maxMemberships,
         c.minTransaction, c.maxTransaction, c.maxRedemption, m.memAge, m.label, c.startDate, c.endDate
EOS;
    $res = dbSafeQuery($couponQ, 'ii', array($con['id'], $id));
    if ($res === false) {
        return array('status' => 'error', 'error' => 'Database Coupon Issue');
    }

    if ($res->num_rows == 0) {
        return array('status' => 'error', 'error' => 'Error: Coupon not found');
    }

    $coupon = $res->fetch_assoc();
    if ($coupon['maxRedemption']) {
        if ($coupon['redeemedCount'] >= $coupon['maxRedemption'])
            return array('status' => 'error', 'error' => 'Coupon has already reached its maximum number of redemptions');
    }

    // if coupon contains a memId, make sure that memId is in list of things we can sell, refetch the mtype array
    $result = array('status' => 'success', 'coupon' => $coupon);
    if ($coupon['memId']) {
        $priceQ = <<<EOS
SELECT id, memGroup, label, shortname, sort_order, price, memAge, memCategory
FROM memLabel
WHERE
    conid=? 
    AND ((atcon = 'Y' AND startdate <= current_timestamp() AND enddate > current_timestamp()) OR (id = ?))
ORDER BY sort_order, price DESC
;
EOS;
        $priceR = dbSafeQuery($priceQ, 'ii', array($con['id'], $coupon['memId']));
    } else {
        $priceQ = <<<EOS
SELECT id, memGroup, label, shortname, sort_order, price, memAge, memCategory
FROM memLabel
WHERE
    conid=? 
ORDER BY sort_order, price DESC
;
EOS;
        $priceR = dbSafeQuery($priceQ, 'i', array($con['id']));
    }
    while ($priceL = $priceR->fetch_assoc()) {
        $membershiptypes[$priceL['id']] = $priceL;
    }
    $result['mtypes'] = $membershiptypes;

    return $result;
}
// apply coupon data to mytpe array
function apply_coupon_data($mtypes, $coupon) {
    foreach ($mtypes as $id => $mbrtype) {
        $primary = true; // if coupon is active, does this 'num' count toward min / max memberships
        $discount = 0;

        // first compute primary membership types
        if ($coupon['memId'] && $coupon['memId'] == $mbrtype['id']) {  // ok this is a forced primary
            $primary = true; // need a statement here, as combining the if's gets difficult
        } else if ($mbrtype['price'] == 0 || ($mbrtype['memCategory'] != 'standard' && $mbrtype['memCategory'] != 'virtual')) {
            $primary = false;
        }

        if ($coupon['couponType'] == '$off' || $coupon['couponType'] == '%off') {
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

    // now check primary membership counts
    if ($coupon['memId'] != null)
        $checknum = $counts[$map[$coupon['memId']]];
    else
        $checknum = $num_primary;

    if ($coupon['minMemberships'])
        if ($checknum < $coupon['minMemberships'])
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

    $code = $coupon['couponType'];
    if ($code == '$off') {
        return min($total, $coupon['discount']);
    }

    if ($code == '%off') {
        if ($coupon['maxTransaction'] !== null)
            $discountable = $total > $coupon['maxTransaction'] ? $coupon['maxTransaction'] : $total;
        else
            $discountable = $total;

        return round($discountable * $coupon['discount'] / 100.0, 2);
    }

    return 0;
}
