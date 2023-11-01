<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con=get_conf('con');
$conid= $con['id'];
$response['conid'] = $conid;

$badgeQ = <<<EOS
SELECT R.create_date, R.change_date, R.price, R.couponDiscount, R.paid, R.id AS badgeId, P.id AS perid, NP.id AS np_id
    , CASE WHEN R.perid IS NULL THEN CONCAT_WS(' ', NP.first_name, NP.middle_name, NP.last_name, NP.suffix)
        ELSE CONCAT_WS(' ', P.first_name, P.middle_name, P.last_name, P.suffix) 
      END AS p_name
    , P.badge_name AS p_badge, NP.badge_name AS np_badge
    , CONCAT_WS('-', M.memCategory, M.memType, M.memAge) as memTyp
    , M.memCategory AS category, M.memType AS type, M.memAge AS age, M.label
    , ifnull(C.name, ' None ') as name
    , R.create_trans, R.complete_trans, IFNULL(R.complete_trans, R.create_trans) AS display_trans
FROM reg R
JOIN memLabel M ON (M.id=R.memId)
LEFT OUTER JOIN perinfo P ON (P.id=R.perid)
LEFT OUTER JOIN newperson NP ON (NP.id=R.newperid)
LEFT OUTER JOIN coupon C on (C.id = R.coupon)
WHERE R.conid=?
ORDER BY R.create_date DESC;
EOS;

$response['query'] = $badgeQ;

$badges = array();

$badgeA = dbSafeQuery($badgeQ, 'i', array($conid));
while($badge = $badgeA->fetch_assoc()) {
    array_push($badges, $badge);
}

$response['badges'] = $badges;

$catQ = <<<EOS
WITH listitems AS (
    SELECT M.memCategory, count(*) occurs
    FROM reg R
    JOIN memLabel M ON (M.id=R.memId)
    WHERE R.conid=?
    GROUP BY M.memCategory
), totalrow AS (
    SELECT SUM(occurs) AS total
    FROM listitems
)
SELECT memCategory, occurs, 100 * occurs / total AS percent
FROM listitems
JOIN totalrow
ORDER BY memCategory;
EOS;

$categories = array();
$catA = dbSafeQuery($catQ, 'i', array($conid));
while($cat = $catA->fetch_assoc()) {
    array_push($categories, $cat);
}

$response['categories'] = $categories;

$typeQ = <<<EOS
WITH listitems AS (
    SELECT M.memType, count(*) occurs
    FROM reg R
    JOIN memLabel M ON (M.id=R.memId)
    WHERE R.conid=?
    GROUP BY M.memType
), totalrow AS (
    SELECT SUM(occurs) AS total
    FROM listitems
)
SELECT memType, occurs, 100 * occurs / total AS percent
FROM listitems
JOIN totalrow
ORDER BY memType;
EOS;

$types = array();
$typeA = dbSafeQuery($typeQ, 'i', array($conid));
while($type = $typeA->fetch_assoc()) {
    array_push($types, $type);
}

$response['types'] = $types;

$labelQ = <<<EOS
WITH listitems AS (
    SELECT M.label, count(*) occurs
    FROM reg R
    JOIN memLabel M ON (M.id=R.memId)
    WHERE R.conid=?
    GROUP BY M.label
), totalrow AS (
    SELECT SUM(occurs) AS total
    FROM listitems
)
SELECT label, occurs, 100 * occurs / total AS percent
FROM listitems
JOIN totalrow
ORDER BY label;
EOS;

$labels = array();
$labelA = dbSafeQuery($labelQ, 'i', array($conid));
while($label = $labelA->fetch_assoc()) {
    array_push($labels, $label);
}

$response['labels'] = $labels;

$ageQ = <<<EOS
WITH listitems AS (
    SELECT M.memAge, count(*) occurs
    FROM reg R
    JOIN memLabel M ON (M.id=R.memId)
    WHERE R.conid=?
    GROUP BY M.memAge
), totalrow AS (
    SELECT SUM(occurs) AS total
    FROM listitems
)
SELECT memAge, occurs, 100 * occurs / total AS percent
FROM listitems
JOIN totalrow
ORDER BY memAge;
EOS;

$ages = array();
$ageA = dbSafeQuery($ageQ, 'i', array($conid));
while($age = $ageA->fetch_assoc()) {
    array_push($ages, $age);
}

$response['ages'] = $ages;

$paidQ = <<<EOS
WITH listitems AS (
    SELECT R.paid, count(*) occurs
    FROM reg R
    WHERE R.conid=?
    GROUP BY R.paid
), totalrow AS (
    SELECT SUM(occurs) AS total
    FROM listitems
)
SELECT paid, occurs, 100 * occurs / total AS percent
FROM listitems
JOIN totalrow
ORDER BY paid;
EOS;

$paids = array();
$paidA = dbSafeQuery($paidQ, 'i', array($conid));
while($paid = $paidA->fetch_assoc()) {
    array_push($paids, $paid);
}

$response['paids'] = $paids;

$couponQ = <<<EOS
WITH listitems AS (
    SELECT ifnull(C.name, ' None ') as name, count(*) occurs
    FROM reg R
    LEFT OUTER JOIN coupon C ON (C.id  = R.coupon)
    WHERE R.conid=?
    GROUP BY C.name
), totalrow AS (
    SELECT SUM(occurs) AS total
    FROM listitems
)
SELECT name, occurs, 100 * occurs / total AS percent
FROM listitems
JOIN totalrow
ORDER BY name;
EOS;

$coupons = array();
$couponA = dbSafeQuery($couponQ, 'i', array($conid));
while($coupon = $couponA->fetch_assoc()) {
    array_push($coupons, $coupon);
}

$response['coupons'] = $coupons;
ajaxSuccess($response);
?>
