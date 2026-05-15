<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'exhibitor';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('region', $_POST) && array_key_exists('regionId', $_POST))) {
    $response['error'] = 'Calling Sequence Error';
    ajaxSuccess($response);
    exit();
}

$conid = getConfValue('con', 'id');
$region = $_POST['region'];
$regionId = $_POST['regionId'];
if (array_key_exists('exhibitorConid', $_POST))
    $exhibitorConid = $_POST['exhibitorConid'];
else
    $exhibitorConid = $conid;

$currency = getConfValue('con', 'currency', 'USD');

$exhibitorQ = <<<EOS
WITH rNames AS (
	select ery.id, CONCAT(er.id, '-', er.name) AS region
	from exhibitsRegions er
	join exhibitsRegionYears ery on ery.exhibitsRegion = er.id
	where ery.conid = ?
), eSpaces AS (
	SELECT e.id, eY.id AS yid, eRY.id AS ryid, eS.id AS sid, eRY.exhibitsRegionYearId
	FROM exhibitors e
	JOIN exhibitorYears eY ON e.id = eY.exhibitorId
	JOIN exhibitorRegionYears eRY ON eRY.exhibitorYearId = eY.id
	JOIN exhibitorSpaces eS on eS.exhibitorRegionYear = eRY.id
	WHERE eY.conid = ? AND (IFNULL(eS.item_requested, 0) > 0 OR IFNULL(eS.item_approved, 0) > 0 OR IFNULL(eS.item_purchased, 0) > 0)
), srNames AS (
	SELECT DISTINCT e.id, r.id AS RYID, r.region
    FROM eSpaces e
    JOIN rNames r ON e.exhibitsRegionYearId = r.id
), regions AS (
	SELECT id, GROUP_CONCAT(region ORDER BY region SEPARATOR '<BR/>') AS regions
	FROM srNames
	GROUP BY id
)
SELECT e.id as exhibitorId, perid, exhibitorName, exhibitorEmail, exhibitorPhone, salesTaxId, website, description, password, publicity, 
       addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, archived,
       artistName, artistPayee, IFNULL(e.notes, '') AS exhNotes, eY.id as exhibitorYearId, conid, contactName, contactEmail, contactPhone, contactPassword,
       mailin, IFNULL(eY.notes, '') AS contactNotes,
       CASE WHEN IFNULL(artistName, '') = '' THEN exhibitorName ELSE CONCAT_WS('<BR/>', exhibitorName, artistName) END AS fullExhName, r.regions
FROM exhibitors e
JOIN exhibitorYears eY ON e.id = eY.exhibitorId
JOIN regions r ON e.id = r.id
WHERE eY.conid = ?;
EOS;

$exhibitorR = dbSafeQuery($exhibitorQ, 'iii', array($exhibitorConid, $exhibitorConid, $exhibitorConid));
if (!$exhibitorR) {
    ajaxSuccess(array(
        "args" => $_POST,
        "query" => $exhibitorQ,
        "error" => "query failed"));
    exit();
}

$exhibitors = array();
while ($exhibitorL = $exhibitorR->fetch_assoc()) {
    $fullAddress = $exhibitorL['addr'];
    if ($exhibitorL['addr2'] != null && $exhibitorL['addr2'] != '')
        $fullAddress .= "<br/>" . $exhibitorL['addr2'];
    if ($exhibitorL['city'] != null && $exhibitorL['city'] != '')
        $fullAddress .= '<br/>' . $exhibitorL['city'] . ', ';
    if ($exhibitorL['state'] != null && $exhibitorL['state'] != '')
        $fullAddress .= $exhibitorL['state'] . ' ';
    if ($exhibitorL['zip'] != null && $exhibitorL['zip'] != '')
        $fullAddress .= $exhibitorL['zip'];
    $exhibitorL['fullAddress'] = $fullAddress;

    $contact = '';
    if ($exhibitorL['contactName'] != null && $exhibitorL['contactName'] != '')
        $contact = $exhibitorL['contactName'];
    if ($exhibitorL['contactEmail'] != null && $exhibitorL['contactEmail'] != '') {
        if ($contact != '')
            $contact .= "<br/>";
        $contact .= $exhibitorL['contactEmail'];
    }
    if ($exhibitorL['contactPhone'] != null && $exhibitorL['contactPhone'] != '') {
        if ($contact != '')
            $contact .= '<br/>';
        $contact .= $exhibitorL['contactPhone'];
    }
    $exhibitorL['contact'] = $contact;
    $exhibitors[] = $exhibitorL;
}
$exhibitorR->free();

