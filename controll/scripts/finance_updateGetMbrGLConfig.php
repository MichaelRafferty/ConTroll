<?php
// update changed membership gl list configuration info and then returns the current list
require_once '../lib/base.php';
require_once '../lib/sessionAuth.php';
require_once '../../lib/gl.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'finance';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$user_perid = $authToken->getPerid();
if (!$user_perid) {
    ajaxError('Invalid credentials passed');
    return;
}

$conid = getConfValue('con', 'id');
$hrtime = getConfValue('controll', 'hrtime', 0);

if ($hrtime)
    $startHRtime = hrtime(true);

if (!isset($_POST) || !isset($_POST['ajax_request_action'])) {
    $response['error'] = 'Missing Information';
    ajaxSuccess($response);
    exit();
}

$action = $_POST['ajax_request_action'];
$response['action'] = $action;
$updated = 0;
$deleted = 0;
$inserted = 0;
$delete_keys = '';
$first = true;

if (array_key_exists('tablename', $_POST)) {
    $tablename = $_POST['tablename'];
    $response['tablename'] = $tablename;
} else {
    $tablename = 'none';
}
$data = [];
$response['success'] = '';

if ($tablename != 'none') {
    try {
        $data = json_decode($_POST['tabledata'], true, 512, JSON_THROW_ON_ERROR);
    }
    catch (Exception $e) {
        $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
        $response['error'] = $msg;
        error_log($msg);
        ajaxSuccess($response);
        exit();
    }
    $error = '';
    foreach ($data as $index => $row) {
        $glNum = $row['glNum'];
        $mdId = $row['mdId'];
        $memId = $row['memId'];
        $label = $row['label'];
        $startdate = $row['startdate'];
        $enddate = $row['enddate'];
        // validate the fields
        if ($mdId == null || !is_numeric($mdId)) {
            $error .= "For the row $memId:$label:$startdate:$enddate, id cannot be empty or non numeric.<br/>";
        }
    }
    if ($error != '') {
        $error .= 'Correct the missing data and save again.';
        $response['error'] = $error;
        ajaxSuccess($response);
        exit();
    }

    $updateSQL = <<<EOS
UPDATE memListDates SET glNum = ?
WHERE id = ?;
EOS;

    // now the updates
    foreach ($data as $index => $row) {
        $glNum = $row['glNum'];
        if ($glNum == '')
            $glNum = null;

        $numrows = dbSafeCmd($updateSQL, 'si',
            array ($glNum, $row['mdId']));
        $updated += $numrows;
    }

    $response['success'] .= "$tablename updated: $updated gl numbers changed.<br/>";
}

// now get the current list
$selSQL = <<<EOS
SELECT id AS memId, mdId, memType, memCategory, memAge, label, startdate, enddate, IFNULL(glNum, '') AS glNum
FROM memListFull
WHERE ((conid = ? and memCategory != 'yearahead') OR (conid = ? AND memCategory in ('rollover', 'yearahead')))
OR ((conid = ? and memCategory != 'yearahead') OR (conid = ? AND memCategory in ('rollover', 'yearahead')))
ORDER BY memType, memCategory, memAge, label, startdate, enddate
EOS;
$glQ = dbSafeQuery($selSQL, 'iiii', array($conid, $conid + 1, $conid + 1, $conid + 2));
if ($glQ === false) {
    $response['error'] = 'Select query failed, get help';
    ajaxSuccess($response);
    exit();
}
$gl = [];
while ($glR = $glQ->fetch_assoc()) {
    $gl[] = $glR;
}
$glQ->free();

$response['mbrGLList'] = $gl;

[$gl, $glNums, $glLabels] = getGL();

// build gl list select options
$glEditorList = [];
$glEditorList[''] = 'No GL Assigned';
$glNumSelect = '<option value="">No GL assigned</option>' . PHP_EOL;
foreach ($glLabels as $glNum => $glLabel) {
    $glNumSelect .= '<option value="' . $glNum . '">' . $glNum . ': ' . $glLabel . '</option>';
    $glEditorList[$glNum] = "$glNum: $glLabel";
}
$response['gl'] = $gl;
$response['glEditorList'] = $glEditorList;
$response['glNumSelect'] = $glNumSelect;

if ($hrtime) {
    $endHRtime = hrtime(true);
    $intervalTime = $endHRtime - $startHRtime;
    $secs = intval($intervalTime / 1000000000);
    $ns = $intervalTime % 1000000000;
    $response['success'] .= sprintf("Call took %d.%09d seconds", $secs, $ns);
}
ajaxSuccess($response);
