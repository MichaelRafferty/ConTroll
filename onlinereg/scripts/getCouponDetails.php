<?php
require_once(__DIR__ . "/../../lib/db_functions.php");
require_once(__DIR__ . "/../../lib/ajax_functions.php");


if(!isset($_POST) || !isset($_POST['code'])) {
    ajaxSuccess(array('status'=>'error', 'error'=>"Error: no code")); exit();
}

db_connect();
$condata = get_con();
$con = get_conf('con');

$code = $_POST['code'];

// find the code in the system
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
$res = dbSafeQuery($couponQ, 'is', array($condata['id'], $code));
if ($res === false) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Error: Database Coupon Issue')); exit();
}

// for now, I am not supporting one use coupons, we'll get to that in a moment
if ($res->num_rows == 0) {
    ajaxSuccess(array('status'=>'error', 'error'=>'Error: Coupon not found')); exit();
}

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
    ajaxSuccess(array('status'=>'error', 'error'=> $ec)); exit();
}

$results = array(
  'coupon' => $coupon,
  );

ajaxSuccess($results);
?>
