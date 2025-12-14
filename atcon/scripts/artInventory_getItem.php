<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET, "session" => getAllSessionVars());

$con = get_con();
$conid=$con['id'];
$perm='artinventory';

$check_auth = check_atcon($perm, $conid);
if($check_auth == false) { 
    ajaxSuccess(array('error' => "Authentication Failure"));
    exit();
}

if(isset($_POST['artist'])) {
    $artist = $_POST['artist'];
} else {
    ajaxSuccess(array('error' => 'Need an Artist Number'));
    exit();
}
if(isset($_POST['region'])) {
    $region = $_POST['region'];
} else {
    ajaxSuccess(array('error' => 'Need an Region'));
    exit();
}
if (isset($_POST['item'])) {
    $item = $_POST['item'];
} else {
    $item = '';
}

$response = array('artist' => $artist, 'item' => $item);

$itemQ = <<<EOS
SELECT e.artistName, e.exhibitorName, eRY.exhibitorNumber, I.item_key, concat(eRY.exhibitorNumber, '-', I.item_key) as id,
    I.title, I.type, I.status, I.location, I.quantity, I.original_qty, 
    concat(I.quantity, '/', I.original_qty) as qty,
    I.min_price, I.sale_price, I.final_price, I.bidder, I.conid, 
    SUBSTRING(I.time_updated,6,11) as time_updated, eRY.exhibitorYearId, eY.exhibitorId
FROM artItems I
    JOIN exhibitorRegionYears eRY ON (eRY.id=I.exhibitorRegionYearId)
    JOIN exhibitorYears eY ON eY.id = eRY.exhibitorYearId
    JOIN exhibitors e ON e.id=eY.exhibitorId
    JOIN exhibitsRegionYears xRY ON (xRY.id=eRY.exhibitsRegionYearId)
    JOIN exhibitsRegions xR on (xR.id=xRY.exhibitsRegion)
WHERE eRY.exhibitorNumber=? AND eY.conid=? AND xR.shortname=?
EOS;
$itemI = 'iis';
$itemP = array($artist, $conid, $region);

if($item == '') {
    $itemQ .= ";";
} else {
    $itemQ .= " AND I.item_key=?;";
    $itemI .= 'i';
    $itemP += $item;
}

$itemR = dbSafeQuery($itemQ, $itemI, $itemP);
$itemArr = [];
$exhibitorYearId = -1;
while($newItem = $itemR->fetch_assoc()) {
    $exhibitorYearId = $newItem['exhibitorYearId'];
    $longName = $newItem['exhibitorName'];
    $artistName = $newItem['artistName'];
    if ($artistName != null && $artistName != '' && $artistName != $longName ) {
        $longName .= "($artistName)";
    }
    $newItem['name'] = $longName;
    $itemArr[] = $newItem;
}

$response['exhibitorYearId'] = $exhibitorYearId;
$response['items'] = $itemArr;

ajaxSuccess($response);
