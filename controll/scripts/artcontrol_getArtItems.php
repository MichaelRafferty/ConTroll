<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "art_control";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$conf = get_conf('con');

$con=get_con();
$conid=$con['id'];

$region = null;
if(array_key_exists('region', $_GET)) {$region = $_GET['region']; }
else { ajaxError('No Data'); } 

$artQ = <<<EOS
SELECT I.id, I.exhibitorRegionYearId, I.item_key, I.title, I.type, I.status, I.location, I.quantity, I.original_qty, 
       I.min_price, I.sale_price, I.final_price, I.bidder, I.material, I.notes,
    ery.exhibitorNumber, ery.locations, e.exhibitorName, exR.name as exhibitRegionName,
    concat(trim(p.first_name), ' ', trim(p.last_name)) as bidderName,
    concat(trim(p.first_name), ' ', trim(p.last_name), ' (', I.bidder, ')') as bidderText,
    concat(I.exhibitorRegionYearId, '_', I.item_key) as extendedKey
FROM artItems I 
    JOIN exhibitorRegionYears ery ON ery.id = I.exhibitorRegionYearId
    JOIN exhibitorYears ey ON ey.id=ery.exhibitorYearId
    JOIN exhibitors e ON e.id=ey.exhibitorId
    JOIN exhibitsRegionYears exRY ON exRY.id=ery.exhibitsRegionYearId
    JOIN exhibitsRegions exR on exR.id=exRY.exhibitsRegion
    LEFT JOIN perinfo p ON p.id=I.bidder
WHERE ey.conid=? and exRY.exhibitsRegion=?
ORDER BY ery.exhibitorNumber, I.item_key;
EOS;

$artR = dbSafeQuery($artQ, 'ii', array($conid, $region));

$items=array();

    while($artItem = $artR->fetch_assoc()) {
        $items[] = $artItem;
    }

$artistQ = <<<EOS
SELECT DISTINCT e.exhibitorName, ery.id as exhibitorRegionYearId, ery.exhibitorNumber, ery.locations
FROM exhibitorYears ey 
    JOIN exhibitorRegionYears ery ON ery.exhibitorYearId = ey.id
    JOIN exhibitors e ON ey.exhibitorId=e.id
    JOIN exhibitsRegionYears exRY ON exRY.id=ery.exhibitsRegionYearId
    JOIN exhibitorSpaces S ON S.exhibitorRegionYear=ery.id 
WHERE ey.conid=? AND exRY.exhibitsRegion=? AND S.item_purchased IS NOT NULL
    AND ery.exhibitorNumber IS NOT NULL
ORDER BY e.exhibitorName;
EOS; 

$response['art'] = $items;
$artistR = dbSafeQuery($artistQ, 'ii', array($conid, $region));

$artists=array();

    while($artist = $artistR->fetch_assoc()) {
        $artists[] = $artist;
    }

$response['artists'] = $artists;

ajaxSuccess($response);
