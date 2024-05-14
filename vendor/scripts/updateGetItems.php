<?php
require_once('../lib/base.php');
require_once('../../lib/log.php');

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
$vendor_year = $_SESSION['eyID'];
$response['vendor'] = $vendor;
$response['vendor_year'] = $vendor_year;

if ($vendor == false) {
    $response['status'] = 'error';
    $response['error'] = 'no vendor found';
    ajaxSuccess($response);
    exit();
}

$data = [];
if (in_array($itemType, ['art', 'print', 'nfs'])) {
    try {
        $data = json_decode($_POST['tabledata'], true, 512, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
        $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
        $response['error'] = $msg;
        error_log($msg);
        ajaxSuccess($response);
        exit();
    }
} else {
    $response['error'] = 'Invalid Data';
    error_log("Unsupported item type: $itemType");
    ajaxSuccess($response);
    exit();
}

$eryQ = <<<EOS
SELECT id from exhibitorRegionYears 
WHERE exhibitorYearId=? and exhibitsRegionYearId=?;
EOS;

$eryR = dbSafeQuery($eryQ, 'ii', array($vendor_year, $region));
if ($eryR === false || $eryR->num_rows != 1) {
    error_log("Unable to retrieve exhibitorRegionYears for exhibitor year id " . $vendor_year . " and exhibitsRegionYearId " . $region);
    $response['error'] = 'Unable to retrieve the proper setup data, please seek assistance';
    ajaxSuccess($response);
    exit();
}
$eryL = $eryR->fetch_assoc();
$exhibitorRegionYearId = $eryL['id'];
$eryR->free();

$maxQ = <<<EOS
SELECT max(item_key) as last_key
FROM artItems i
JOIN exhibitorRegionYears eRY on eRY.id=i.exhibitorRegionYearId
WHERE eRY.exhibitorYearId=? and eRY.exhibitsRegionYearId=?
GROUP BY eRY.exhibitorYearId, eRY.exhibitsRegionYearId;
EOS;

$maxL = 'ii';
$maxA = array($vendor_year, $region);

$maxR = dbSafeQuery($maxQ, $maxL, $maxA);
if ($maxR == null || $maxR->num_rows == 0) {
    $nextItemKey = 1;
} else {
    $maxL = $maxR->fetch_assoc();
    $nextItemKey = $maxL['last_key'] + 1;
    $maxR->free();
}

$response['nextItemKey'] = $nextItemKey;
$first = true;
$delete_keys = '';

foreach ($data as $index => $row) {
    if (array_key_exists('to_delete', $row) && $row['to_delete'] == 1 && array_key_exists('id', $row)) {
        $delete_keys .= ($first ? "'" : ",'") . sql_safe($row['id']) . "'";
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

$log = get_conf('log');
logInit($log['artshow']);
logWrite("Start of changes made by exhibitor $vendor for year $vendor_year");
$deleted = 0;
if ($delete_keys != '') {
    $delsql = "DELETE FROM artItems WHERE id in ( $delete_keys );";
    web_error_log("Delete sql = /$delsql/");
    $deleted += dbCmd($delsql);
    logWrite("Deleted $deleted art items: $delete_keys");
}

$inssql = <<<EOS
INSERT INTO artItems (item_key, title, material, type, original_qty, quantity, min_price, sale_price, conid, exhibitorRegionYearId, updatedBy) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 3);
EOS;
$updsql = <<<EOS
UPDATE artItems SET item_key=?, title=?, material=?, original_qty=?, quantity=?, min_price=?, sale_price=?, updatedBy=3 
WHERE id=?
EOS;

$updated = 0;
$inserted = 0;
$data_errors = '';
foreach ($data as $index => $row) { 
    if (array_key_exists('to_delete', $row)) {
        if ($row['to_delete'] == 1) continue;
    }
    $item_key = 0;
    if (array_key_exists('item_key', $row)) {
        $item_key = $row['item_key'];
    }
    $title = 'Unknown';
    if (array_key_exists('title', $row)) {
        $title = $row['title'];
    }
    $material = null;
    if (array_key_exists('material', $row)) {
        $material = $row['material'];
    }
    $qty = 1;
    if (array_key_exists('original_qty', $row)) {
        $qty= $row['original_qty'];
    }

    if (!array_key_exists('sale_price', $row) && ($itemType != 'art')) {
        $data_errors .= "Item: " . $row['item_key'] . ", Sale Price is required<br/>";
        continue; // print and nfs need sale
    }

    if (!array_key_exists('min_price', $row) && ($itemType == 'art')) {
        $data_errors .= "Item: " . $row['item_key'] . ", Min Bid is required<br/>";
        continue; // art need min bid
    } else if (!array_key_exists('min_price', $row)) {
        $row['min_price'] = $row['sale_price'];
    }

    if ($itemType == 'art' && array_key_exists('sale_price', $row) && $row['sale_price'] != null && $row['sale_price'] <= $row['min_price']) {
        $data_errors .= "Item: " . $row['item_key'] . ", Sale Price must be > Min Bid<br/>";
        continue; // invalid
    }

    $sale_price = null;
    $min_price = null;
    if (array_key_exists('sale_price', $row))
        $sale_price = $row['sale_price'];
    if (array_key_exists('min_price', $row))
        $min_price = $row['min_price'];
    if (array_key_exists('id', $row) && ($row['id'] > 0)) { // update
        $typestr = 'issiiddi';
        $vararray = array($item_key, $title, $material, $qty, $qty, $min_price, $sale_price, $row['id']);
        $numrows = dbSafeCmd($updsql, $typestr, $vararray);
        $updated += $numrows;
        logWrite(array($updsql, $typestr, $vararray, $numrows));
    } else { // new!
        $typestr = 'isssiiddii';
        $paramarray =  array($nextItemKey++, $title, $material, $itemType, $qty, $qty, $min_price, $sale_price, $conid, $exhibitorRegionYearId);

        $numrows = dbSafeCmd($inssql, $typestr, $paramarray);
        logWrite(array($inssql, $typestr, $paramarray, $numrows));
        if ($numrows !== false) {
            $inserted++;
        }
    }
}
if ($data_errors != '') {
    $response['warn'] = "$data_errors<BR/>$itemType updated: $inserted added, $updated changed, $deleted removed.";
} else {
    $response['message'] = "$itemType updated: $inserted added, $updated changed, $deleted removed.";
}
logWrite($response);
logWrite("End of changes made by exhibitor $vendor for year $vendor_year");

$itemQ = <<<EOS
SELECT i.id, item_key, title, material, type, original_qty, min_price, sale_price, status, 0 as uses
FROM artItems i
    JOIN exhibitorRegionYears eRY on eRY.id=i.exhibitorRegionYearId
WHERE eRY.exhibitorYearId=? and eRY.exhibitsRegionYearId=?
EOS;

$itemL = 'ii';
$itemA = array($vendor_year, $region);

switch ($itemType) {
    case 'art':
    case 'print':
    case 'nfs':
        $itemQ .= ' and i.type=?';
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
