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

if (!(array_key_exists('regionId', $_POST))) {
    $response['error'] = 'Calling Sequence Error';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];
$regionId = $_POST['regionId'];

$exhibitorQ = <<<EOS
SELECT DISTINCT e.id as exhibitorId, perid, artistName, exhibitorName, exhibitorEmail, website, city, state, zip, 
       eY.id as contactId, eY.conid, contactName, contactEmail
FROM exhibitors e
JOIN exhibitorYears eY ON e.id = eY.exhibitorId
JOIN exhibitorRegionYears eRY ON eRY.exhibitorYearId = eY.id
JOIN exhibitsRegionYears ery ON ery.id = eRY.exhibitsRegionYearId
LEFT OUTER JOIN exhibitorSpaces eS ON eS.exhibitorRegionYear = eRY.id AND ery.conid = eY.conid
WHERE ery.id = ? AND eY.conid = ? AND eS.item_purchased IS NULL
ORDER BY e.id;
EOS;

$exhibitorR = dbSafeQuery($exhibitorQ, 'ii', array($regionId, $conid));
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
?>
