<?php
global $db_ini;

require_once '../lib/base.php';
$check_auth = google_init('ajax');
$perm = 'exhibitor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('regionId', $_POST) && array_key_exists('exhibitorId', $_POST))) {
    $response['error'] = 'Calling Sequence Error';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];
$regionId = $_POST['regionId'];
$exhibitorId = $_POST['exhibitorId'];



// build the space allocation arrays for this vendor
// build region array
$regionQ = <<<EOS
SELECT ert.portalType, ert.requestApprovalRequired, ert.purchaseApprovalRequired,ert.purchaseAreaTotals,ert.mailInAllowed, ert.mailinMaxUnits, ert.inPersonMaxUnits,
er.name, er.shortname, er.description, er.sortorder,
ery.ownerName, ery.ownerEmail, ery.id, ery.includedMemId, ery.additionalMemId, ery.totalUnitsAvailable, ery.conid, ery.mailinFee,
mi.price AS includedMemPrice, ma.price AS additionalMemPrice
FROM exhibitsRegionTypes ert
JOIN exhibitsRegions er ON er.regionType = ert.regionType
JOIN exhibitsRegionYears ery ON er.id = ery.exhibitsRegion
JOIN memList mi ON (ery.includedMemId = mi.id)
JOIN memList ma ON (ery.additionalMemId = ma.id)
WHERE ery.conid = ? AND er.id = ?
ORDER BY er.sortorder;
EOS;

$regionR = dbSafeQuery($regionQ,'ii',array($conid, $regionId));
$region_list = array(); // forward array, id -> data
$regions = array(); // reverse array, shortname -> id

while ($region = $regionR->fetch_assoc()) {
    $region_list[$region['id']] = $region;
    $regions[$region['shortname']] = $region['id'];
}
$regionR->free();

// build spaces array
$spaceQ = <<<EOS
SELECT es.id, er.shortname as regionShortname, er.name as regionName, es.shortname as spaceShortname, es.name AS spaceName,
       es.description, es.unitsAvailable, es.unitsAvailableMailin, es.exhibitsRegionYear
FROM exhibitsSpaces es
JOIN exhibitsRegionYears ery ON (es.exhibitsRegionYear = ery.id)
JOIN exhibitsRegions er ON (ery.exhibitsRegion = er.id)
JOIN exhibitsRegionTypes ert ON (er.regionType = ert.regionType)
WHERE ery.conid=? AND er.id = ?
ORDER BY es.exhibitsRegionYear, es.sortorder;
EOS;

$spaceR =  dbSafeQuery($spaceQ, 'ii', array($conid, $regionId));
$space_list = array();
$spaces = array();
// output the data for the scripts to use

while ($space = $spaceR->fetch_assoc()) {
    $space_list[$space['exhibitsRegionYear']][$space['id']] = $space;
    $spaces[$space['spaceShortname']] = array( 'region' => $space['exhibitsRegionYear'], 'space' => $space['id'] );
}
$spaceR->free();

// built price lists
foreach ($space_list AS $yearId => $regionYear) {
    foreach ($regionYear as $id => $space) {
        $priceQ = <<<EOS
SELECT p.id, p.spaceId, p.code, p.description, p.units, p.price, p.includedMemberships, p.additionalMemberships, p.requestable, p.sortOrder, es.id AS spaceId, es.exhibitsRegionYear
FROM exhibitsSpacePrices p
JOIN exhibitsSpaces es ON p.spaceId = es.id
WHERE spaceId=?
ORDER BY p.spaceId, p.sortOrder;
EOS;
        $priceR = dbSafeQuery($priceQ, 'i', array($id));
        $price_list = array();
        while ($price = $priceR->fetch_assoc()) {
            $price_list[] = $price;
        }
        $priceR->free();
        $space_list[$yearId][$id]['prices'] = $price_list;
    }
}

// get this exhibitor
$vendorQ = <<<EOS
SELECT e.id as exhibitorId, artistName, exhibitorName, exhibitorEmail, exhibitorPhone, website, description, e.need_new AS eNeedNew, 
    IFNULL(e.notes, '') AS exhNotes, ey.id AS exhibitorYearId, ey.contactName, ey.contactEmail, ey.contactPhone, 
    ey.need_new AS cNeedNew, DATEDIFF(now(), ey.lastVerified) AS DaysSinceLastVerified, ey.lastVerified, ey.mailin, IFNULL(ey.notes, '') AS contactNotes,
    e.addr, e.addr2, e.city, e.state, e.zip, e.country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, publicity,
    p.id AS perid, p.first_name AS p_first_name, p.last_name AS p_last_name, n.id AS newperid, n.first_name AS n_first_name, n.last_name AS n_last_name
FROM exhibitors e
LEFT OUTER JOIN exhibitorYears ey ON e.id = ey.exhibitorId
LEFT OUTER JOIN perinfo p ON p.id = e.perid
LEFT OUTER JOIN newperson n ON n.id = e.newperid
WHERE e.id=? AND ey.conid = ?;
EOS;

$infoR = dbSafeQuery($vendorQ, 'ii', array($exhibitorId, $conid));
$info = $infoR->fetch_assoc();
$infoR->free();

// load the country codes for the option pulldown
$fh = fopen(__DIR__ . '/../../lib/countryCodes.csv', 'r');
$countryOptions = '';
while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
    $countryOptions .=  "<option value='".$data[1]."'>".$data[0]."</option>\n";
}
fclose($fh);


$vendorPQ = <<<EOS
SELECT exRY.*, ery.id AS exhibitsRegionYearId
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
JOIN exhibitsRegionYears ery ON exRY.exhibitsRegionYearId = ery.id
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
JOIN exhibitsRegionTypes ert ON er.regionType = ert.regionType
WHERE exY.exhibitorId = ? AND er.id = ? AND ery.conid = ?;
EOS;

$vendor_perm = null;
$vendorPR = dbSafeQuery($vendorPQ, 'iii', array($exhibitorId, $regionId, $conid));
if ($vendorPR !== false) {
    if ($vendorPR->num_rows > 0) {
        $vendor_perm = $vendorPR->fetch_assoc();
    }
    $vendorPR->free();
}

$exhibitorSQ = <<<EOS
SELECT *
FROM vw_ExhibitorSpace
WHERE exhibitorId = ? and conid = ?;
EOS;

$exhibitorSR = dbSafeQuery($exhibitorSQ, 'ii', array($exhibitorId, $conid));
$exhibitorSpaceList = array();
while ($space = $exhibitorSR->fetch_assoc()) {
    $exhibitorSpaceList[$space['spaceId']] = $space;
}
$exhibitorSR->free();

$response['region_list'] = $region_list;
$response['exhibits_spaces'] = $space_list;
$response['exhibitor_info'] = $info;
$response['exhibitor_spacelist'] = $exhibitorSpaceList;
$response['exhibitor_perm'] = $vendor_perm;
$response['regions'] = $regions;
$response['spaces'] = $spaces;
$response['country_options'] = $countryOptions;

ajaxSuccess($response);
