<?php
global $db_ini;

require_once '../lib/base.php';
$check_auth = google_init('ajax');
$perm = 'vendor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
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
WHERE locations IS NOT NULL AND locations != '' AND exhibitsRegionYearId = ?;
EOS;
$locationR = dbSafeQuery($locationQ, 'i', array($exhibitsRegionYearId));
$locationsUsed = '';
if ($locationR != false) {
    while ($locationL = $locationR->fetch_assoc()) {
        $locationsUsed .= ',' . $locationL['locations'];
    }
}
if (strlen($locationsUsed) > 1) {
    $locationsUsed = substr($locationsUsed, 1);
    $locationsUsed = explode(',', $locationsUsed);
    for ($i = 0; $i < count($locationsUsed); $i++) {
        $locationsUsed[$i] = trim($locationsUsed[$i]);
    }
    natsort($locationsUsed);
}
$response['locationsUsed'] = $locationsUsed;
ajaxSuccess($response);
?>
