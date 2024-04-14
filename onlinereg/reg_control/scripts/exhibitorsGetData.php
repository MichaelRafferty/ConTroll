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

if (!(array_key_exists('region', $_POST) && array_key_exists('regionId', $_POST))) {
    $response['error'] = 'Calling Sequence Error';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];
$region = $_POST['region'];
$regionId = $_POST['regionId'];

$exhibitorQ = <<<EOS
SELECT e.id as exhibitorId, perid, exhibitorName, exhibitorEmail, exhibitorPhone, website, description, password, publicity, 
       addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, archived,
       eY.id as contactId, conid, contactName, contactEmail, contactPhone, contactPassword, mailin
FROM exhibitors e
JOIN exhibitorYears eY ON e.id = eY.exhibitorId
WHERE eY.conid = ?;
EOS;

$exhibitorR = dbSafeQuery($exhibitorQ, 'i', array($conid));
if (!$exhibitorR) {
    ajaxSuccess(array(
        "args" => $_POST,
        "query" => $exhibitorQ,
        "error" => "query failed"));
    exit();
}

$exhibitors = array();
while ($exhibitorL = $exhibitorR->fetch_assoc()) {
    $exhibitors[] = $exhibitorL;
}
$exhibitorR->free();

$response['exhibitors'] = $exhibitors;

// get approvals for this region
$approvalQ = <<<EOS
SELECT exRY.id, eY.exhibitorId, exRY.exhibitsRegionYearId, exRY.approval, exRY.updateDate, exRY.updateBy, eR.name, eR.shortname, e.exhibitorName, e.exhibitorEmail, e.website,
       COUNT(exS.item_approved) + COUNT(exS.item_requested) + COUNT(exS.item_purchased) AS used
FROM exhibitorRegionYears exRY
JOIN exhibitsRegionYears eRY ON exRY.exhibitsRegionYearId = eRY.id
JOIN exhibitsRegions eR on eRY.exhibitsRegion = eR.id
JOIN exhibitorYears eY on exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.exhibitsRegionYear = eRY.id
JOIN exhibitorSpaces exS ON es.id = exS.spaceId AND exS.exhibitorRegionYear = exRY.id
JOIN exhibitors e ON eY.exhibitorId = e.id
WHERE eRY.exhibitsRegion = ? and eRY.conid = ?
GROUP BY exRY.id, eY.exhibitorId, exRY.exhibitsRegionYearId, exRY.approval, exRY.updateDate, exRY.updateBy, eR.name, eR.shortname, e.exhibitorName, e.exhibitorEmail, e.website;
EOS;

$approvalR = dbSafeQuery($approvalQ, 'si', array($regionId, $conid));
if (!$approvalR) {
    ajaxSuccess(array(
        'args' => $_POST,
        'query' => $approvalQ,
        'error' => 'query failed'));
    exit();
}

$approvals = array();
while ($approvalL = $approvalR->fetch_assoc()) {
    $approvalData = $approvalL;
    if ($approvalData['used'] > 0) {
        $approvalData['b1'] = -1;
        $approvalData['b2'] = -1;
        $approvalData['b3'] = -1;
        $approvalData['b4'] = -1;
    } else {
        $approvalData['b1'] = time();
        $approvalData['b2'] = $approvalData['b1'] + 1;
        $approvalData['b3'] = $approvalData['b2'] + 1;
        $approvalData['b4'] = $approvalData['b3'] + 1;
    }
    $approvals[] = $approvalData;
}
$approvalR->free();
$response['approvals'] = $approvals;

// Get the summary of each space for this region
$spaceQ = <<<EOS
SELECT xS.spaceId, xS.name, IFNULL(SUM(requested_units), 0) AS requested, IFNULL(SUM(approved_units),0) AS approved, IFNULL(SUM(purchased_units),0) AS purchased,
    IFNULL(SUM(CASE WHEN approved_units IS NULL THEN requested_units ELSE 0 END), 0) AS new,
    IFNULL(SUM(CASE WHEN purchased_units IS NULL THEN approved_units ELSE 0 END), 0) AS pending,
    unitsAvailable
FROM vw_ExhibitorSpace xS
JOIN exhibitsSpaces eS ON xS.spaceId = eS.id
JOIN exhibitsRegionYears eRY ON eS.exhibitsRegionYear = eRY.id
WHERE eRY.conid=? AND eRY.exhibitsRegion = ?
GROUP BY xS.spaceId, xS.name, eS.unitsAvailable
EOS;

$spaceR = dbSafeQuery($spaceQ, 'ii', array($conid, $regionId));
if (!$spaceR) {
    ajaxSuccess(array(
        'args' => $_POST,
        'query' => $spaceQ,
        'error' => 'query failed'));
    exit();
}

$spaces = array();
while($spaceLine = $spaceR->fetch_assoc()) {
    $spaces[] = $spaceLine;

}
$spaceR->free();

