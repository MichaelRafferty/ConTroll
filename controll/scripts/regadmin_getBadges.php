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

// all memberships (badges) for this conid
$badgeQ = <<<EOS
WITH notes AS (
    SELECT R.id, count(N.id) AS ncount
    FROM reg R
    JOIN regActions N on R.id = N.regId
    WHERE R.conid = ? AND N.action = 'notes'
    GROUP BY R.id
), history AS (
    SELECT R.id, count(H.historyId) AS hcount
    FROM reg R
    JOIN regHistory H ON R.id = H.id
    WHERE R.conid = ?
    GROUP BY R.id
)
SELECT R.id AS badgeId, IFNULL(R.complete_trans, R.create_trans) AS display_trans, R.create_trans, R.complete_trans, 
    P.id AS perid, NP.id AS newperson_id,   
    CASE WHEN R.perid IS NULL THEN 
            TRIM(CONCAT_WS(' ', TRIM(CONCAT_WS(' ', TRIM(CONCAT_WS(' ', IFNULL(NP.first_name, ''), IFNULL(NP.middle_name, ''))), 
            IFNULL(NP.last_name, ''))), IFNULL(NP.suffix, '')))
        ELSE
            TRIM(CONCAT_WS(' ', TRIM(CONCAT_WS(' ', TRIM(CONCAT_WS(' ', IFNULL(P.first_name, ''), IFNULL(P.middle_name, ''))),
            IFNULL(P.last_name, ''))), IFNULL(P.suffix, '')))
    END AS fullName,
    CASE WHEN R.perid IS NULL THEN IFNULL(NP.first_name, '') ELSE IFNULL(P.first_name, '') END AS first_name,
    CASE WHEN R.perid IS NULL THEN IFNULL(NP.middle_name, '') ELSE IFNULL(P.middle_name, '') END AS middle_name,
    CASE WHEN R.perid IS NULL THEN IFNULL(NP.last_name, '') ELSE IFNULL(P.last_name, '') END AS last_name,
    CASE WHEN R.perid IS NULL THEN IFNULL(NP.badge_name, '') ELSE IFNULL(P.badge_name, '') END AS badge_name,
    CASE WHEN R.perid IS NULL THEN IFNULL(NP.email_addr, '') ELSE IFNULL(P.email_addr, '') END AS email_addr,
    CASE WHEN R.perid IS NULL THEN IFNULL(NP.legalname, '') ELSE IFNULL(P.legalname, '') END AS legalname,
    CASE WHEN R.perid IS NULL THEN IFNULL(NP.pronouns, '') ELSE IFNULL(P.pronouns, '') END AS pronouns,
    M.label, R.memId, R.price, R.couponDiscount, R.paid, R.coupon, R.status, R.create_date, R.change_date,
    M.memCategory AS category, M.memType AS type, M.memAge AS age, 
    ifnull(C.name, ' None ') as name, N.ncount, H.hcount
FROM reg R
JOIN memLabel M ON (M.id=R.memId)
LEFT OUTER JOIN perinfo P ON (P.id=R.perid)
LEFT OUTER JOIN newperson NP ON (NP.id=R.newperid)
LEFT OUTER JOIN coupon C on (C.id = R.coupon)
LEFT OUTER JOIN notes N on N.id = R.id
LEFT OUTER JOIN history H on H.id = R.id
WHERE R.conid=?
ORDER BY R.create_date DESC;
EOS;

$response['query'] = $badgeQ;
$badges = [];
$badgeA = dbSafeQuery($badgeQ, 'iii', array($conid, $conid, $conid));
while($badge = $badgeA->fetch_assoc()) {
    array_push($badges, $badge);
}

$response['badges'] = $badges;

// now get all the filter tables (item, count)
$catQ = <<<EOS
WITH listitems AS (
    SELECT M.memCategory, count(*) occurs
    FROM reg R
    JOIN memList M ON (M.id=R.memId)
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

$categories = [];
$catA = dbSafeQuery($catQ, 'i', array($conid));
while($cat = $catA->fetch_assoc()) {
    array_push($categories, $cat);
}

$response['categories'] = $categories;

$typeQ = <<<EOS
WITH listitems AS (
    SELECT M.memType, count(*) occurs
    FROM reg R
    JOIN memList M ON (M.id=R.memId)
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

$types = [];
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

$labels = [];
$labelA = dbSafeQuery($labelQ, 'i', array($conid));
while($label = $labelA->fetch_assoc()) {
    array_push($labels, $label);
}

$response['labels'] = $labels;

$ageQ = <<<EOS
WITH listitems AS (
    SELECT M.memAge, count(*) occurs
    FROM reg R
    JOIN memList M ON (M.id=R.memId)
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

$ages = [];
$ageA = dbSafeQuery($ageQ, 'i', array($conid));
while($age = $ageA->fetch_assoc()) {
    array_push($ages, $age);
}

$response['ages'] = $ages;

$priceQ = <<<EOS
WITH listitems AS (
    SELECT R.price, count(*) occurs
    FROM reg R
    WHERE R.conid=?
    GROUP BY R.price
), totalrow AS (
    SELECT SUM(occurs) AS total
    FROM listitems
)
SELECT price, occurs, 100 * occurs / total AS percent
FROM listitems
JOIN totalrow
ORDER BY price;
EOS;

$prices = [];
$priceA = dbSafeQuery($priceQ, 'i', array($conid));
while($price = $priceA->fetch_assoc()) {
    array_push($prices, $price);
}

$response['prices'] = $prices;

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

$coupons = [];
$couponA = dbSafeQuery($couponQ, 'i', array($conid));
while($coupon = $couponA->fetch_assoc()) {
    array_push($coupons, $coupon);
}

$response['coupons'] = $coupons;

$statusQ = <<<EOS
WITH listitems AS (
    SELECT ifnull(R.status, ' None ') as name, count(*) occurs
    FROM reg R
    WHERE R.conid=?
    GROUP BY R.status
), totalrow AS (
    SELECT SUM(occurs) AS total
    FROM listitems
)
SELECT name, occurs, 100 * occurs / total AS percent
FROM listitems
JOIN totalrow
ORDER BY name;
EOS;

$statuses = [];
$statusA = dbSafeQuery($statusQ, 'i', array($conid));
while($status = $statusA->fetch_assoc()) {
    $statuses[] = $status;
}
$response['statuses'] = $statuses;

// lastly get the memLabel list itself for filtering
$memLabelQ = <<<EOS
SELECT *
FROM memLabel
WHERE conid = ?;
EOS;
$memLabels = [];
$memLabelA = dbSafeQuery($memLabelQ, 'i', array($conid));
while($memLabel = $memLabelA->fetch_assoc()) {
    $memLabels[] = $memLabel;
}
$response['memLabels'] = $memLabels;

$memLabels = [];
$memLabelA = dbSafeQuery($memLabelQ, 'i', array($conid +1));
while($memLabel = $memLabelA->fetch_assoc()) {
    $memLabels[] = $memLabel;
}
$response['memLabelsNext'] = $memLabels;

ajaxSuccess($response);
?>
