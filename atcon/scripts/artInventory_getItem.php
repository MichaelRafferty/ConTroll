<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET, "session" => getAllSessionVars());

$con = get_con();
$conid=$con['id'];
$perm='artinventory';

$check_auth = check_atcon($perm, $conid);
if($check_auth == false) { 
    ajaxSuccess(array('error' => "Authentication Failure"));
}

if(isset($_GET['artist'])) { 
    $artist = $_GET['artist'];
} else {
    ajaxSuccess(array('error' => 'Need an Artist Number'));
}
if(isset($_GET['item'])) { 
    $item = $_GET['item'];
} else {
    $item = '';
}

$response = array('artist' => $artist, 'item' => $item);

$itemQ = <<<EOS
SELECT e.artistName, e.exhibitorName, eRY.exhibitorNumber, I.item_key, concat(eRY.exhibitorNumber, '-', I.item_key) as id,
    I.title, I.type, I.status, I.location, I.quantity, I.original_qty, 
    concat(I.quantity, '/', I.original_qty) as qty,
    I.min_price, I.sale_price, I.final_price, I.bidder, I.conid, 
    SUBSTRING(I.time_updated,6,11) as time_updated
FROM artItems I
    JOIN exhibitorRegionYears eRY ON (eRY.id=I.exhibitorRegionYearId)
    JOIN exhibitorYears eY ON eY.id = eRY.exhibitorYearId
    JOIN exhibitors e ON e.id=eY.exhibitorId
WHERE eRY.exhibitorNumber=? AND eY.conid=?
EOS;
$itemI = 'ii';
$itemP = array($artist, $conid);

if($item == '') {
    $itemQ .= ";";
} else {
    $itemQ .= " AND I.item_key=?;";
    $itemI .= 'i';
    $itemP += $item;
}

$itemR = dbSafeQuery($itemQ, $itemI, $itemP);
$itemArr = [];
while($newItem = $itemR->fetch_assoc()) {
    $longName = $newItem['exhibitorName'];
    $artistName = $newItem['artistName'];
    if ($artistName != null && $artistName != '' && $artistName != $longName ) {
        $longName .= "($artistName)";
    }
    $newItem['name'] = $longName;
    $itemArr[] = $newItem;
}

$response['items'] = $itemArr;

ajaxSuccess($response);
?>
