<?php
require_once "../lib/base.php";
require_once('../../lib/exhibitorYears.php');
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

if (!array_key_exists('past', $_POST)) {
    $response['error'] = 'Calling Sequence Error';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];
$past = json_decode($_POST['past'], true, 512, JSON_THROW_ON_ERROR);

// update the exhibitors by building the ones that are marked import in $past
foreach ($past as $exh) {
    if ($exh['import']) {
        // build this element, as import is true.
        $yearId = exhibitorBuildYears($exh['id']);
        exhibitorCheckMissingSpaces($exh['id'], $yearId);
    }
}


// get the exhibitor data
$exhibitorQ = <<<EOS
SELECT e.id as exhibitorId, perid, artistName, exhibitorName, exhibitorEmail, exhibitorPhone, website, description, password, publicity, 
       addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, archived,
       IFNULL(e.notes, '') AS exhNotes, eY.id as exhibitorYearId, conid, contactName, contactEmail, contactPhone, contactPassword, mailin
FROM exhibitors e
JOIN exhibitorYears eY ON e.id = eY.exhibitorId
WHERE eY.conid = ?;
EOS;

$exhibitorR = dbSafeQuery($exhibitorQ, 'i', array($conid));
if (!$exhibitorR) {
    ajaxSuccess(array(
        'args' => $_POST,
        'query' => $exhibitorQ,
        'error' => 'query failed'));
    exit();
}

$exhibitors = array();
while ($exhibitorL = $exhibitorR->fetch_assoc()) {
    $exhibitors[] = $exhibitorL;
}
$exhibitorR->free();

$response['exhibitors'] = $exhibitors;

ajaxSuccess($response);
