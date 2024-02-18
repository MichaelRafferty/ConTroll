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

$con = get_con();
$conid = $con['id'];
if (!array_key_exists('approvalType', $_POST)) {
    ajaxError('No Data');
}
$approvalType = $_POST['approvalType'];
$regionYearId = $_POST['regionYearId'];

switch ($approvalType) {
    case 'req':
        if (!array_key_exists('exhibitorData', $_POST)) {
            ajaxError('No Data');
        }
        $exhibitorData = $_POST['exhibitorData'];

//region = { eYRid: currentRegion, exhibitorId: space['exhibitorId'], exhibitorName: space['exhibitorName'], website: space['website'],
        //                   exhibitorEmail: space['exhibitorEmail'], transid: space['transid'], };
        $exhibitsYearId = $exhibitorData['eYRid'];
        $exhibitorId = $exhibitorData['exhibitorId'];

        $upQ = <<<EOS
UPDATE exhibitorSpaces eS
JOIN exhibitorYears eY on eS.exhibitorYearId = eY.id
JOIN exhibitsSpaces es ON es.id = eS.spaceId
JOIN exhibitsRegionYears ery ON es.exhibitsRegionYear = ery.id AND eY.conid = ery.conid
SET item_approved = item_requested, time_approved = NOW()
WHERE ery.id = ?;
EOS;
        $num_rows = dbSafeCmd($upQ, 'i', array($exhibitsYearId));
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
WHERE spaceId = ? and exhibitorYearId = ?;
EOS;
$upCanQ = <<<EOS
UPDATE exhibitorSpaces
SET item_approved = null, item_requested = null, time_requested = null, time_approved = null
WHERE spaceId = ? and exhibitorYearId = ?;
EOS;

        // requests = each space price id in the format
        $requests = $_POST['requests'];
        $exhibitorId = $_POST['exhibitorId'];
        $exhibitorYearId = $_POST['exhibitorYearId'];
        $requests = explode('&',$requests);
        $num_rows = 0;
        foreach ($requests as $req) {
            $reqitems = explode('=', $req);
            $spaceId = $reqitems[0];
            $value = $reqitems[1];
            $spaceId = str_replace('exhbibitor_req_price_id_', '', $spaceId);
            if ($value > 0) {
                $num_rows += dbSafeCmd($upQ, 'iii', array($value, $spaceId, $exhibitorYearId));
            } else {
                $num_rows += dbSafeCmd($upCanQ, 'ii', array($spaceId, $exhibitorYearId));
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
JOIN exhibitorYears eY ON (eY.id = eS.exhibitorYearId)
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
    eRY.id AS exhibitsRegionYearId, eRY.exhibitsRegion AS regionId,
    exh.pu * 10000 + exh.au * 100 + exh.ru AS sortOrder
FROM vw_ExhibitorSpace xS
JOIN exhibitsSpaces eS ON xS.spaceId = eS.id
JOIN exhibitsRegionYears eRY ON eS.exhibitsRegionYear = eRY.id
JOIN exh ON (xS.exhibitorId = exh.id)
WHERE eRY.conid=? AND eRY.id = ? AND (IFNULL(requested_units, 0) > 0 OR IFNULL(approved_units, 0) > 0)
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
}
ajaxSuccess($response);
?>
