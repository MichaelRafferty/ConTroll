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

ajaxSuccess($response);
?>
