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
if (!array_key_exists('approvalValue', $_POST)) {
    ajaxError('No Data');
}
$approvalValue = $_POST['approvalValue'];

if (!array_key_exists('approvalData', $_POST)) {
    ajaxError('No Data');
}
$approvalData = $_POST['approvalData'];

$approvalId = $approvalData['id'];
$curvalue = $approvalData['approval'];

if ($approvalValue == $curvalue) {
    $response['status'] = 'success';
    $response['message'] = 'Nothing to change';
} else {
    $upQ = <<<EOS
UPDATE exhibitorRegionYears
SET approval = ?, updateDate = NOW(), updateBy = ?
WHERE id = ?;
EOS;
    $num_rows = dbSafeCmd($upQ, 'sii', array($approvalValue, $_SESSION['user_perid'], $approvalId));
    if ($num_rows == 1) {
        $response['status'] = 'success';
        $response['message'] = "Approval changed to $approvalValue";
        $approvalData['approval'] = $approvalValue;
        $approvalData['b1'] = time();
        $approvalData['b2'] = $approvalData['b1'] + 1;
        $approvalData['b3'] = $approvalData['b2'] + 1;
        $approvalData['b4'] = $approvalData['b3'] + 1;
    }
    if ($num_rows == 0) {
        $response['status'] = 'success';
        $response['message'] = 'Nothing to change';
    }
}

$response['info'] = $approvalData;

if ($approvalValue == 'approved' || $approvalValue == 'denied') {
    $appQ = <<<EOS
SELECT ownerEmail, ownerName, er.name, e.exhibitorName, e.exhibitorEmail, exY.contactName, exY.contactEmail, e.id as exhibitorId, exRY.exhibitorNumber,
       IFNULL(TRIM(CONCAT(p.first_name, ' ', p.last_name)), TRIM(CONCAT(n.first_name, ' ', n.last_name))) AS agentName, agentRequest
FROM exhibitorRegionYears exRY
JOIN exhibitorYears exY ON exRY.exhibitorYearId = exY.id
JOIN exhibitors e ON exY.exhibitorId = e.id
JOIN exhibitsRegionYears ery ON ery.id = exRY.exhibitsRegionYearId
JOIN exhibitsRegions er on ery.exhibitsRegion = er.id
LEFT OUTER JOIN perinfo p ON p.id = exRY.agentPerid
LEFT OUTER JOIN newperson n ON n.id = exRY.agentNewperson
WHERE exRY.id = ?
EOS;
    $appR = dbSafeQuery($appQ, 'i', array($approvalId));
    if ($appR == false || $appR->num_rows != 1) {
        $response['error'] = 'Unable to send request approval email: cannot fetch details';
    } else {
        $appdata = $appR->fetch_assoc();
        $appR->free();
        $region = $appdata['name'];
        $ownerName = $appdata['ownerName'];
        $ownerEmail = $appdata['ownerEmail'];
        $exhibitorName = $appdata['exhibitorName'];
        $exhibitorEmail = $appdata['exhibitorEmail'];
        $contactEmail = $appdata['contactEmail'];

        $appSubject = "Request to appear in the $region";
        $appRegion = "You have been $approvalValue to appear in the $region at " . $conf['label'] . PHP_EOL . PHP_EOL;
        if ($approvalValue == 'approved') {
            $appRegion .= "Please sign into the portal and request your space.";
        } else {
            $appRegion .= "If you have questions, please reach out to $ownerName at $ownerEmail.";
        }

// now tell the exhibitor they have been approved
        $body = <<<EOS
Dear $exhibitorName

$appRegion

Thank you.
$ownerName
EOS;
        load_email_procs();
        $return_arr = send_email($ownerEmail, array($exhibitorEmail, $contactEmail), $ownerEmail, $appSubject, $body, null);

        if (array_key_exists('error_code', $return_arr)) {
            $error_code = $return_arr['error_code'];
        } else {
            $error_code = null;
        }
        if (array_key_exists('email_error', $return_arr)) {
            $response['error'] = 'Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code';
        } else {
            $response['message'] .= ', Email sent';
        }
    }
}


ajaxSuccess($response);
?>
