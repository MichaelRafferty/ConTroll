<?php

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "art_control";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if(!$check_auth || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$itemId = null;
if(array_key_exists('itemId', $_GET)) {$itemId = $_GET['itemId']; }
else { ajaxError('No Data'); }


$getItemQuery = <<<EOQ
SELECT i.id, i.exhibitorRegionYearId, i.item_key as itemNumber, i.title, i.material, i.type, i.status, 
       i.location, i.quantity, i.original_qty as orig_qty, i.min_price, i.sale_price, i.bidder, i.final_price
FROM artItems i
WHERE i.id = ?
EOQ;

$itemInfoR=dbSafeQuery($getItemQuery, 'i', array($itemId));
if($itemInfoR->num_rows != 1) {
    $response['error'] = "Bad Item Id: " . $itemId . "no data returned";
    ajaxSuccess($response);
}
$itemInfo = $itemInfoR->fetch_assoc();

$response['item'] = $itemInfo;
$region = $itemInfo['exhibitorRegionYearId'];

$response['region'] = $region;

$getArtistQuery = <<<EOQ
SELECT ERY.exhibitorNumber, ERY.locations, E.exhibitorName, HR.name as exhibitRegionName, HRY.id as exhibitRegionYearId
FROM exhibitorRegionYears ERY
    JOIN exhibitorYears EY on EY.id=ERY.exhibitorYearId
    JOIN exhibitors E on E.id=EY.exhibitorId
    JOIN exhibitsRegionYears HRY on HRY.id=ERY.exhibitsRegionYearId
    JOIN exhibitsRegions HR on HR.id=HRY.exhibitsRegion
WHERE ERY.id = ?
EOQ;

$artistR = dbSafeQuery($getArtistQuery, 'i', array($region));
if($artistR->num_rows != 1) {
    $response['error'] = "Bad Region: " . $region;
    ajaxSuccess($response);
}
$artistInfo = $artistR->fetch_assoc();
$response['artist'] = $artistInfo;

//TODO turn ERY.locations into an array (if not null)

//TODO get perinfo.id from bidder number in itemData (if id exists) or return null
$bidderId = $artistInfo['bidder'];
$getBidderQuery = <<<EOQ
SELECT CONCAT_WS(' ', p.first_name, p.last_name) as name, p.badge_name
FROM perinfo p
WHERE p.id = ?
EOQ;

if($bidderId != null) {
    $bidderR = dbSafeQuery($getBidderQuery, 'i', array($bidderId));
    if($bidderR->num_rows != 1) {
        $response['error'] = "Bad Bidder: " . $bidderId;
        ajaxSuccess($response);
    }
    $bidderInfo = $bidderR->fetch_assoc();
    $response['bidder'] = $bidderInfo;
} else { $response['bidder'] = null;}

ajaxSuccess($response);
?>
