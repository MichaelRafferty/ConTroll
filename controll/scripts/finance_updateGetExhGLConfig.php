<?php
// update changed exhibitor gl list configuration info and then returns the current list
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
    echo "not yet";
    exit(0);
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
        if ($glNum === null || $glNum == '') {
            $error .= "For the row $memId:$label:$startdate:$enddate, $glNum cannot be empty.<br/>";
        }
        if ($mdId == null || !is_numeric($mdId)) {
            $error .= "For the row $memId:$label:$startdate:$enddate, $mdId cannot be empty or non numeric.<br/>";
        }
    }
    if ($error != '') {
        $error .= 'Correct the missing data and save again.';
        $response['error'] = $error;
        ajaxSuccess($response);
        exit();
    }

    foreach ($data as $index => $row ) {

    }

    $updateSQL = <<<EOS
UPDATE memListDates SET glNum = ?
WHERE id = ?;
EOS;

    // now the updates
    foreach ($data as $row) {
        $numrows = dbSafeCmd($updateSQL, 'ii',
            array ($row['glNum'], $row['mdId']));
        $updated += $numrows;
    }

    $response['success'] .= "$tablename updated: $updated gl numbers changed.<br/>";
}

// now get the current list
// now get the current list
    $selSQL = <<<EOS
SELECT 'exhibitsRegionYears' AS tableName, 'glnum' AS fieldName, IFNULL(glNum, '') AS glNum, ery.id AS id, er.shortname, 
       'Space' as title, 'Year Default' AS 
label
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
WHERE conid = ?
UNION
SELECT 'exhibitsRegionYears' AS tableName, 'revenueGLNum' AS fieldName, IFNULL(revenueGLNum, '') AS glNum, ery.id AS id, er.shortname,
       'Sales' as title, 'Revenue' AS label
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
WHERE conid = ?
UNION
SELECT 'exhibitsRegionYears' AS tableName, 'mailinGLnum' AS fieldName, IFNULL(mailinGLnum, '') AS glNum, ery.id AS id, er.shortname,
       'Mail In' as title, 'Fee'
FROM exhibitsRegionYears ery
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
WHERE conid = ?
UNION
SELECT 'exhibitsSpaces' AS tableName, 'glNum' AS fieldName, IFNULL(es.glNum, '') AS glNum, es.id AS id, er.shortname,
       'Spaces' as title, 'Default' AS label
FROM exhibitsSpaces es
JOIn exhibitsRegionYears ery ON ery.id = es.exhibitsRegionYear
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
WHERE conid = ?
UNION
SELECT 'exhibitsSpacePrices' AS tableName, 'glNum' AS fieldName, IFNULL(esp.glNum, '') AS glNum, esp.id AS id, er.shortname,
       'Space' as title, esp.description AS label
FROM exhibitsSpacePrices esp
JOIN exhibitsSpaces es ON es.id = esp.spaceId
JOIn exhibitsRegionYears ery ON ery.id = es.exhibitsRegionYear
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id
WHERE conid = ?
ORDER BY 1,2,5,6
EOS;
$glQ = dbSafeQuery($selSQL, 'iiiii', array($conid, $conid, $conid,$conid, $conid));
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

$response['exhGLList'] = $gl;

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
