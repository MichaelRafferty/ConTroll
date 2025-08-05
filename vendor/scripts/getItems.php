<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$ini = get_conf('reg');

$condata = get_con();
$vendor = '';

$response = array('post' => $_POST, 'get' => $_GET);

$region = $_POST['region']; // TODO error checking
$getType = $_POST['gettype'];

$vendor = getSessionVar('id');
$vendor_year = getSessionVar('eyID');
$response['vendor'] = $vendor;
$response['vendor_year'] = $vendor_year;
if($vendor == false) {
    $response['status'] = 'error';
    $response['error'] = 'no vendor found';
    ajaxSuccess($response);
    exit();
}

$itemQ = <<<EOS
SELECT i.id, item_key, title, material, type, original_qty, min_price, sale_price, status, 0 as uses 
FROM artItems i
JOIN exhibitorRegionYears eRY on eRY.id=i.exhibitorRegionYearId
WHERE eRY.exhibitorYearId=? and eRY.exhibitsRegionYearId = ?; 
EOS;

$itemL = 'ii';
$itemA = array($vendor_year, $region);

switch($getType) {
    case 'art':
    case 'print':
    case 'nfs':
        $itemQ .= " and i.type=?";
        $itemL .= 's';
        $itemA[] = $getType;
        break;
    default:
        break;
}

$itemR = dbSafeQuery($itemQ, $itemL, $itemA);

$items = array('art' => array(), 'print' => array(), 'nfs' => array());

while ($item = $itemR->fetch_assoc()) {
    $items[$item['type']][] = $item;
}

$response['items'] = $items;
$response['itemCount'] = $itemR->num_rows;
$itemR->free();

// now get the max item count for this region
$maxQ = <<<EOS
SELECT IFNULL(ert.maxInventory, 999999) AS maxInventory, ery.ownerName, ery.ownerEmail, er.name
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON er.id = ery.exhibitsRegion
JOIN exhibitsRegionTypes ert ON ert.regionType = er.regionType
WHERE ery.id = ?;
EOS;

$maxR = dbSafeQuery($maxQ, 'i', array($region));
if ($maxR === false || $maxR->num_rows != 1) {
    $response['error'] = 'Cannot retrieve max inventory limit, seek assistance';
}

$response['inv'] = $maxR->fetch_assoc();
$maxR->free();

ajaxSuccess($response);
