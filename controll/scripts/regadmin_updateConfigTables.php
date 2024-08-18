<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

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

if (!isset($_POST) || !isset($_POST['ajax_request_action']) || !isset($_POST['tablename'])
    || !isset($_POST['indexcol']) || !isset($_POST['tabledata'])) {
    $response['error'] = 'Invalid Parameters';
    ajaxSuccess($response);
    exit();
}


$con = get_conf('con');
$conid=$con['id'];
$nextconid=$conid + 1;

//var_error_log($_POST);

$action=$_POST['ajax_request_action'];
$tablename=$_POST['tablename'];
$keyfield = $_POST['indexcol'];
try {
    $tabledata = $_POST['tabledata'];
    if ($tablename == 'customText') {
        $tabledata = urldecode(base64_decode($tabledata));
        }
    $tabledata = json_decode($tabledata, true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

$response['table'] = $tablename;
$inserted = 0;
$updated = 0;
$deleted = 0;
$sortorder = 10;

if ($tablename != 'customText') {
// build list of keys to delete
    $delete_keys = '';
    $first = true;
// compute delete keys in the array and redo the sort order
    $sort_order = 10;
    foreach ($tabledata as $index => $row) {
        if (array_key_exists('to_delete', $row) && $row['to_delete'] == 1 && array_key_exists($keyfield, $row)) {
            $delete_keys .= ($first ? "'" : ",'") . sql_safe($row[$keyfield]) . "'";
            $first = false;
        }
        else {
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
}

switch ($tablename) {
    case 'policy':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM policies WHERE policy in (?);";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbSafeCmd($delsql, 'i', array($delete_keys));
        }
        $inssql = <<<EOS
INSERT INTO policies (policy, prompt, description, sortOrder, required, defaultValue, createDate, updateDate, updateBy, active)
VALUES (?,?,?,?,?,?,NOW(),NOW(),?,?);
EOS;
        $updsql = <<<EOS
UPDATE policies
SET policy = ?, prompt = ?, description = ?, required = ?, defaultValue = ?, updateBy = ?, active = ?, sortorder = ?
WHERE policy = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($tabledata as $row ) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (array_key_exists('policyKey', $row)) { // if key is there, it's an update
                // policy = ?, prompt = ?, description = ?, required = ?, defaultValue = ?, updateBy = ?, active = ?, sortorder = ?
                $numrows = dbSafeCmd($updsql, 'sssssisis', array($row['policy'], $row['prompt'], $row['description'],
                    $row['required'], $row['defaultValue'], $user_perid, $row['active'],
                    $row['sortorder'], $row['policyKey']));
                $updated += $numrows;
            }
        }

        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        foreach ($tabledata as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (!array_key_exists('policyKey', $row)) { // if key is not there, its an insert
                // policy, prompt, description, sortOrder, required, defaultValue, updateBy, active)
                $numrows = dbSafeInsert($inssql, 'sssissis', array($row['policy'], $row['prompt'], $row['description'],
                    $row['sortOrder'], $row['required'], $row['defaultValue'], $user_perid, $row['active']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        break;

    case 'interests':
        if ($delete_keys != '') {
            $delsql = 'DELETE FROM interests WHERE interest in (?);';
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbSafeCmd($delsql, 'i', array ($delete_keys));
        }
        $inssql = <<<EOS
INSERT INTO interests (interest, description, notifyList, sortOrder, createDate, updateDate, updateBy, active, csv)
VALUES (?,?,?,?,NOW(),NOW(),?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE interests
SET interest = ?, description = ?, notifyList = ?, csv = ?, updateBy = ?, active = ?, sortorder = ?
WHERE interest = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($tabledata as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (array_key_exists('interestKey', $row)) { // if key is there, it's an update
                // interest = ?, description = ?, notifyList = ?, csv = ?, updateBy = ?, active = ?, sortorder = ?
                $numrows = dbSafeCmd($updsql, 'ssssisis', array ($row['interest'], $row['description'], $row['notifyList'],
                      $row['csv'], $user_perid, $row['active'], $row['sortorder'], $row['interestKey']));
                $updated += $numrows;
            }
        }

        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        foreach ($tabledata as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (!array_key_exists('interestKey', $row)) { // if key is not there, its an insert
                // interest, description, notifyList, sortOrder, createDate, updateDate, updateBy, active, csv)
                $numrows = dbSafeInsert($inssql, 'sssiiss', array ($row['interest'], $row['description'], $row['notifyList'],
                    $row['sortOrder'], $user_perid, $row['active'], $row['csv']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        break;

    case 'customText':
        $updsql = <<<EOS
UPDATE controllTxtItems
SET contents = ?
WHERE appName = ? AND appPage = ? AND appSection = ? AND txtItem = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($tabledata as $row) {
            $numrows = dbSafeCmd($updsql, 'sssss',
                 array($row['contents'], $row['appName'], $row['appPage'], $row['appSection'], $row['txtItem']));
            $updated += $numrows;
        }
        break;

    default:
        $response['error'] = 'Invalid table';
}

$response['success'] = "$tablename updated: $inserted added, $updated changed, $deleted removed.";
ajaxSuccess($response);
?>
