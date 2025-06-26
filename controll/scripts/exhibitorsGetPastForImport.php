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

if (!(array_key_exists('portalType', $_POST) && array_key_exists('portalName', $_POST))) {
    $response['error'] = 'Calling Sequence Error';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];


// get all exhibitors that are not set up for this year

$pastQ = <<<EOS
WITH maxcid AS (
    SELECT max(conid) as maxConid, exhibitorId
    FROM exhibitorYears
    GROUP BY exhibitorId
)
SELECT e.id, e.perid, e.newperid, e.exhibitorName, e.exhibitorEmail, e.exhibitorPhone, e.website, e.publicity,
    e.addr, e.addr2, e.city, e.state, e.zip, e.country, IFNULL(e.notes, '') AS exhNotes,
    e.shipCompany, e.shipAddr, e.shipAddr2, e.shipCity, e.shipState, e.shipZip, e.shipCountry, e.archived,
    ey.id as eyId, ey.conid, ey.exhibitorId, ey.contactName, ey.contactEmail, ey.contactPhone, ey.mailin, 0 as import
FROM exhibitors e
LEFT OUTER JOIN maxcid ON e.id = maxcid.exhibitorId
LEFT OUTER JOIN exhibitorYears ey ON e.id = ey.exhibitorId AND maxcid.maxConid = ey.conid
LEFT OUTER JOIN exhibitorYears cey ON e.id = cey.exhibitorId and cey.conid = ?
WHERE cey.id IS NULL;
EOS;

$pastR = dbSafeQuery($pastQ,'i',array($conid));
$past = array(); // forward array, id -> data

while ($pastL = $pastR->fetch_assoc()) {
    $past[] = $pastL;
}
$pastR->free();

$response['past'] = $past;
ajaxSuccess($response);
