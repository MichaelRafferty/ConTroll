<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'art_control';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$conf = get_conf('con');

$con=get_con();
$conid=$con['id'];

$region = null;
if (array_key_exists('region', $_POST)) {
    $region = $_POST['region'];
} else {
    ajaxError('No Data');
}

if (array_key_exists('conYear', $_POST)) {
    $conYear = $_POST['conYear'];
} else {
    $conYear = $conid;
}
$minConYear = getConfValue('controll', 'viewPriorLimit', $conid);
if ($conYear < $minConYear)
    $conYear = $minConYear;
$minEdit = getConfValue('controll', 'artEditYear', $conid);
$response['editable'] = $conYear >= $minEdit;

$artQ = <<<EOS
WITH historyCount AS (
    SELECT H.id, count(*) AS historyCount
    FROM artItemsHistory H
    JOIN exhibitorRegionYears ery ON ery.id = H.exhibitorRegionYearId
    JOIN exhibitorYears ey ON ey.id=ery.exhibitorYearId
    JOIN exhibitsRegionYears exRY ON exRY.id=ery.exhibitsRegionYearId
    WHERE ey.conid=? and exRY.exhibitsRegion=?
    GROUP BY H.id
)
SELECT I.id, I.exhibitorRegionYearId, I.item_key, I.title, I.type, I.status, I.location, I.quantity, I.original_qty, 
    I.min_price, I.sale_price, I.final_price, I.bidder, I.material, I.notes, I.conid, h.historyCount,
    ey.id AS exhibitorYearId, ery.exhibitsRegionYearId,
    ery.exhibitorNumber, ery.locations, e.exhibitorName, exR.name as exhibitRegionName,
    concat(trim(p.first_name), ' ', trim(p.last_name)) as bidderName,
    concat(trim(p.first_name), ' ', trim(p.last_name), ' (', I.bidder, ')') as bidderText,
    concat(I.exhibitorRegionYearId, '_', I.item_key) as extendedKey
FROM artItems I 
    JOIN exhibitorRegionYears ery ON ery.id = I.exhibitorRegionYearId
    JOIN exhibitorYears ey ON ey.id=ery.exhibitorYearId
    JOIN exhibitors e ON e.id=ey.exhibitorId
    JOIN exhibitsRegionYears exRY ON exRY.id=ery.exhibitsRegionYearId
    JOIN exhibitsRegions exR on exR.id=exRY.exhibitsRegion
    LEFT JOIN perinfo p ON p.id=I.bidder
    LEFT JOIN historyCount h on h.id = I.id
WHERE ey.conid=? and exRY.exhibitsRegion=?
ORDER BY ery.exhibitorNumber, I.item_key;
EOS;

$artR = dbSafeQuery($artQ, 'iiii', array($conYear, $region, $conYear, $region));

$items=array();

    while($artItem = $artR->fetch_assoc()) {
        $items[] = $artItem;
    }
    $artR->free();

$response['art'] = $items;

    $artistQ = <<<EOS
SELECT DISTINCT e.exhibitorName, ery.id as exhibitorRegionYearId, ery.exhibitorNumber, ery.locations
FROM exhibitorYears ey 
    JOIN exhibitorRegionYears ery ON ery.exhibitorYearId = ey.id
    JOIN exhibitors e ON ey.exhibitorId=e.id
    JOIN exhibitsRegionYears exRY ON exRY.id=ery.exhibitsRegionYearId
    JOIN exhibitorSpaces S ON S.exhibitorRegionYear=ery.id 
WHERE ey.conid=? AND exRY.exhibitsRegion=? AND S.item_purchased IS NOT NULL
    AND ery.exhibitorNumber IS NOT NULL
ORDER BY e.exhibitorName;
EOS;
    $artistR = dbSafeQuery($artistQ, 'ii', array($conYear, $region));

$artists=array();

    while($artist = $artistR->fetch_assoc()) {
        $artists[] = $artist;
    }
    $artistR->free();

$response['artists'] = $artists;

ajaxSuccess($response);
