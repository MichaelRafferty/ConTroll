<?php
require_once '../lib/base.php';
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

if (!array_key_exists('exhibitorRegionYearId', $_POST) || !array_key_exists('exhibitsRegionYearId', $_POST) || !array_key_exists('locations', $_POST)) {
    ajaxError('No Data');
}

$exhibitorRegionYearId = $_POST['exhibitorRegionYearId'];
$exhibitsRegionYearId = $_POST['exhibitsRegionYearId'];
$locations = $_POST['locations'];
if ($locations == null)
    $locations = '';

$upQ = <<<EOS
UPDATE exhibitorRegionYears 
SET locations = ?
WHERE id = ?;
EOS;

$num_rows = dbSafeCmd($upQ, 'si', array($locations, $exhibitorRegionYearId));
if ($num_rows > 0 ) {
    $response['status'] = 'success';
    $response['success'] = "Locations Updated";
    }
if ($num_rows == 0) {
    $response['status'] = 'success';
    $response['success'] = 'Nothing to change';
}

// get all locations in use
$locationQ = <<<EOS
SELECT exRY.locations
FROM exhibitorRegionYears exRY
JOIN exhibitsRegionYears eRY ON exRY.exhibitsRegionYearId = eRY.id
WHERE locations != '' AND exhibitsRegionYearId = ?;
EOS;
$locationR = dbSafeQuery($locationQ, 'i', array($exhibitsRegionYearId));
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
}
$response['locationsUsed'] = $locationsUsed;
ajaxSuccess($response);
