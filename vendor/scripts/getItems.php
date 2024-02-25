<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$vendor_conf = get_conf('vendor');
$debug = get_conf('debug');
$ini = get_conf('reg');

$condata = get_con();
$vendor = '';

$response = array('post' => $_POST, 'get' => $_GET);

$region = $_POST['region']; // TODO error checking
$getType = $_POST['gettype'];

$vendor = $_SESSION['id'];
$vendor_year = $_SESSION['eyID'];
$response['vendor'] = $vendor;
$response['vendor_year'] = $vendor_year;
if($vendor == false) {
    $response['status'] = 'error';
    $response['error'] = 'no vendor found';
    ajaxSuccess($response);
    exit();
}

$itemQ = <<<EOS
SELECT i.id, item_key, title, material, type, original_qty, min_price, sale_price 
FROM artItems i
    JOIN exhibitorRegionYears eRY on eRY.id=i.vendor_show
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

ajaxSuccess($response);
?>
