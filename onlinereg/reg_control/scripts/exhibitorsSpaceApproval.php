<?php
global $db_ini;

require_once '../lib/base.php';
require_once('../../../lib/email__load_methods.php');
$check_auth = google_init('ajax');
$perm = 'vendor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];
$conf = get_conf('con');
if (!array_key_exists('approvalType', $_POST)) {
    ajaxError('No Data');
}
$approvalType = $_POST['approvalType'];
if (array_key_exists('regionYearId', $_POST))
    $regionYearId = $_POST['regionYearId'];

switch ($approvalType) {
    case 'req':
        if (!array_key_exists('exhibitorData', $_POST)) {
            ajaxError('No Data');
        }
        $exhibitorData = $_POST['exhibitorData'];
        $regionYearId = $exhibitorData['regionYearId'];

//region = { eYRid: currentRegion, exhibitorId: space['exhibitorId'], exhibitorName: space['exhibitorName'], website: space['website'],
        //                   exhibitorEmail: space['exhibitorEmail'], transid: space['transid'], };
        $exhibitorId = $exhibitorData['exhibitorId'];

        $upQ = <<<EOS
UPDATE exhibitorSpaces eS
JOIN exhibitorRegionYears exRY ON eS.exhibitorRegionYear = exRY.id
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
SET item_approved = item_requested, time_approved = NOW()
WHERE ery.id = ? and eY.exhibitorId = ?;
EOS;
        $num_rows = dbSafeCmd($upQ, 'ii', array($regionYearId, $exhibitorId));
        if ($num_rows > 0 ) {
            $response['status'] = 'success';
            $response['success'] = "Space Approved";
            }
        if ($num_rows == 0) {
            $response['status'] = 'success';
            $response['success'] = 'Nothing to change';
        }
        break;
    case 'other': // this is either admin approve other or admin change approval functions
        if (!(array_key_exists('requests', $_POST) && array_key_exists('exhibitorYearId', $_POST))) {
            ajaxError('No Data');
        }

        $exhibitorId = $_POST['exhibitorId'];
        $exhibitorYearId = $_POST['exhibitorYearId'];

        $upQ = <<<EOS
UPDATE exhibitorSpaces eS
JOIN exhibitorRegionYears exRY ON eS.exhibitorRegionYear = exRY.id
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
SET item_approved = ?, time_approved = NOW()
WHERE eS.spaceId = ? and ery.id = ? AND eY.exhibitorId = ?;
EOS;
        $upQ2 = <<<EOS
UPDATE exhibitorSpaces eS
JOIN exhibitorRegionYears exRY ON eS.exhibitorRegionYear = exRY.id
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
SET item_requested = item_approved, time_requested = time_approved 
WHERE eS.spaceId = ? and ery.id = ? and eY.exhibitorId = ? and item_requested is NULL;
EOS;
        $upCanQ = <<<EOS
UPDATE exhibitorSpaces eS
JOIN exhibitorRegionYears exRY ON eS.exhibitorRegionYear = exRY.id
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
SET item_approved = null, item_requested = null, time_requested = NOW(), time_approved = NOW()
WHERE eS.spaceId = ? and ery.id = ? and eY.exhibitorId = ?;
EOS;
        $existingQ = <<<EOS
SELECT item_requested, item_approved, time_requested, time_approved
FROM exhibitorSpaces eS
JOIN exhibitorRegionYears exRY ON eS.exhibitorRegionYear = exRY.id
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
WHERE eS.spaceId = ? and ery.id = ? and eY.exhibitorId = ?;
EOS;


        // requests = each space price id in the format
        $requests = $_POST['requests'];
        $exhibitorId = $_POST['exhibitorId'];
        $exhibitorYearId = $_POST['exhibitorYearId'];
        $regionYearId = $_POST['regionYearId'];
        $requests = explode('&',$requests);
        $num_rows = 0;
        foreach ($requests as $req) {
            $reqitems = explode('=', $req);
            $spaceId = $reqitems[0];
            $value = $reqitems[1];
            $spaceId = str_replace('exhbibitor_req_price_id_', '', $spaceId);
            if ($value > 0) {
                $num_rows += dbSafeCmd($upQ, 'iiii', array($value, $spaceId, $regionYearId, $exhibitorId));
                dbSafeCmd($upQ2, 'iii', array($spaceId, $regionYearId, $exhibitorId));
            } else {
                $paramarray = array($spaceId, $regionYearId, $exhibitorId);
                $typestr = 'iii';
                $existingR = dbSafeQuery($existingQ, $typestr, $paramarray);
                if ($existingR == false || $existingR->num_rows != 1) {
                    web_error_log("Could not retrieve existing space request for $spaceId, $regionYearId, $exhibitorId");
                }
                $existing = $existingR->fetch_assoc();
                $existingR->free();
                if ($existing['item_requested'] != null) { // only if there was something existing, cancel it}
                    $num_rows += dbSafeCmd($upCanQ, $typestr, $paramarray);
                }
            }
        }
        if ($num_rows > 0) {
            $response['status'] = 'success';
            $response['success'] = 'Space Approval Updated';
        }
        if ($num_rows == 0) {
            $response['status'] = 'success';
            $response['success'] = 'Nothing to change';
        }
        break;

    case 'pay': // set request, approve and purchased, as needed
        if (!(array_key_exists('requests', $_POST) && array_key_exists('exhibitorYearId', $_POST))) {
            ajaxError('No Data');
        }

        $exhibitorId = $_POST['exhibitorId'];
        $exhibitorYearId = $_POST['exhibitorYearId'];

        $upQ = <<<EOS
UPDATE exhibitorSpaces eS
JOIN exhibitorRegionYears exRY ON eS.exhibitorRegionYear = exRY.id
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
SET item_requested = ?, time_requested = NOW(),
    item_approved = ?, time_approved = NOW(),
    item_purchased = ?, time_purchased = NOW()
WHERE eS.spaceId = ? and ery.id = ? AND eY.exhibitorId = ?;
EOS;
        $upCanQ = <<<EOS
UPDATE exhibitorSpaces eS
JOIN exhibitorRegionYears exRY ON eS.exhibitorRegionYear = exRY.id
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
SET item_approved = null, item_requested = null, 
    time_requested = NOW(), time_approved = NOW()
WHERE eS.spaceId = ? and ery.id = ? and eY.exhibitorId = ?;
EOS;
        $existingQ = <<<EOS
SELECT item_requested, item_approved, time_requested, time_approved, item_purchased, time_purchased
FROM exhibitorSpaces eS
JOIN exhibitorRegionYears exRY ON eS.exhibitorRegionYear = exRY.id
JOIN exhibitorYears eY ON exRY.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
WHERE eS.spaceId = ? and ery.id = ? and eY.exhibitorId = ?;
EOS;

        // requests = each space price id in the format
        $requests = $_POST['requests'];
        $exhibitorId = $_POST['exhibitorId'];
        $exhibitorYearId = $_POST['exhibitorYearId'];
        $regionYearId = $_POST['regionYearId'];
        $requests = explode('&',$requests);
        $num_rows = 0;
        foreach ($requests as $req) {
            $reqitems = explode('=', $req);
            $spaceId = $reqitems[0];
            $value = $reqitems[1];
            $spaceId = str_replace('exhbibitor_req_price_id_', '', $spaceId);
            $paramarray = array($spaceId, $regionYearId, $exhibitorId);
            $typestr = 'iii';
            $existingR = dbSafeQuery($existingQ, $typestr, $paramarray);
            if ($existingR == false|| $existingR->num_rows != 1) {
                web_error_log("Could not retrieve existing space request for $spaceId, $regionYearId, $exhibitorId");
                continue;
            }
            $existing = $existingR->fetch_assoc();
            $existingR->free();
            // row already exists, update it,
            if ($value > 0) { // update it to the new value
                $num_rows += dbSafeCmd($upQ, 'iiiiii', array($value, $value, $value, $spaceId, $regionYearId, $exhibitorId));
            } else { // it's cancelled, if its not already cancelled, cancel it
                if ($existing['item_requested'] != null) { // only if there was something existing, cancel it}
                    $num_rows += dbSafeCmd($upCanQ, $typestr, $paramarray);
                }
            }
        }
        if ($num_rows > 0) {
            $response['status'] = 'success';
            $response['success'] = 'Space Purchase Updated';
        }
        if ($num_rows == 0) {
            $response['status'] = 'success';
            $response['success'] = 'Nothing to change';
        }
        break;

    default:
        $response['error'] =  'Bad type passed, get help';
}

