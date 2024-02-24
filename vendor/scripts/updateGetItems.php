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
$in_session = false;
$forcePassword = false;
$regserver = $ini['server'];
$vendor = '';

$response = array('post' => $_POST, 'get' => $_GET);

$region = $_POST['region']; // TODO error checking
$itemType = $_POST['itemType'];

$vendor = $_SESSION['id'];
$vendor_year = $_SESSION['cID'];
$response['vendor'] = $vendor;
$response['vendor_year'] = $vendor_year;

if($vendor == false) {
    $response['status'] = 'error';
    $response['error'] = 'no vendor found';
    ajaxSuccess($response);
    exit();
}

$data = [];
if(in_array($itemType, ['art', 'print', 'nfs']) {
    try {
        $data = json_decode($_POST['tabledata'], true, 512, JSON_THROW_ON_ERROR)
    } catch (Exception $e) {
        $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
        $response['error'] = $msg;
        error_log($msg);
        ajaxSuccess($response);
        exit();
    }
} else {
    $response['error'] = "Invalid Data";
    error_log("Unsupported item type: $itemType");
    ajaxSuccess($response);
    exit();
}

$maxQ = <<<EOS
SELECT max(item_key) as last_key
FROM artItems i
    JOIN exhibitorRegionYears eRY on eRY.id=i.vendor_show
WHERE eRY.exhibitorYearId=? and eRY.exhibitsRegionYearId=?
GROUP BY eRY.exhibitorYearId, eRY.exhibitsRegionYearId;
EOS;

$maxL = "ii";
$maxA = array($vendor_year, $region);

$maxR = dbSafeQuery($maxQ, $maxL, $maxA)->fetch_assoc();
$nextItemKey = 0;
if($maxR == null) { $nextItemKey = 1; } 
else { $nextItemKey = $maxR['last_key'] + 1; }

$response['nextItemKey'] = $nextItemKey;

foreach ($data as $index => $row ) {
    if (array_key_exists('to_delete', $row) && $row['to_delete'] == 1) {
        $delete_keys .= ($first ? "'" : ",'") . sql_safe($row[$keyfield]) . "'";
        $first = false;
    } else {
        // trim all fields
        foreach ($row as $field => $value) {
            if ($value != null) {
                $data[$index][$field] = trim($value);
            }
        }
    }
}

/*
if($delete_keys != '') {
    $delsql = "DELETE FROM artItems WHERE id in ( $delete_keys );" 
    web_error_log("Delete sql = /$delsql/");
    $deleted += dbCmd($delsql);
}
*/

$inssql = <<<EOS
INSERT INTO artItems (item_key, title, material, type, original_qty, min_price, sale_price) 
VALUES (?, ?, ?, ?, ?, ?, ?);
EOS;
$updsql = <<<EOS
UPDATE artItems SET item_key=?, title=?, material=?, original_qty=?, min_price=?, sale_price=? 
WHERE id=?
EOS;

foreach ($data as $index => $row) { 
    if (array_key_exists('to_delete', $row)) {
        if ($row['to_delete'] == 1) continue;
    }
    $title = 'Unknown';
    if(array_key_exists('title', $row) {
        $title = $row['title']
    }
    $material = null;
    if(array_key_exists('material', $row)) {
        $material = $row['material'];
    }
    if(array_key_exists('id', $row)) { // update
    } else { // new!
    }
}


$itemQ = <<<EOS
SELECT i.id, item_key, title, material, type, original_qty, min_price, sale_price
FROM artItems i
    JOIN exhibitorRegionYears eRY on eRY.id=i.vendor_show
WHERE eRY.exhibitorYearId=? and eRY.exhibitsRegionYearId=?
EOS;

$itemL = 'ii';
$itemA = array($vendor, $region);

switch($itemType) {
    case 'art':
    case 'print':
    case 'nfs':
        $itemQ .= " and i.type=?";
        $itemL .= 's';
        $itemA[] = $itemType;
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