$response['exhibitors'] = $exhibitors;

// get the region type fields to know what kind of info is required
$typesQ = <<<EOS
SELECT et.regionType, et.portalType, et.usesInventory, ery.id AS exhibitsRegionYearId
FROM exhibitsRegions er
JOIN exhibitsRegionTypes et ON er.regionType = et.regionType
JOIN exhibitsRegionYears ery ON er.id = ery.exhibitsRegion
WHERE er.id = ? AND ery.conid = ?;
EOS;
$typeR = dbSafeQuery($typesQ, 'ii', array($regionId, $exhibitorConid));
if ($typeR === false) {
    ajaxSuccess(array(
        'args' => $_POST,
        'query' => $typesQ,
        'error' => 'query failed'));
    exit();
}
$typeL = $typeR->fetch_assoc();
$response['portalType'] = $typeL['portalType'];
$response['regionType'] = $typeL['regionType'];
$response['usesInventory'] = $typeL['usesInventory'];
$response['exhibitsRegionYearId'] = $typeL['exhibitsRegionYearId'];
$typeR->free();

// get approvals for this region
$approvalQ = <<<EOS
SELECT exRY.id, eY.exhibitorId, exRY.exhibitsRegionYearId, exRY.approval, exRY.updateDate, exRY.updateBy, eR.name, eR.shortname, 
       e.exhibitorName, e.artistName, e.artistPayee, e.exhibitorEmail, e.website,
       COUNT(exS.item_approved) + COUNT(exS.item_requested) + COUNT(exS.item_purchased) AS used,
       CASE WHEN IFNULL(artistName, '') = '' THEN exhibitorName ELSE CONCAT_WS('<BR/>', exhibitorName, artistName) END AS fullExhName
FROM exhibitorRegionYears exRY
JOIN exhibitsRegionYears eRY ON exRY.exhibitsRegionYearId = eRY.id
JOIN exhibitsRegions eR on eRY.exhibitsRegion = eR.id
JOIN exhibitorYears eY on exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.exhibitsRegionYear = eRY.id
LEFT JOIN exhibitorSpaces exS ON es.id = exS.spaceId AND exS.exhibitorRegionYear = exRY.id
JOIN exhibitors e ON eY.exhibitorId = e.id
WHERE eRY.exhibitsRegion = ? and eRY.conid = ? AND exRY.approval != 'none'
GROUP BY exRY.id, eY.exhibitorId, exRY.exhibitsRegionYearId, exRY.approval, exRY.updateDate, exRY.updateBy, eR.name, eR.shortname,
         e.exhibitorName, e.artistName, e.artistPayee, e.exhibitorEmail, e.website;
EOS;

