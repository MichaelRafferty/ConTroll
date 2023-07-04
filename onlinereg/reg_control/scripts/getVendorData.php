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
       vs.name as spaceName, vs.requested_units, vs.requested_code, vs.requested_description, vs.approved_units, vs.approved_code, vs.approved_description, vs.purchased_units, vs.purchased_code, vs.purchased_description
FROM vw_VendorSpace vs
JOIN vendors v ON (vs.vendorId = v.id)
WHERE vs.conid = ?
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

ajaxSuccess($response);
?>