if (array_key_exists('success', $response) && $approvalType != 'pay') {
    // detail of space for this region
    $details = array();
    $detailQ = <<<EOS
WITH exh AS (
SELECT e.id, e.exhibitorName, e.website, e.exhibitorEmail, eRY.id AS exhibitorYearId, exRY.exhibitorNumber, exRY.agentRequest,
    TRIM(CONCAT(p.first_name, ' ', p.last_name)) as pName, TRIM(CONCAT(n.first_name, ' ', n.last_name)) AS nName,
	SUM(IFNULL(espr.units, 0)) AS ru, SUM(IFNULL(espa.units, 0)) AS au, SUM(IFNULL(espp.units, 0)) AS pu
FROM exhibitorSpaces eS
LEFT OUTER JOIN exhibitsSpacePrices espr ON (eS.item_requested = espr.id)
LEFT OUTER JOIN exhibitsSpacePrices espa ON (eS.item_approved = espa.id)
LEFT OUTER JOIN exhibitsSpacePrices espp ON (eS.item_purchased = espp.id)
JOIN exhibitorRegionYears exRY ON exRY.id = eS.exhibitorRegionYear
JOIN exhibitorYears eY ON (eY.id = exRY.exhibitorYearId)
JOIN exhibitors e ON (e.id = eY.exhibitorId)
JOIN exhibitsSpaces s ON (s.id = eS.spaceId)
JOIN exhibitsRegionYears eRY ON s.exhibitsRegionYear = eRY.id
LEFT OUTER JOIN perinfo p ON p.id = exRY.agentPerid
LEFT OUTER JOIN newperson n ON n.id = exRY.agentNewperson
WHERE eY.conid = ? AND eRY.id = ? AND e.id = ?
GROUP BY e.id, e.exhibitorName, e.website, e.exhibitorEmail, eRY.id, exRY.exhibitorNumber, exRY.agentRequest, pName, nName
)
SELECT xS.id, xS.exhibitorId, exh.exhibitorName, exh.website, exh.exhibitorEmail,
    xS.spaceId, xS.name as spaceName, xS.item_requested, xS.time_requested, xS.requested_units, xS.requested_code, xS.requested_description,
    xS.item_approved, xS.time_approved, xS.approved_units, xS.approved_code, xS.approved_description,
    xS.item_purchased, xS.time_purchased, xS.purchased_units, xS.purchased_code, xS.purchased_description, xS.transid,
    eRY.id AS exhibitsRegionYearId, eRY.exhibitsRegion AS regionId, eRY.ownerName, eRY.ownerEmail, eR.name AS regionName, exh.exhibitorNumber,
    IFNULL(pName, nName) as agentName,
    exh.pu * 10000 + exh.au * 100 + exh.ru AS sortOrder
FROM vw_ExhibitorSpace xS
    JOIN exhibitsSpaces eS ON xS.spaceId = eS.id
    JOIN exhibitsRegionYears eRY ON eS.exhibitsRegionYear = eRY.id
    JOIN exhibitsRegions eR ON eR.id = eRY.exhibitsRegion
    JOIN exh ON (xS.exhibitorId = exh.id)
WHERE eRY.conid=? AND eRY.id = ? AND xS.exhibitorId = ? AND (time_requested IS NOT NULL OR time_approved IS NOT NULL)
ORDER BY sortOrder, exhibitorName, spaceName
EOS;

    $detailR = dbSafeQuery($detailQ, 'iiiiii', array($conid, $regionYearId, $exhibitorId, $conid, $regionYearId, $exhibitorId));

    while ($detailL = $detailR->fetch_assoc()) {
        $detail = $detailL;
        $detail['b1'] = time();
        $detail['b2'] = time();
        $detail['b3'] = time();
        $details[] = $detail;
    }

    $response['detail'] = $details;

    // now send the approved email
    // first get the email addresses
    $exhibitorQ = <<<EOS
SELECT exhibitorName, exhibitorEmail, contactName, contactEmail
FROM exhibitors e
JOIN exhibitorYears y ON e.id = y.exhibitorId
WHERE e.id = ?
EOS;
    $exhibitorR = dbSafeQuery($exhibitorQ, 'i', array($exhibitorId));
    $exhibitorL = $exhibitorR->fetch_assoc();
    $exhibitorName = $exhibitorL['exhibitorName'];
    $exhibitorEmail = $exhibitorL['exhibitorEmail'];
    $contactName = $exhibitorL['contactName'];
    $contactEmail = $exhibitorL['contactEmail'];
    $exhibitorR->free();

    // Now get/format the approval space details
    $spaceDetail = '';
    $spaceHeader = '';
    $spaceSubject = '';
    $ownerName = '';
    $ownerEmail = '';
    $approved = false;

    foreach ($details AS $key => $detail) {
        if ($detail['exhibitorId'] && $detail['exhibitsRegionYearId'] == $regionYearId) {
            if ($spaceHeader == '') {
                $ownerName = $detail['ownerName'];
                $ownerEmail = $detail['ownerEmail'];
                $spaceHeader = "Your approval for space in " . $con['label'] . "'s " . $detail['regionName'] . " has been updated.";
                $spaceSubject = "Update to " . $con['label'] . "'s " . $detail['regionName'] . " space approval";
            }
            if ($detail['item_requested'] != null && $detail['item_approved'] != null) { // space was requested and something was approved
                if ($detail['item_requested'] == $detail['item_approved']) {
                    $spaceDetail .= "Your request for " . $detail['approved_description'] . " of " . $detail['spaceName'] . " was approved.\n";
                    $approved = true;
                } else {
                    $spaceDetail .= 'Your have been approved for ' . $detail['approved_description'] . ' of ' . $detail['spaceName'] . ".\n";
                    $approved = true;
                }
            } else if ($detail['time_approved'] != null && $detail['item_approved'] == null) {
                $spaceDetail .= "Your request for " . $detail['spaceName'] . " has been denied.\n";
            }
        }
    }

    if ($approved) {
        $spaceDetail .= "\nPlease sign into the portal to purchase your space and memberships.\n";
    }

    $body = <<<EOS
Dear $exhibitorName

$spaceHeader

$spaceDetail

Thank you.
$ownerName
EOS;
    load_email_procs();
    $return_arr = send_email($ownerEmail, array($exhibitorEmail, $contactEmail), $ownerEmail, $spaceSubject, $body, null);

    if (array_key_exists('error_code', $return_arr)) {
        $error_code = $return_arr['error_code'];
    } else {
        $error_code = null;
    }
    if (array_key_exists('email_error', $return_arr)) {
        $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
    } else {
        $response['success'] .= ', Request sent';
    }
}
ajaxSuccess($response);
?>
