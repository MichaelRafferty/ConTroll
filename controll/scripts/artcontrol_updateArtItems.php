<?php

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "art_control";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid= $con['id'];

$region = $_POST['region'];
$tabledata = null;
$response['region'] = $region;

try {
    $tabledata = json_decode($_POST['tabledata'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    $error_log($msg);
    ajaxSuccess($response);
    exit();
}

$response['tabledata'] = $tabledata;

$updateSQL = <<<EOS
UPDATE artItems
SET item_key = ?, location = ?, min_price = ?, original_qty = ?, quantity = ?, sale_price = ?, status = ?, title = ?, 
    type = ?, material = ?, bidder = ?, final_price = ?, notes = ?
WHERE id = ?
EOS;

$updateTypes = "issiisssssiisi";

$updated = 0;
$new = 0;
$insertSQL = <<<EOS
INSERT INTO artItems (item_key, conid, location, min_price, original_qty, quantity, sale_price, status, title, type, material, 
                      bidder, final_price, notes, exhibitorRegionYearId, updatedBy) VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);                                                                                                        
        
EOS;

$insertTypes = "iisiiiissssiisii";

foreach ($tabledata as $row) {
    if($row['final_price'] == '') {$row['final_price'] = null;}
    if($row['bidder'] == '') {$row['bidder'] = null;}
    if($row['location'] == null) {
        $location = '';
    } else {
        $location = trim($row['location']);
    }
    if($row['min_price'] == '' || $row['min_price'] == 0) {$row['min_price'] = null;}
    if($row['final_price'] == '' || $row['final_price'] == 0) {$row['final_price'] = null;}
    if($row['sale_price'] == '') {$row['sale_price'] = null;}
    if($row['notes'] == '') { $row['notes'] = null;}

    // perform art type based must be null/equal items
    switch($row['type']) {
        case 'art':
            $row['original_qty'] = 1;
            $row['quantity'] = $row['status'] == 'Purchased/Released' ? 0 : 1;
            break;
        case 'print':
            $row['bidder'] = null;
            $row['final_price'] = null;
            $row['min_price'] = $row['sale_price'];
            break;
        case 'nfs':
            $row['bidder'] = null;
            $row['final_price'] = null;
            $row['min_price'] = $row['sale_price'];
            break;
    }

    if(($row['id'] < 0) && ($row['min_price']==null)) { $row['min_price']=$row['sale_price']; }

    $paramarray = array($row['item_key'], $conid, $location, $row['min_price'], $row['original_qty'], $row['quantity'],
        $row['sale_price'], $row['status'] , $row['title'], $row['type'], $row['material'], $row['bidder'], $row['final_price'],
        $row['notes'],
        $row['id']);
    if($row['id'] > 0) {
        $updated += dbSafeCmd($updateSQL, $updateTypes, $paramarray);
    } else {
        array_pop($paramarray);
        $paramarray[] = $row['exhibitorRegionYearId'];

        $maxKey = array('item_key'=>0);
        $maxKeyR = dbSafeQuery("SELECT max(item_key) as item_key FROM artItems WHERE exhibitorRegionYearId=? GROUP BY exhibitorRegionYearId", 'i', array($row['exhibitorRegionYearId']));
        if($maxKeyR->num_rows > 0) { $maxKey = $maxKeyR->fetch_array(); }

        if($row['item_key'] == 0) { $paramarray[0] = $maxKey['item_key']+1; }
        elseif($row['item_key'] <= $maxKey['item_key']) {
            $checkKeyR = dbSafeQuery("SELECT item_key FROM artItems WHERE exhibitorRegionYearId=? and item_key=?", 'ii',
                array($row['exhibitorRegionYearId'], $row['item_key']));
            if($checkKeyR->num_rows >0) { $paramarray[0] = $maxKey['item_key']+1; }
            }

        $response['insert'] = $insertSQL;
        $response['insertArray'] = $paramarray;
        $paramarray[]=$_SESSION['user_perid'];
        $new_index = dbSafeInsert($insertSQL,$insertTypes,$paramarray);
        if($new_index > 0 ) { $new++; }
        }
    }

$response['status'] = "$updated items Updated $new items Inserted";

// refetch the updated data with all changed fields and keys
$artQ = <<<EOS
SELECT I.id, I.exhibitorRegionYearId, I.item_key, I.title, I.type, I.status, I.location, I.quantity, I.original_qty, 
       I.min_price, I.sale_price, I.final_price, I.bidder, I.material, I.notes, ey.id AS exhibitorYearId, ery.exhibitsRegionYearId,
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
$artR->free();

$response['art'] = $items;

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
$artistR = dbSafeQuery($artistQ, 'ii', array($conid, $region));

$artists=array();

while($artist = $artistR->fetch_assoc()) {
    $artists[] = $artist;
}
$artistR->free();

$response['artists'] = $artists;

ajaxSuccess($response);
