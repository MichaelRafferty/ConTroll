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
SELECT I.item_key, I.title, I.type, I.status, I.location, I.quantity, I.original_qty, I.min_price, I.sale_price, I.final_price, I.bidder,
    ery.exhibitorNumber, ery.locations, e.exhibitorName,
    concat(trim(p.first_name), ' ', trim(p.last_name)) as bidderName,
    concat(trim(p.first_name), ' ', trim(p.last_name), ' (', I.bidder, ')') as bidderText
FROM artItems I 
    JOIN exhibitorRegionYears ery ON ery.id = I.exhibitorRegionYearId
    JOIN exhibitorYears ey ON ey.id=ery.exhibitorYearId
    JOIN exhibitors e ON e.id=ey.exhibitorId
    JOIN exhibitsRegionYears exRY ON exRY.id=ery.exhibitsRegionYearId
    LEFT JOIN perinfo p ON p.id=I.bidder
WHERE ey.conid=? and exRY.exhibitsRegion=?;
EOS;

$artR = dbSafeQuery($artQ, 'ii', array($conid, $region));

$items=array();

    while($artItem = $artR->fetch_assoc()) {
        $items[] = $artItem;
    }

$artistQ = <<<EOS
SELECT DISTINCT e.exhibitorName, ery.id as exhibitorRegionYearId, ery.exhibitorNumber
FROM exhibitorYears ey 
    JOIN exhibitorRegionYears ery ON ery.exhibitorYearId = ey.id
    JOIN exhibitors e ON ey.exhibitorId=e.id
    JOIN exhibitsRegionYears exRY ON exRY.id=ery.exhibitsRegionYearId
    JOIN exhibitorSpaces S on S.exhibitorRegionYear=ery.id 
WHERE ey.conid=? and exRY.exhibitsRegion=? and S.item_purchased is not null
    and ery.exhibitorNumber is not null;
EOS; 

$response['art'] = $items;
$artistR = dbSafeQuery($artistQ, 'ii', array($conid, $region));

$artists=array();

    while($artist = $artistR->fetch_assoc()) {
        $artists[] = $artist;
    }

$response['artists'] = $artists;


ajaxSuccess($response);
?>
