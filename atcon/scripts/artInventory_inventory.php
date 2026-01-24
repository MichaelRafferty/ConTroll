<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET, "session" => getAllSessionVars());

$con = get_con();
$conid=$con['id'];
$perm='artinventory';
$response['conid'] = $conid;
$response['perm'] = $perm;

$check_auth = check_atcon($perm, $conid);
if($check_auth == false) { 
    ajaxSuccess(array('error' => "Authentication Failure"));
}

if(!isset($_POST['actions'])) {
    ajaxSuccess(array('error' => "No Actions"));
}
$actions = json_decode($_POST['actions'], true);
$response = array();
$response['actions'] = $actions;
$response['log'] = array();

function clearAbandonedSale($item_key, $exhibitorNumber) {
    global $conid, $response;
    $numrows = 0;
    $artid = 0;
    $checkQ = <<<EOS
SELECT I.id, I.type 
FROM artItems I 
    JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
WHERE item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
    $checkR = dbSafeQuery($checkQ, 'iii', array($item_key, $conid, $exhibitorNumber));
    if($checkR->num_rows > 0) {
        $checkA = $checkR->fetch_assoc();
        if($checkA['type'] != 'art') {
            $type = $checkA['type'];
            //array_push($response['log'], "$item_key, $exhibitorNumber ($conid) cleaning - not art: ");
            return false; // only do this for art
        }
        $artid = $checkA['id'];
        //array_push($response['log'], "$item_key, $exhibitorNumber ($conid) cleaning - artid: $artid");
    } else {
        //array_push($response['log'], "$item_key, $exhibitorNumber ($conid) cleaning - no item");
        return false; // if there are no items with this id then don't
    }

    $cleanCnt = <<<EOS
SELECT id, transid, status, perid, paid FROM artSales where artid=? and paid=0 and amount>0;
EOS;
$cleanR = dbSafeQuery($cleanCnt, 'i', array($artid));
if($cleanR->num_rows == 0) {
    //array_push($response['log'], "$item_key, $exhibitorNumber ($conid) cleaning - no sales");
    return 0; // no rows to delete
}
else { $numrows = $cleanR->num_rows; }

    $cleanCmd = <<<EOS
DELETE FROM artSales where artid=? and paid=0 and amount > 0;
EOS;
    $countCleaned = dbSafeCmd($cleanCmd, 'i', array($artid));

    if($numrows != $countCleaned) {
        //array_push($response['log'], "$item_key, $exhibitorNumber cleaning - count mismatch $numrows v $countCleaned");
        web_error_log("WARNING: cleanSales: mismatch between count seen and count cleaned");
    }
    array_push($response['log'], "$item_key, $exhibitorNumber cleaning - $countCleaned deleted");
    return $countCleaned;
}


foreach ($actions as $action) {
    $action = (array)$action;
    $item = explode("-", $action['item']); 
    $log = $action['action'] . " " . $item[0] . " - " . $item[1];
    switch($action['action']) {
        case 'Check In':
            $checkInQ = <<<EOS
UPDATE artItems I 
    JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET status='Checked In' 
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $checkInR = dbSafeCmd($checkInQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkInR";
            $clearCount = clearAbandonedSale($item[1], $item[0]);
            if($clearCount>0) { $log .= " cleared $clearCount abandoned sales";}
            break;
        case 'Set Location':
            $location = $action['value'];
            if ($location == null)
                $location = '';
            else
                $location = trim($location);

            $log .= " to $location ";
            $locationQ = <<<EOS
UPDATE artItems I 
JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET I.location=?
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $locationR = dbSafeCmd($locationQ, 'siii', array($location,$item[1], $conid, $item[0]));
            $log .= " changed $locationR";
            break;
        case 'Inventory':
            $inventoryQ = <<<EOS
UPDATE artItems I 
JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET I.time_updated=current_timestamp()
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $inventoryR = dbSafeCmd($inventoryQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $inventoryR";
            break;
        case 'Check Out':
            $checkOutQ = <<<EOS
UPDATE artItems I 
JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET I.status='Checked Out'
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $checkOutR = dbSafeCmd($checkOutQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkOutR";
            $clearCount = clearAbandonedSale($item[1], $item[0]);
            if($clearCount>0) { $log .= " cleared $clearCount abandoned sales";}
            break;
        case 'Set Bidder':
            $log .= " to " . $action['value'];
            $bidQ =<<<EOS
UPDATE artItems I
JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET I.status='BID', I.bidder=?
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $bidR = dbSafeCmd($bidQ, 'iiii', array($action['value'], $item[1], $conid, $item[0]));
            $log .= " changed $bidR";
            $clearCount = clearAbandonedSale($item[1], $item[0]);
            if($clearCount>0) { $log .= " cleared $clearCount abandoned sales";}
            break;
        case 'Set Bid': // currently not enforcing bid values
            $log .= " to " . $action['value'];
            $bidQ =<<<EOS
UPDATE artItems I
JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET I.status='BID', I.final_price=?
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $bidR = dbSafeCmd($bidQ, 'iiii', array($action['value'], $item[1], $conid, $item[0]));
            $clearCount = clearAbandonedSale($item[1], $item[0]);
            if($clearCount>0) { $log .= " cleared $clearCount abandoned sales";}
            break;
        case 'Sell To Bidsheet':
            $checkInQ = <<<EOS
UPDATE artItems I 
JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET status='Sold Bid Sheet' 
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $checkInR = dbSafeCmd($checkInQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkInR";
            $clearCount = clearAbandonedSale($item[1], $item[0]);
            if($clearCount>0) { $log .= " cleared $clearCount abandoned sales";}
            break;
        case 'Sell At Auction':
            $checkInQ = <<<EOS
UPDATE artItems I 
JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET status='Sold At Auction' 
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $checkInR = dbSafeCmd($checkInQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkInR";
            $clearCount = clearAbandonedSale($item[1], $item[0]);
            if($clearCount>0) { $log .= " cleared $clearCount abandoned sales";}
            break;
        case 'Send To Auction':
            $checkInQ = <<<EOS
UPDATE artItems I 
JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET status='To Auction' 
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $checkInR = dbSafeCmd($checkInQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkInR";
            $clearCount = clearAbandonedSale($item[1], $item[0]);
            if($clearCount>0) { $log .= " cleared $clearCount abandoned sales";}
            break;
        case 'Release':
            $checkInQ = <<<EOS
UPDATE artItems I 
JOIN exhibitorRegionYears eRY on eRY.id=I.exhibitorRegionYearId
    JOIN exhibitorYears eY on eY.id=eRY.exhibitorYearId
SET status='Purchased/Released' 
WHERE I.item_key=? and eY.conid=? and eRY.exhibitorNumber=?;
EOS;
            $checkInR = dbSafeCmd($checkInQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkInR";
            break;
        default:
            $log .= " => Unknown Action";
    }
    $response['log'][] = $log;
}

ajaxSuccess($response);
