<?php
global $db_ini;

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = "vendor";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];

$vendorQ = "SELECT * from vendors;";
$vendorR = dbQuery($vendorQ);
if(!$vendorR) {
  ajaxSuccess(array(
    "args"=>$_POST,
    "query"=>$vendorQ,
    "error"=>"query failed"));
  exit();
}

$vendors = array();
while ($vendorL = fetch_safe_assoc($vendorR)) {
    $vendors[] = $vendorL;
}

$response['vendors'] = $vendors;

// Summary of spaces available and their current status
$spaces = array();
$spaceQ = <<<EOS
SELECT spaceId, v.name, IFNULL(SUM(requested_units), 0) AS requested, IFNULL(SUM(approved_units),0) AS approved, IFNULL(SUM(purchased_units),0) AS purchased,
    IFNULL(SUM(CASE WHEN approved_units IS NULL THEN requested_units ELSE 0 END), 0) AS new,
    IFNULL(SUM(CASE WHEN purchased_units IS NULL THEN approved_units ELSE 0 END), 0) AS pending,
    unitsAvailable
FROM vw_VendorSpace v
JOIN vendorSpaces vs on (v.spaceId = vs.id AND v.conid = vs.conid)
WHERE v.conid=?
GROUP BY spaceid, name, unitsAvailable
EOS;
$spaceR = dbSafeQuery($spaceQ, 'i', array($conid));
if (!$spaceR) {
    ajaxSuccess(array(
        'args' => $_POST,
        'query' => $spaceQ,
        'error' => 'query failed'));
    exit();
}

while($spaceLine = fetch_safe_assoc($spaceR)) {
    $spaces[] = $spaceLine;
}

$response['summary'] = $spaces;

// detail of space for each vendor
$details = array();
$detailQ = <<<EOS
SELECT vs.id, vendorId, v.name as vendorName, v.website, v.email,
       vs.spaceId, vs.name as spaceName, vs.item_requested, vs.time_requested, vs.requested_units, vs.requested_code, vs.requested_description,
       vs.item_approved, vs.time_approved, vs.approved_units, vs.approved_code, vs.approved_description,
       vs.item_purchased, vs.time_purchased, vs.purchased_units, vs.purchased_code, vs.purchased_description
FROM vw_VendorSpace vs
JOIN vendors v ON (vs.vendorId = v.id)
WHERE vs.conid = ? AND vs.requested_units IS NOT NULL
ORDER BY v.name, vs.name
EOS;

$detailR = dbSafeQuery($detailQ, 'i', array($conid));
if (!$detailR) {
    ajaxSuccess(array(
        'args' => $_POST,
        'query' => $detailQ,
        'error' => 'query failed'));
    exit();
}

while($detailL = fetch_safe_assoc($detailR)) {
    $details[] = $detailL;
}

$response['detail'] = $details;

// build option lists for each space
//
$dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

$priceR = dbQuery('SELECT id, spaceId, code, description, units, price, requestable FROM vendorSpacePrices ORDER BY spaceId, price;');
$price_list = array();
$currentSpaceId = -999;
$prices='';
while ($price = fetch_safe_assoc($priceR)) {
    if ($price['spaceId'] != $currentSpaceId) {
        if ($currentSpaceId != -999) {
            $price_list[$currentSpaceId] = $prices;
        }
        $currentSpaceId = $price['spaceId'];
        $prices = '';
    }
    $prices .= "<option value='" . $price['id'] . "'>(" . $price['units'] . ' units) ' . $price['description'] . ' for ' . $dolfmt->formatCurrency($price['price'], 'USD') . "</option>\n";
}
if ($prices != '')
    $price_list[$currentSpaceId] = $prices;


$response['price_list'] = $price_list;

ajaxSuccess($response);
?>