$approvalR = dbSafeQuery($approvalQ, 'si', array($regionId, $exhibitorConid));
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
    } else {
        $approvalData['b1'] = time();
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

$spaceR = dbSafeQuery($spaceQ, 'ii', array($exhibitorConid, $regionId));
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
SELECT e.id, e.exhibitorName, e.artistName, e.artistPayee, e.website, e.exhibitorEmail, exRY.exhibitorNumber, exRY.agentRequest,
    TRIM(CONCAT(p.first_name, ' ', p.last_name)) as pName, TRIM(CONCAT(n.first_name, ' ', n.last_name)) AS nName, 
    CASE WHEN IFNULL(artistName, '') = '' THEN exhibitorName ELSE CONCAT_WS('<BR/>', exhibitorName, artistName) END AS fullExhName,
    eY.id AS exhibitorYearId, exRY.locations, exRY.id AS exhibitorRegionYearId,
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
GROUP BY e.id, e.exhibitorName, e.artistName, e.artistPayee, e.website, e.exhibitorEmail, exRY.exhibitorNumber, exRY.agentRequest, pName, nName,
    eY.id, exRY.locations, exRY.id
), invC AS (
	SELECT exh.exhibitorRegionYearId AS id, COUNT(a.id) AS invCount
    FROM exh
    LEFT OUTER JOIN artItems a ON (a.exhibitorRegionYearId = exh.exhibitorRegionYearId)
	GROUP BY exh.exhibitorRegionYearId
)
SELECT xS.id, xS.exhibitorId, exh.exhibitorName, exh.artistName, exh.website, exh.exhibitorEmail,
    xS.spaceId, xS.name as spaceName, xS.item_requested, xS.time_requested, xS.requested_units, xS.requested_code, xS.requested_description,
    xS.item_approved, xS.time_approved, xS.approved_units, xS.approved_code, xS.approved_description,
    xS.item_purchased, xS.time_purchased, xS.purchased_units, xS.purchased_code, xS.purchased_description, xS.transid, xS.shortname,
    eRY.id AS exhibitsRegionYearId, eRY.exhibitsRegion AS regionId, eRY.ownerName, eRY.ownerEmail, eR.name AS regionName, 
    exh.exhibitorNumber, exh.exhibitorYearId, exh.locations,
    IFNULL(pName, nName) as agentName, invC.invCount, exh.exhibitorRegionYearId, eT.mailInAllowed,
    CASE WHEN IFNULL(artistName, '') = '' THEN exhibitorName ELSE CONCAT_WS('<BR/>', exhibitorName, artistName) END AS fullExhName
FROM vw_ExhibitorSpace xS
JOIN exhibitsSpaces eS ON xS.spaceId = eS.id
JOIN exhibitsRegionYears eRY ON eS.exhibitsRegionYear = eRY.id
JOIN exhibitsRegions eR ON eR.id = eRY.exhibitsRegion
JOIN exhibitsRegionTypes eT ON (eT.regionType = eR.RegionType)
JOIN exh ON (xS.exhibitorId = exh.id)
LEFT OUTER JOIN  invC ON (exh.exhibitorRegionYearId = invC.id)
WHERE eRY.conid=? AND eRY.exhibitsRegion = ? AND (IFNULL(requested_units, 0) > 0 OR IFNULL(approved_units, 0) > 0)
ORDER BY xS.exhibitorId, spaceId;
EOS;

$detailR = dbSafeQuery($detailQ, 'iiii',  array($exhibitorConid, $regionId, $exhibitorConid, $regionId));
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

$priceR = dbSafeQuery($priceQ, 'ii', array($exhibitorConid, $regionId));
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
    $prices .= "<option value='" . $price['id'] . "'>(" . $price['units'] . ' units) ' . $price['description'] . ' for ' . $dolfmt->formatCurrency($price['price'], $currency) . "</option>\n";
}
if ($prices != '')
    $price_list[$currentSpaceId] = $prices;

// get all locations in use
$locationQ = <<<EOS
SELECT exRY.locations
FROM exhibitorRegionYears exRY
JOIN exhibitsRegionYears eRY ON exRY.exhibitsRegionYearId = eRY.id
WHERE locations IS NOT NULL AND locations != '' AND exhibitsRegion = ? AND conid = ?;
EOS;
$locationR = dbSafeQuery($locationQ, 'ii', array($regionId, $exhibitorConid));
$locationsUsed = '';
if ($locationR !== false) {
    while ($locationL = $locationR->fetch_assoc()) {
        $locationsUsed .= ',' . $locationL['locations'];
    }
}

if (strlen($locationsUsed) > 1) {
    $locs = substr($locationsUsed, 1);
    $locs = explode(',', $locs);
    for ($i = 0; $i < count($locs); $i++) {
        $locs[$i] = trim($locs[$i]);
    }
    natsort($locs);
    $locationsUsed = [];
    // php just changed the array next pointers, internally, to get the array back as index 0 ... index n, you need to copy it over
    foreach ($locs as $loc)
        $locationsUsed[] = $loc;
} else {
    $locationsUsed = [];
}
$response['locationsUsed'] = $locationsUsed;
$response['price_list'] = $price_list;

ajaxSuccess($response);
