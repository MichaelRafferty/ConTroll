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
INSERT INTO artItems (item_key, location, min_price, original_qty, quantity, sale_price, status, title, type, material, 
                      bidder, final_price, notes, exhibitorRegionYearId, updatedBy) VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);                                                                                                            
EOS;

$insertTypes = "isiiiissssiisii";


foreach ($tabledata as $row) {
    if($row['final_price'] == '') {$row['final_price'] = null;}
    if($row['bidder'] == '') {$row['bidder'] = null;}
    if($row['location'] == '') {$row['location'] = null;}
    if($row['min_price'] == '') {$row['min_price'] = null;}
    if($row['sale_price'] == '') {$row['sale_price'] = null;}
    if($row['notes'] == '') { $row['notes'] = null;}

    if(($row['id'] < 0) && ($row['min_price']==null)) { $row['min_price']=$row['sale_price']; }

    $paramarray = array($row['item_key'], $row['location'], $row['min_price'], $row['original_qty'], $row['quantity'],
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

if($new > 0) {

}

ajaxSuccess($response);
