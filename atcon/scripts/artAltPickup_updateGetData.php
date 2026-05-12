<?php
// ConTroll Registration System, Copyright 2015-2026, Michael Rafferty, Licensed under the GNU Affero General Public License, Version 3.
// library AJAX Processor: artAltPickup_updateGetData.php
// Author: Syd Weinstein
// update the authorization data and get the new table

require_once "../lib/base.php";

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

if (!isSessionVar('user')) {
    header('Location: /index.php');
    exit(0);
}

$conid = getConfValue('con', 'id', '-1');

if (!array_key_exists('ajax_request_action', $_POST)) {
    $response['error'] = 'Parameter Missing';
    ajaxSuccess($response);
    exit();
}
$action=$_POST['ajax_request_action'];
switch ($action) {
    case 'loadInitialData':
        // nothing special to do here
        break;
}

// now get the data table
// get initial list of pickup relationships
$pSQL = <<<EOS
SELECT a.*, pb.first_name, pb.middle_name, pb.last_name,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', pb.first_name, pb.middle_name, pb.last_name, pb.suffix), ' +', ' ')) AS bidderFullName,
       TRIM(REGEXP_REPLACE(CONCAT_WS(' ', pp.first_name, pp.middle_name, pp.last_name, pp.suffix), ' +', ' ')) AS pickupFullName
FROM artshowAltPickupAuth a
LEFT OUTER JOIN perinfo pb ON pb.id = a.bidderPerid
LEFT OUTER JOIN perinfo pp ON pp.id = a.pickupPerid
WHERE conid = ?;
EOS;
$pR = dbSafeQuery($pSQL, 'i', array($conid));
if ($pR === false) {
    RenderErrorAjax('Query failed, seek assistance');
    exit();
}
$pickupAuths = [];
$ordinal = 0;
while ($pL = $pR->fetch_assoc()) {
    $pL['ordinal'] = $ordinal++;
    $pickupAuths[] = $pL;
}
$pR->free();
$response['authList'] = $pickupAuths;
ajaxSuccess($response);
