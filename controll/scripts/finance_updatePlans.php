<?php
global $db_ini;

require_once "../lib/base.php";
require_once '../../lib/paymentPlans.php';

$check_auth = google_init("ajax");
$perm = "finance";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

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

$con = get_conf('con');
$conid=$con['id'];

if (!(array_key_exists('ajax_request_action', $_POST) && array_key_exists("tablename", $_POST))) {
    $response['error'] = 'Parameter Missing';
    ajaxSuccess($response);
    exit();
}
$action=$_POST['ajax_request_action'];
if ($action != 'plans') {
    $response['error'] = 'Request Error';
    ajaxSuccess($response);
    exit();
}
$tablename=$_POST['tablename'];
try {
    $tabledata = json_decode($_POST['tabledata'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

$delete_keys = '';
$deleteArray = [];
$first = true;
// compute delete keys in the array and redo the sort order
$sort_order = 10;
foreach ($tabledata as $index => $row) {
    if (array_key_exists('to_delete', $row) && $row['to_delete'] == 1 && array_key_exists('id', $row)) {
        $delete_keys .= ($first ? "'" : ",'") . sql_safe($row['id']) . "'";
        $deleteArray[] = $row['id'];
        $first = false;
    } else {
        if (array_key_exists('sortorder', $row))
            $roworder = $row['sortorder'];
        else
            $roworder = 500;

        if ($roworder >= 0 && $roworder < 900) {
            $tabledata[$index]['sortorder'] = $sort_order;
            $sort_order += 10;
        }
    }
}
$deleted = 0;
if ($delete_keys != '') {
    $delsql = 'DELETE FROM paymentPlans WHERE id = ?;';
    web_error_log("Delete sql = /$delsql/");
    foreach ($deleteArray as $key) {
        web_error_log("Delete key = /$key/");
        $deleted += dbSafeCmd($delsql, 'i', array($key));
    }
}

$inssql = <<<EOS
INSERT INTO paymentPlans(name, description, catList, memList, excludeList, portalList, downPercent, downAmt, minPayment, numPaymentMax,
     payByDate, payType, modify, reminders, downIncludeNonPlan, lastPaymentPartial, active, sortorder, createDate, updateDate, updateBy)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),?);
EOS;
$updsql = <<<EOS
UPDATE paymentPlans
SET name = ?, description = ?, catList = ?, memList = ?, excludeList = ?, portalList = ?, downPercent = ?, downAmt = ?, minPayment = ?,
    numPaymentMax = ?, payByDate = ?, payType = ?, modify = ?, reminders = ?, downIncludeNonPlan = ?, lastPaymentPartial = ?, 
    active = ?, sortorder = ?, updateDate = NOW(), updateBy = ?
WHERE id = ?;
EOS;
// now the updates, do the updates first in case we need to insert a new row with the same older key
$inserted = 0;
$updated = 0;

foreach ($tabledata as $row) {
    if (array_key_exists('to_delete', $row)) {
        if ($row['to_delete'] == 1)
            continue;
    }
    if (array_key_exists('id', $row) && $row['id'] != null) { // if key is there, it's an update
        $numrows = dbSafeCmd($updsql, 'ssssssdddisssssssiii', array (
            $row['name'], $row['description'], $row['catList'], $row['memList'], $row['excludeList'], $row['portalList'],
            $row['downPercent'], $row['downAmt'], $row['minPayment'], $row['numPaymentMax'], $row['payByDate'], $row['payType'],
            $row['modify'], $row['reminders'], $row['downIncludeNonPlan'], $row['lastPaymentPartial'], $row['active'], $row['sortorder'],
            $user_perid, $row['id']));
        $updated += $numrows;
    }
}

// now the inserts, do the inserts last in case we need to insert a new row with the same older key
foreach ($tabledata as $row) {
    if (array_key_exists('to_delete', $row)) {
        if ($row['to_delete'] == 1)
            continue;
    }
    if (!(array_key_exists('id', $row) && $row['id'] != null && $row['id'] > 0)) { // if key is not there, its an insert
        // INSERT INTO paymentPlans(name, description, catList, memList, excludeList, portalList, downPercent, downAmt, minPayment, numPaymentMax,
        //     payByDate, payType, modify, reminders, downIncludeNonPlan, lastPaymentPartial, active, sortorder, createDate, updateDate, updateBy)
        $numrows = dbSafeInsert($inssql, 'ssssssdddisssssssii', array ($row['name'], $row['description'], $row['catList'],
            $row['memList'], $row['excludeList'], $row['portalList'], $row['downPercent'], $row['downAmt'], $row['minPayment'],
            $row['numPaymentMax'], $row['payByDate'], $row['payType'], $row['modify'], $row['reminders'], $row['downIncludeNonPlan'],
            $row['lastPaymentPartial'], $row['active'], $row['sortorder'], $user_perid));
        if ($numrows !== false)
            $inserted++;
    }
}

$response['success'] = "Payment Plans updated: $inserted added, $updated changed, $deleted removed.";
// now get the plan data again
$response['paymentPlans'] = getPlanConfig();

ajaxSuccess($response);
?>
