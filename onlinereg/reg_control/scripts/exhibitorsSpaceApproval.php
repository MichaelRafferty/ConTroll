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
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
SET item_approved = item_requested, time_approved = NOW()
WHERE ery.id = ?;
EOS;
        $num_rows = dbSafeCmd($upQ, 'i', array($regionYearId));
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
        $upQ = <<<EOS
UPDATE exhibitorSpaces
SET item_approved = ?, time_approved = NOW()
WHERE spaceId = ? and exhibitorRegionYear = ?;
EOS;
$upCanQ = <<<EOS
UPDATE exhibitorSpaces
SET item_approved = null, item_requested = null, time_requested = NOW(), time_approved = NOW()
WHERE spaceId = ? and exhibitorRegionYear = ?;
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
                $num_rows += dbSafeCmd($upQ, 'iii', array($value, $spaceId, $regionYearId));
            } else {
                $num_rows += dbSafeCmd($upCanQ, 'ii', array($spaceId, $regionYearId));
            }
        }
        if ($num_rows > 0) {
            $response['status'] = 'success';
            $response['success'] = 'Space Approvel Updated';
        }
        if ($num_rows == 0) {
            $response['status'] = 'success';
            $response['success'] = 'Nothing to change';
        }
        break;

    default:
        $response['error'] =  'Bad type passed, get help';
}

if (array_key_exists('success', $response)) {
    // detail of space for this region
    $details = array();
    $detailQ = <<<EOS
WITH exh AS (
SELECT e.id, e.exhibitorName, e.website, e.exhibitorEmail, eRY.id AS exhibitorYearId, 
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
WHERE eY.conid = ? AND eRY.id = ?
GROUP BY e.id, e.exhibitorName, e.website, e.exhibitorEmail, eRY.id
)
SELECT xS.id, xS.exhibitorId, exh.exhibitorName, exh.website, exh.exhibitorEmail,
    xS.spaceId, xS.name as spaceName, xS.item_requested, xS.time_requested, xS.requested_units, xS.requested_code, xS.requested_description,
    xS.item_approved, xS.time_approved, xS.approved_units, xS.approved_code, xS.approved_description,
    xS.item_purchased, xS.time_purchased, xS.purchased_units, xS.purchased_code, xS.purchased_description, xS.transid,
    eRY.id AS exhibitsRegionYearId, eRY.exhibitsRegion AS regionId, eRY.ownerName, eRY.ownerEmail, eR.name AS regionName,
    exh.pu * 10000 + exh.au * 100 + exh.ru AS sortOrder
FROM vw_ExhibitorSpace xS
JOIN exhibitsSpaces eS ON xS.spaceId = eS.id
JOIN exhibitsRegionYears eRY ON eS.exhibitsRegionYear = eRY.id
JOIN exhibitsRegions eR ON eR.id = eRY.exhibitsRegion
JOIN exh ON (xS.exhibitorId = exh.id)
WHERE eRY.conid=? AND eRY.id = ? AND (time_requested IS NOT NULL OR time_approved IS NOT NULL)
ORDER BY sortOrder, exhibitorName, spaceName
EOS;

    $detailR = dbSafeQuery($detailQ, 'iiii', array($conid, $regionYearId, $conid, $regionYearId));

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
    $return_arr = send_email($conf['regadminemail'], array($exhibitorEmail, $contactEmail), $ownerEmail, $spaceSubject, $body, null);

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
