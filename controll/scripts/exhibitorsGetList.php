<?php
global $db_ini;

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = 'exhibitor';

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('regionId', $_POST))) {
    $response['error'] = 'Calling Sequence Error';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];
$regionId = $_POST['regionId'];

$exhibitorQ = <<<EOS
WITH regionYear AS (
    SELECT eRY.id, eY.exhibitorId
    FROM exhibitorRegionYears eRY
    JOIN exhibitsRegionYears ery ON ery.id = eRY.exhibitsRegionYearID
    JOIN exhibitorYears eY ON eY.id = eRY.exhibitorYearId
    WHERE ery.exhibitsRegion = ? AND eY.conid = ?
), esNotNull AS (
    SELECT rY.id
    FROM exhibitorSpaces eS
    JOIN regionYear rY ON (rY.id = eS.exhibitorRegionYear)
    WHERE eS.price IS NOT NULL
)
SELECT DISTINCT e.id as exhibitorId, perid, artistName, exhibitorName, exhibitorEmail, website, city, state, zip, 
       eY.id as exhibitorYearId, eY.conid, contactName, contactEmail
FROM exhibitors e
JOIN exhibitorYears eY ON e.id = eY.exhibitorId
JOIN exhibitorRegionYears eRY ON eRY.exhibitorYearId = eY.id
JOIN exhibitsRegionYears ery ON ery.id = eRY.exhibitsRegionYearId
LEFT OUTER JOIN esNotNull nn ON nn.id = eRY.id
WHERE ery.exhibitsRegion = ? AND eY.conid = ? AND nn.id IS NULL
ORDER BY e.id;
EOS;

$exhibitorR = dbSafeQuery($exhibitorQ, 'iiii', array($regionId, $conid, $regionId, $conid));
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
$response['status'] = 'success';
$response['message'] = count($exhibitors) . ' exhibitors found.';

ajaxSuccess($response);
