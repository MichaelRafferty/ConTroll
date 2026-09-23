<?php
// update changed gl list configuration info and then returns the current list
require_once '../lib/base.php';
require_once '../lib/sessionAuth.php';

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
        // validate the fields
        if ($glNum === null || $glNum == '') {
            $error .= "For the field $glNum cannot be empty.<br/>";
        }
        if (!array_key_exists('glLabel', $row)) {
            $error .= "For the entry $glNum The tax rate cannot be empty.<br/>";
        } else if ($row['glLabel'] == '') {
            $error .= "For the entry $glNum The GL Label field cannot be empty.<br/>";
        }
    }
    if ($error != '') {
        $error .= 'Correct the missing data and save again.';
        $response['error'] = $error;
        ajaxSuccess($response);
        exit();
    }

    foreach ($data as $index => $row ) {
        if (array_key_exists('to_delete', $row) && $row['to_delete'] == 1 && array_key_exists('keyfield', $row)) {
            $delete_keys .= ($first ? "'" : ",'") . sql_safe($row['keyfield']) . "'";
            $first = false;
        } else {
            // trim all fields
            foreach ($row as $field => $value) {
                if ($value != null) {
                    $data[$index][$field] = trim($value);
                }
            }
        }
    }

    // rebuild the sort order with gaps
    $sortRows = [];
    $newrow = 800000;
    foreach ($data as $index => $row ) {
        if (array_key_exists('to_delete', $row) && $row['to_delete'] == 1 && array_key_exists('keyfield', $row))
            continue;
        if (array_key_exists('sortOrder', $row))
            $sortRows[$row['sortOrder']] = ['sortOrder' => $row['sortOrder'], 'key' => $row['glNum']];
        else {
            $sortRows[$newrow] = ['sortOrder' => $newrow, 'key' => $row['glNum']];
            $newrow++;
        }
    }
    // now loop over the sortRows array and compute the new sort order
    $keys = array_keys($sortRows);
    sort($keys);
    $sort_order = 10;
    $postSort = [];
    foreach ($keys as $key) {
        $row = $sortRows[$key];
        $roworder = $row['sortOrder'];
        if ($roworder >= 0 && $roworder < 900000) {
            $postSort[$row['key']] = $sort_order;
            $sort_order += 10;
        }
    }
    // now put back the new sort order
    foreach ($data as $index => $row ) {
        if (array_key_exists($row['glNum'], $postSort)) {
            $data[$index]['sortOrder'] = $postSort[$row['glNum']];
        }
    }

    if ($delete_keys != '') {
        $delsql = "DELETE FROM gl WHERE glNum in ( $delete_keys );";
        $deleted += dbCmd($delsql);
    }

    $insertSQL = <<<EOS
INSERT INTO gl(glNum,glLabel, description, sortOrder, active, updateBy)
VALUES (?, ?, ?, ?, ?, ?);
EOS;
    $updateSQL = <<<EOS
UPDATE gl SET glNum = ?, glLabel = ?, description = ?, sortOrder = ?, active = ?, updateBy = ?
WHERE glNum = ?;
EOS;

    // now the updates, do the updates first in case we need to insert a new row with the same older key
    foreach ($data as $row) {
        if (array_key_exists('to_delete', $row)) {
            if ($row['to_delete'] == 1)
                continue;
        }

        if (array_key_exists('keyfield', $row) && $row['keyfield'] != null && $row['keyfield'] != '') {
            $numrows = dbSafeCmd($updateSQL, 'sssisis',
                array ($row['glNum'], $row['glLabel'], $row['description'], $row['sortOrder'], $row['active'], $user_perid, $row['keyfield']));
            $updated += $numrows;
        }
    }

    // now the inserts, do the inserts last in case we need to insert a new row with the same older key
    foreach ($data as $row) {
        if (array_key_exists('to_delete', $row)) {
            if ($row['to_delete'] == 1)
                continue;
        }
        if (!array_key_exists('keyfield', $row) || $row['keyfield'] == null || $row['keyfield'] == '') {
            $numrows = dbSafeCmd($insertSQL, 'sssisi',
                array ($row['glNum'], $row['glLabel'], $row['description'], $row['sortOrder'], $row['active'], $user_perid));
            $inserted += $numrows;
        }
    }

    // now do the deletes

    $response['success'] .= "$tablename updated: $updated gl entries changed.<br/>";
}

// now get the current list
$selSQL = <<<EOS
WITH cnt AS (
    SELECT glNum, count(*) cnt FROM memList GROUP BY glNum
    UNION
    SELECT glNum, count(*) cnt FROM taxList GROUP BY glNum
    UNION
    SELECT glNum, count(*) cnt FROM exhibitsRegionYears GROUP BY glNum
    UNION
    SELECT revenueGlNum, count(*) cnt FROM exhibitsRegionYears GROUP BY revenueGlNum
    UNION
    SELECT mailinGLNum, count(*) cnt FROM exhibitsRegionYears GROUP BY mailinGLNum
    UNION
    SELECT glNum, count(*) cnt FROM exhibitsSpacePrices GROUP BY glNum
    UNION
    SELECT glNum, count(*) cnt FROM exhibitsSpaces GROUP BY glNum
), sum AS (
    SELECT glNum, sum(cnt) cnt FROM cnt GROUP BY glNum
)
SELECT g.glNum, g.glNum as keyfield, glLabel, description, sortOrder, active, createDate, updateDate, updateBy, IFNULL(s.cnt, 0) AS uses 
FROM gl g
LEFT OUTER JOIN sum s ON g.glNum = s.glNum
ORDER BY g.glNum;
EOS;
$glQ = dbQuery($selSQL);
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

$response['glList'] = $gl;

if ($hrtime) {
    $endHRtime = hrtime(true);
    $intervalTime = $endHRtime - $startHRtime;
    $secs = intval($intervalTime / 1000000000);
    $ns = $intervalTime % 1000000000;
    $response['success'] .= sprintf("Call took %d.%09d seconds", $secs, $ns);
}
ajaxSuccess($response);
