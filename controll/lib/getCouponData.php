<?php

function getCouponData($response)
{
    $con = get_conf('con');
    $conid = $con['id'];

    $couponQ = <<<EOS
WITH keycounts AS (
	SELECT c.id, count(*) keycount
    FROM coupon c
    JOIN couponKeys k ON (c.id = k.couponId)
    GROUP by c.id
), coupons AS (
    SELECT c.id,c.conid,c.oneUse,c.code,c.name,c.startDate,c.endDate,c.couponType,c.discount,c.memId,c.minMemberships,c.maxMemberships,c.limitMemberships,
        c.minTransaction,c.maxTransaction,c.maxRedemption,c.createTS,c.createBy,c.updateTS,c.updateBy,
        COUNT(t.id) AS redeemedCount, 
        m.memAge, m.shortname, m.label,
        CASE WHEN c.startDate > now() THEN 'early' ELSE null END AS startFlag, 
        CASE WHEN c.endDate <= now() THEN 'expired' ELSE null END AS endFlag,
        CASE WHEN c.startDate < '2000-01-01' THEN null ELSE c.startDate END AS dispStart,
        CASE WHEN c.endDate > '2099-01-01' THEN null ELSE c.endDate END AS dispEnd,
        k.keycount
    FROM coupon c
    LEFT OUTER JOIN memLabel m ON (c.memId = m.id)
    LEFT OUTER JOIN transaction t ON (t.coupon = c.id and t.complete_date is not null)
    LEFT OUTER JOIN keycounts k ON (k.id = c.id)
    WHERE c.conid = ?
    GROUP BY c.id,c.conid,c.oneUse,c.code,c.name,c.startDate,c.endDate,c.couponType,c.discount,c.memId,c.minMemberships,c.maxMemberships,c.limitMemberships,
        c.minTransaction,c.maxTransaction,c.maxRedemption,c.createTS,c.createBy,c.updateTS,c.updateBy,
        m.memAge, m.shortname, m.label
    ORDER BY c.startDate, code
)
SELECT *, CASE WHEN redeemedCount >= maxRedemption THEN 'FULL' ELSE redeemedCount END AS redeemedCount
FROM coupons;
EOS;

    $couponR = dbSafeQuery($couponQ, 'i', array($conid));
    if ($couponR == false) {
        $response['status'] = 'error';
        $response['error'] = 'Error: Error retrieving coupon list';
        ajaxSuccess($response);
        exit();
    }
    $coupons = array();
    while ($couponL = $couponR->fetch_assoc()) {
        $coupons[] = $couponL;
    }

    $response['status'] = 'success';
    $response['coupons'] = $coupons;

    return $response;
}
?>