$response['summary'] = $spaces;

// detail of space for this region
$details = array();
$detailQ = <<<EOS
WITH exh AS (
SELECT e.id, e.exhibitorName, e.website, e.exhibitorEmail, eRY.id AS exhibitorYearId, exRY.exhibitorNumber, exRY.agentRequest,
    TRIM(CONCAT(p.first_name, ' ', p.last_name)) as pName, TRIM(CONCAT(n.first_name, ' ', n.last_name)) AS nName,
	SUM(IFNULL(espr.units, 0)) AS ru, SUM(IFNULL(espa.units, 0)) AS au, SUM(IFNULL(espp.units, 0)) AS pu
FROM exhibitorSpaces eS
LEFT OUTER JOIN exhibitsSpacePrices espr ON (eS.item_requested = espr.id)
LEFT OUTER JOIN exhibitsSpacePrices espa ON (eS.item_approved = espa.id)
LEFT OUTER JOIN exhibitsSpacePrices espp ON (eS.item_purchased = espp.id)
JOIN exhibitorRegionYears exRY ON (exRY.id = eS.exhibitorRegionYear)
JOIN exhibitorYears eY ON (eY.id = exRY.exhibitorYearId)
JOIN exhibitors e ON (e.id = eY.exhibitorId)
JOIN exhibitsSpaces s ON (s.id = eS.spaceId)
JOIN exhibitsRegionYears eRY ON s.exhibitsRegionYear = eRY.id
LEFT OUTER JOIN perinfo p ON p.id = exRY.agentPerid
LEFT OUTER JOIN newperson n ON n.id = exRY.agentNewperson
WHERE eY.conid = ? AND eRY.exhibitsRegion = ?
GROUP BY e.id, e.exhibitorName, e.website, e.exhibitorEmail, eRY.id, exRY.exhibitorNumber, pName, nName, agentRequest
)
SELECT xS.id, xS.exhibitorId, exh.exhibitorName, exh.website, exh.exhibitorEmail,
    xS.spaceId, xS.name as spaceName, xS.item_requested, xS.time_requested, xS.requested_units, xS.requested_code, xS.requested_description,
    xS.item_approved, xS.time_approved, xS.approved_units, xS.approved_code, xS.approved_description,
    xS.item_purchased, xS.time_purchased, xS.purchased_units, xS.purchased_code, xS.purchased_description, xS.transid, xS.shortname,
    eRY.id AS exhibitsRegionYearId, eRY.exhibitsRegion AS regionId, exh.exhibitorNumber,
    IFNULL(pName, nName) as agentName,
    exh.pu * 10000 + exh.au * 100 + exh.ru AS sortOrder
FROM vw_ExhibitorSpace xS
JOIN exhibitsSpaces eS ON xS.spaceId = eS.id
JOIN exhibitsRegionYears eRY ON eS.exhibitsRegionYear = eRY.id
JOIN exh ON (xS.exhibitorId = exh.id)
WHERE eRY.conid=? AND eRY.exhibitsRegion = ? AND (IFNULL(requested_units, 0) > 0 OR IFNULL(approved_units, 0) > 0)
ORDER BY sortOrder, exhibitorName, spaceName
EOS;

$detailR = dbSafeQuery($detailQ, 'iiii',  array($conid, $regionId, $conid, $regionId));
if (!$detailR) {
    ajaxSuccess(array(
        'args' => $_POST,
        'query' => $detailQ,
        'error' => 'query failed'));
    exit();
}

while($detailL = $detailR->fetch_assoc()) {
    $detail = $detailL;
    $detail['b1'] = time();
    $detail['b2'] = $detail['b1'] + 1;
    $detail['b3'] = $detail['b2'] + 1;
    $detail['b4'] = $detail['b3'] + 1;
    $details[] = $detail;
}

$response['detail'] = $details;

// build option lists for each space
//
$curLocale = locale_get_default();
$dolfmt = new NumberFormatter($curLocale == 'en_US_POSIX' ? 'en-us' : $curLocale, NumberFormatter::CURRENCY);
$priceQ = <<<EOS
SELECT eSP.id, eSP.spaceId, eSP.code, eSP.description, eSP.units, eSP.price, eSP.requestable
FROM exhibitsSpacePrices eSP
JOIN exhibitsSpaces eS ON eSP.spaceId = eS.id
JOIN exhibitsRegionYears eRY ON eS.exhibitsRegionYear = eRY.id
WHERE eRY.conid = ? AND eRY.exhibitsRegion = ?
ORDER BY spaceId, price;
EOS;

$priceR = dbSafeQuery($priceQ, 'ii', array($conid, $regionId));
$price_list = array();
$currentSpaceId = -999;
$prices='';
while ($price = $priceR->fetch_assoc()) {
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
