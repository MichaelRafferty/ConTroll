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

ajaxSuccess($response);
?>
