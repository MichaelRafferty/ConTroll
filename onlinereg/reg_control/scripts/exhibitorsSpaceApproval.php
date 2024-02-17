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
            $response['message'] = "Space Approved";
            }
        if ($num_rows == 0) {
            $response['status'] = 'success';
            $response['message'] = 'Nothing to change';
        }
        break;
    case 'other': // this is either admin approve other or admin change approval functions

        // requests = each space price id in the format
        break;

    default:
        $response['error'] =  'Bad type passed, get help';
}

/*
        $approvalData['approval'] = $approvalValue;
        $approvalData['b1'] = time();
        $approvalData['b2'] = time();
        $approvalData['b3'] = time();
        $approvalData['b4'] = time();
    }
    if ($num_rows == 0) {
        $response['status'] = 'success';
        $response['message'] = 'Nothing to change';
    }
}

$response['info'] = $approvalData;
*/

ajaxSuccess($response);
?>
