<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET, "session" => $_SESSION);

$con = get_con();
$conid=$con['id'];
$perm='artinventory';
$response['conid'] = $conid;
$response['perm'] = $perm;

$check_auth = check_atcon($_SESSION['user'], $_SESSION['passwd'], $perm, $conid);
if($check_auth == false) { 
    ajaxSuccess(array('error' => "Authentication Failure"));
}

if(!isset($_POST['actions'])) {
    ajaxSuccess(array('error' => "No Actions"));
}
$actions = json_decode($_POST['actions']);
$response = array();
$response['actions'] = $actions;
$response['log'] = array();

foreach ($actions as $action) {
    $action = (array)$action;
    $item = explode("-", $action['item']); 
    $log = $action['action'] . " " . $item[0] . " - " . $item[1];
    switch($action['action']) {
        case 'Check In':
            $checkInQ = <<<EOS
UPDATE artItems I 
JOIN artshow S on S.id=I.artshow
SET status='Checked In' 
WHERE I.item_key=? and I.conid=? and S.art_key=?;
EOS;
            $checkInR = dbSafeCmd($checkInQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkInR";
            break;
        case 'Set Location':
            $log .= " to " . $action['value'];
            $locationQ = <<<EOS
UPDATE artItems I 
JOIN artshow S on S.id=I.artshow
SET I.location=?
WHERE I.item_key=? and I.conid=? and S.art_key=?;
EOS;
            $locationR = dbSafeCmd($locationQ, 'siii', array($action['value'],$item[1], $conid, $item[0]));
            $log .= " changed $locationR";
            break;
        case 'Inventory':
            $inventoryQ = <<<EOS
UPDATE artItems I 
JOIN artshow S on S.id=I.artshow
SET I.time_updated=current_timestamp()
WHERE I.item_key=? and I.conid=? and S.art_key=?;
EOS;
            $inventoryR = dbSafeCmd($inventoryQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $inventoryR";
            break;
        case 'Check Out':
            $checkOutQ = <<<EOS
UPDATE artItems I 
JOIN artshow S on S.id=I.artshow
SET I.status='Checked Out'
WHERE I.item_key=? and I.conid=? and S.art_key=?;
EOS;
            $checkOutR = dbSafeCmd($checkOutQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkOutR";
            break;
        case 'Set Bidder':
            $log .= " to " . $action['value'];
            $bidQ =<<<EOS
UPDATE artItems I
JOIN artshow S on S.id=I.artshow
SET I.status='Bid', I.bidder=?
WHERE I.item_key=? and I.conid=? and S.art_key=?;
EOS;
            $bidR = dbSafeCmd($bidQ, 'iiii', array($action['value'], $item[1], $conid, $item[0]));
            $log .= " changed $bidR";
            break;
        case 'Set Bid': // currently not enforcing bid values
            $log .= " to " . $action['value'];
            $bidQ =<<<EOS
UPDATE artItems I
JOIN artshow S on S.id=I.artshow
SET I.status='Bid', I.final_price=?
WHERE I.item_key=? and I.conid=? and S.art_key=?;
EOS;
            $bidR = dbSafeCmd($bidQ, 'iiii', array($action['value'], $item[1], $conid, $item[0]));
            break;
        case 'Sell To Bidsheet':
            $checkInQ = <<<EOS
UPDATE artItems I 
JOIN artshow S on S.id=I.artshow
SET status='Sold Bid Sheet' 
WHERE I.item_key=? and I.conid=? and S.art_key=?;
EOS;
            $checkInR = dbSafeCmd($checkInQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkInR";
            break;
        case 'Send To Auction':
            $checkInQ = <<<EOS
UPDATE artItems I 
JOIN artshow S on S.id=I.artshow
SET status='To Auction' 
WHERE I.item_key=? and I.conid=? and S.art_key=?;
EOS;
            $checkInR = dbSafeCmd($checkInQ, 'iii', array($item[1], $conid, $item[0]));
            $log .= " changed $checkInR";
            break;
        case 'Release':
            $checkInQ = <<<EOS
UPDATE artItems I 
JOIN artshow S on S.id=I.artshow
SET status='purchased/released' 
WHERE I.item_key=? and I.conid=? and S.art_key=?;
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
?>
