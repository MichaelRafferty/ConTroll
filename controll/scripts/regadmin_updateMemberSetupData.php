<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || (!checkAuth($check_auth['sub'], $perm) &&
                            !checkAuth($check_auth['sub'], 'atcon'))) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid=$con['id'];
$nextconid=$conid + 1;
$year = $conid;

//var_error_log($_POST);
//ajax_request_action: which save button pushed (memtype, category, curage, nextage
//tabledata: array of table data
//tablename: database table
//indexcol: key column


$action=$_POST['ajax_request_action'];
$table = $_POST['tablename'];
$keyfield = $_POST['indexcol'];
try {
    $data = json_decode( $_POST['tabledata'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = "Caught exception on json_decode: " . $e->getMessage() . PHP_EOL . "JSON error: " . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}
//$data = $_POST['tabledata'];
$inserted = 0;
$updated = 0;
$deleted = 0;
$sortorder = 10;

// build list of keys to delete
$delete_keys = '';
$first = true;
// compute delete keys in the array and redo the sort order
$sort_order = 10;
foreach ($data as $index => $row ) {
    if (array_key_exists('to_delete', $row) && $row['to_delete'] == 1 && array_key_exists($keyfield, $row)) {
        $delete_keys .= ($first ? "'" : ",'") . sql_safe($row[$keyfield]) . "'";
        $first = false;
    } else {
        if (array_key_exists('sortorder', $row))
            $roworder = $row['sortorder'];
        else
            $roworder = 500;

        if ($roworder >= 0 && $roworder < 900) {
            $data[$index]['sortorder'] = $sort_order;
            $sort_order += 10;
        }
    }
}

//web_error_log("Keys to delete = ($delete_keys)");
switch ($action) {
    case 'nextage':
        $year = $nextconid;
        // fall into curage just a different year

    case 'curage':
        if ($delete_keys != '') {
            $delsql = "DELETE FROM ageList WHERE conid = ? AND ageType in ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbSafeCmd($delsql, 'i', array($year));
        }
        $inssql = <<<EOS
INSERT INTO ageList(conid, ageType, label, shortname, badgeFlag, sortorder)
VALUES(?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE ageList
SET ageType = ?, label = ?, shortname = ?, badgeFlag = ?, sortorder = ?
WHERE ageType = ? and conid = ?;
EOS;

        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($data as $row ) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (array_key_exists('agekey', $row)) { // if key is there, it's an update
                $numrows = dbSafeCmd($updsql, 'ssssisi', array($row['ageType'], $row['label'], $row['shortname'], $row['badgeFlag'], $row['sortorder'], $row['agekey'], $year));
                $updated += $numrows;
            }
        }

        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }
            if (!array_key_exists('agekey', $row)) { // if key is not there, its an insert
                $numrows = dbSafeInsert($inssql, 'issssi', array($year, $row['ageType'], $row['label'], $row['shortname'], $row['badgeFlag'], $row['sortorder']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        break;

    case 'memtype':
        // first the deletes
        if ($delete_keys != '') {
            $delsql = "DELETE FROM memTypes WHERE memType in ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO memTypes(memType, notes, active, sortorder)
VALUES(?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE memTypes
SET  memType = ?, notes = ?, active=?, sortorder=?
WHERE memType = ?;
EOS;
        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }

            if (array_key_exists('memtypekey', $row)) { // if key is there, it's an update
                $numrows = dbSafeCmd($updsql, 'sssis', array($row['memType'], $row['notes'], $row['active'], $row['sortorder'], $row['memtypekey']));
                $updated += $numrows;
            }
        }
        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }

            if (!array_key_exists('memtypekey', $row)) { // if key is not there, its an insert
                $numrows = dbSafeInsert($inssql, 'sssi', array($row['memType'],  $row['notes'], $row['active'], $row['sortorder']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        break;

    case 'category':
        // first the deletes
        if ($delete_keys != '') {
            $delsql = "DELETE FROM memCategories WHERE memCategory in ( $delete_keys );";
            web_error_log("Delete sql = /$delsql/");
            $deleted += dbCmd($delsql);
        }
        $inssql = <<<EOS
INSERT INTO memCategories(memCategory, onlyOne, standAlone, variablePrice, taxable, badgeLabel, notes, active, sortorder)
VALUES(?,?,?,?,?,?,?,?,?);
EOS;
        $updsql = <<<EOS
UPDATE memCategories
SET memCategory=?, onlyOne = ?, standAlone = ?, variablePrice = ?, taxable=?, badgeLabel = ?, notes=?, active=?, sortorder=?
WHERE memCategory=?;
EOS;
        // now the updates, do the updates first in case we need to insert a new row with the same older key
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }

            if (array_key_exists('memcatkey', $row)) { // if key is there, it's an update
                $numrows = dbSafeCmd($updsql, 'ssssssssis', array($row['memCategory'], $row['onlyOne'], $row['standAlone'], $row['variablePrice'],
                    $row['taxable'], $row['badgeLabel'],  $row['notes'], $row['active'], $row['sortorder'], $row['memcatkey']));
                $updated += $numrows;
            }
        }
        // now the inserts, do the inserts last in case we need to insert a new row with the same older key
        $sort_order = 10;
        foreach ($data as $row) {
            if (array_key_exists('to_delete', $row)) {
                if ($row['to_delete'] == 1)
                    continue;
            }

            if (!array_key_exists('memcatkey', $row)) { // if key is not there, its an insert
                $numrows = dbSafeInsert($inssql, 'ssssssssi', array($row['memCategory'], $row['onlyOne'], $row['standAlone'], $row['variablePrice'],
                   $row['taxable'], $row['badgeLabel'],  $row['notes'], $row['active'], $row['sortorder']));
                if ($numrows !== false)
                    $inserted++;
            }
        }
        break;

    default:
        $response['error'] = "Invalid Request";
        ajaxSuccess($response);
        exit();
}

$response['year'] = $year;
$response['success'] = "$table updated: $inserted added, $updated changed, $deleted removed.";
//error_log("$action = $action on year $year");
ajaxSuccess($response);
?>
