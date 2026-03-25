<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET, "session" => getAllSessionVars());

$con = get_con();
$conid=$con['id'];
$perm='artinventory';
$response = array();
$response['conid'] = $conid;
$response['perm'] = $perm;

$check_auth = check_atcon($perm, $conid);
if($check_auth == false) { 
    ajaxSuccess(array('error' => "Authentication Failure"));
    exit();
}

// data: { type: type, item: item, quantity: quantity, print: print, },
$pollItem = null;
if (array_key_exists('pollitem', $_POST)) {
    $pollItem = $_POST['pollitem'];
    $response['pollitem'] = $pollItem;
// given a poll item get it's values
    $pQ = <<<EOS
SELECT i.*, eRY.exhibitorNumber
FROM artItems i 
JOIN exhibitorRegionYears eRY ON i.exhibitorRegionYearId = eRY.id
WHERE i.id = ?;
EOS;
    $pR = dbSafeQuery($pQ, 'i', array ($pollItem));
    $response['numRows'] = $pR->num_rows;
    if ($pR->num_rows == 1) {
        $pollItem = $pR->fetch_assoc();
    }
    $pR->free();
    $response['item'] = $pollItem;
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('type', $_POST) && array_key_exists('item', $_POST) &&
    array_key_exists('quantity', $_POST) && array_key_exists('print', $_POST))) {
    ajaxSuccess(array ('error' => 'Parameter Error'));
    exit();
}

$type = $_POST['type'];
$item = $_POST['item'];
$quantity = $_POST['quantity'];
$print = $_POST['print'];

if ($type == 'bid' && !(array_key_exists('bid', $_POST) && array_key_exists('toAuction', $_POST) && array_key_exists('bidder', $_POST))) {
    ajaxSuccess(array ('error' => 'Parameter Error'));
    exit();
}
$bid = $_POST['bid'];
$bidder = $_POST['bidder'];
$toAuction = $_POST['toAuction'];

// check validity of inputs
// 1. check to see if the item exists and get it's statis and current bid/quantity
$cQ = <<<EOS
SELECT i.id, item_key, title, type, status, quantity, original_qty, min_price, final_price, bidder, conid, eRY.exhibitorNumber
FROM artItems i
JOIN exhibitorRegionYears eRY ON i.exhibitorRegionYearId = eRY.id
WHERE i.id = ?;
EOS;
$cR = dbSafeQuery($cQ, 'i', array($item));
if ($cR->num_rows != 1) {
    ajaxSuccess(array ('error' => 'Scan code not found'));
    exit();
}
$curItem = $cR->fetch_assoc();
$cR->free();

if ($curItem['conid'] != $conid) {
    ajaxSuccess(array ('error' => "The scan code ($item) for artist " . $curItem['exhibitorNumber'] . ' titled ' .
        $curItem['title'] . " is from conid " . $curItem['conid'] . ", not the current conid, $conid"));
    exit();
}

$update = false;

// 2. check if the status is correct for the inventory type
switch ($type) {
    case 'checkin':
        if ($curItem['status'] == 'Checked In') {
            $update = true;
            $valid = true;
        } else
            $valid = $curItem['status'] == 'Entered';
        break;
    case 'bid':
        $valid = str_contains(',Entered,Checked In,BID,To Auction,', $curItem['status']);
        break;
    case 'checkout':
        $valid = str_contains(',Withdrawn,Entered,Checked In,Removed from Show,', $curItem['status']);
        break;
    default:
        ajaxSuccess(array ('error' => 'Invalid inventory mode'));
        exit();
}

if (!$valid) {
    ajaxSuccess(array ('error' => "Item current status of " . $curItem['status'] . " is not valid for this inventory type.<br/>" .
        "If a change is needed on this item (Artist: " . $curItem['exhibitorNumber'] .
        " Title: " . $curItem['title'] . "), please see an administrator."));
    exit();
}

// 3. Validate the quantity or bid amount
switch ($type) {
    case 'checkin':
        if ($curItem['type'] == 'print' && $quantity <= 0) {
            ajaxSuccess(array ('error' => 'Received print quantity must be greater than 0'));
            exit();
        }
        $newStatus = 'Checked In';
        break;
    case 'bid':
        if ($curItem['type'] != 'art') {
            ajaxSuccess(array ('error' => "Bids are only allowed on items of type 'Art', this is of type " . $curItem['type']));
            exit();
        }
        $curBid = $curItem['final_price'] != null && $curItem['final_price'] > 0 ? $curItem['final_price'] : $curItem['min_price'];
        if ($bid < $curBid) {
            ajaxSuccess(array ('error' => "Bid of $bid, must be greater than the current bid of " . $curBid));
            exit();
        }
        // now validate the bidder field
        $cQ = <<<EOS
SELECT p.id, count(r.id) AS regs
FROM perinfo p 
LEFT OUTER JOIN reg r ON p.id = r.perid AND r.conid = ?
WHERE p.id = ?
GROUP BY p.id;
EOS;
        $cR = dbSafeQuery($cQ, 'ii', array($conid, $bidder));
        if ($cR->num_rows != 1) {
            ajaxSuccess(array ('error' => "Bidder ID $bidder is not valid"));
            exit();
        }
        $cL = $cR->fetch_assoc();
        $cR->free();
        if ($cL['id'] == null) {
            ajaxSuccess(array('warn' => "Bidder ID $bidder is does not exist"));
            exit();
        }
        if ($cL['regs'] == 0) {
            ajaxSuccess(array('warn' => "Bidder ID $bidder is not registered for this conid"));
            exit();
        }

        break;
    case 'checkout':
        if ($curItem['type'] == 'print' && $quantity < 0) {
            ajaxSuccess(array ('error' => 'Returned print quantity must be greater than or equal to 0'));
            exit();
        }
        break;
}

// perform the update
switch ($type) {
    case 'checkin':
        $uQ = <<<EOS
UPDATE artItems
SET status = 'Checked In', original_qty = ?, quantity = ?
WHERE id = ?;
EOS;
        if ($curItem['type'] != 'print') {
            $quantity = 1;
        }
        $numRows = dbSafeCmd($uQ, 'iii', array($quantity, $quantity, $item));
        if ($numRows == 1) {
            $response['message'] = "$item (" . $curItem['title'] . ") changed to Checked In with received (original) quantity $quantity";
        } else {
            $response['warn'] = 'Nothing changed.';
        }
        break;
    case 'bid':
            $uQ = <<<EOS
UPDATE artItems
SET status = ?, bidder = ?, final_price = ?
WHERE id = ?;
EOS;
        $status = $toAuction == 'Y' ? 'To Auction' : 'BID';
        $numRows = dbSafeCmd($uQ, 'sidi', array($status, $bidder, $bid, $item));
        if ($numRows == 1) {
            $response['message'] = "$item (" . $curItem['title'] . ") bid updated to $bid by $bidder and is now in status $status";
        } else {
            $response['warn'] = "Nothing changed.";
        }
        break;
    case 'checkout':
        $uQ = <<<EOS
UPDATE artItems
SET status = 'Checked Out', quantity = ?
WHERE id = ?;
EOS;
        if ($curItem['type'] != 'print') {
            $quantity = 1;
        }
        $numRows = dbSafeCmd($uQ, 'ii', array($quantity, $item));
        if ($numRows == 1) {
            $response['message'] = "$item (" . $curItem['title'] . ") changed to Checked Out with returned quantity $quantity";
        } else {
            $response['warn'] = 'Nothing changed.';
        }
        break;
}

ajaxSuccess($response);
