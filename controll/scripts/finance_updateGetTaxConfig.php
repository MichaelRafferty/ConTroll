<?php
// update changed taxList configuration info and then returns the current listr
require_once '../lib/base.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$check_auth = google_init('ajax');
$perm = 'exhibitor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('user_perid', $_SESSION)) {
    $user_perid = $_SESSION['user_perid'];
}
else {
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

$first = true;

if (array_key_exists('tablename', $_POST)) {
    $tablename = $_POST['tablename'];
    $response['tablename'] = $tablename;
} else {
    $tablename = 'none';
}
$data = [];

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
        $taxField = $row['taxField'];
        // validate the fields
        if (!array_key_exists('rate', $row)) {
            $error .= "For the entry $taxField The tax rate cannot be empty.<br/>";
        } else if ($row['rate'] < 0 || $row['rate'] >= 100) {
            $error .= "For the entry $taxField The tax rate must be between 0 and 100.<br/>";
        }
        if (!array_key_exists('label', $row)) {
            $error .= "For the entry $taxField The tax receipt label cannot be empty.<br/>";
        } else {
            $data[$index]['label'] = trim($row['label']);
        }
        if (!array_key_exists('glNum', $row))
            $data[$index]['glNum'] = null;
        else
            $data[$index]['glNum'] = trim($row['glNum']);
        if (!array_key_exists('glLabel', $row))
            $data[$index]['glLabel'] = null;
        else
            $data[$index]['glLabel'] = trim($row['glLabel']);
    }
    if ($error != '') {
        $error .= 'Correct the missing data and save again.';
        $response['error'] = $error;
        ajaxSuccess($response);
        exit();
    }

    $insupdsql = <<<EOS
INSERT INTO taxList(conid, taxField, label, rate, active, glNum, glLabel, lastUpdate, updatedBy)
VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
ON DUPLICATE KEY UPDATE label = ?, rate = ?, active = ?, glNum = ?, glLabel = ?, lastUpdate = NOW(), updatedBy = ?;
EOS;

    // now the updates, do the updates first in case we need to insert a new row with the same older key
    foreach ($data as $row) {
        $numrows = dbSafeCmd($insupdsql, 'issdsssisdsssi',
            array ($conid, $row['taxField'], $row['label'], $row['rate'], $row['active'], $row['glNum'], $row['glLabel'], $user_perid,
                $row['label'], $row['rate'], $row['active'], $row['glNum'], $row['glLabel'], $user_perid));
        $updated += $numrows;
    }

    $response['message'] = "$tablename updated: $updated tax rates changed.";
}


// check to see if this is the first time in a new year
$yearcheckR = dbSafeQuery("SELECT conid, COUNT(*) AS numRows FROM taxList WHERE conid IN (?, ?) GROUP BY conid;", 'ii', array($conid-1, $conid));
if ($yearcheckR == false) {
    $response['error'] = "Year check query failed";
    ajaxSuccess($response);
    exit();
}
$years = [];
while ($yearL = $yearcheckR->fetch_assoc()) {
    $years[$yearL['conid']] = $yearL['numRows'];
}
$yearcheckR->free();
if (count($years) == 1 && array_key_exists($conid - 1, $years)) {
    // There is data from last year and not this year..., so insert the new year data.
    $ins = <<<EOS
INSERT INTO taxList(conid, taxField, label, rate, active, glNum, glLabel, lastUpdate, updatedBy)
SELECT ?, taxField, label, rate, active, glNum, glLabel, now(), ?
FROM taxList
WHERE conid = ?;
EOS;
    $numRows=dbSafeCmd($ins, 'ii', array($conid, $user_perid, $conid - 1));
}
// now get the current list
$getQ = <<<EOS
SELECT *
FROM taxList
WHERE conid = ?
ORDER BY taxField;
EOS;

$taxList = [];
$getR = dbSafeQuery($getQ, 'i', array($conid));
while ($taxL = $getR->fetch_assoc()) {
    $taxList[] = $taxL;
}
$getR->free();

$response['taxList'] = $taxList;

if ($hrtime) {
    $endHRtime = hrtime(true);
    $intervalTime = $endHRtime - $startHRtime;
    $secs = intval($intervalTime / 1000000000);
    $ns = $intervalTime % 1000000000;
    $response['success'] = sprintf("Call took %d.%09d seconds", $secs, $ns);
}
ajaxSuccess($response);
