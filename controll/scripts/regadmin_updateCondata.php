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

$con = get_conf('con');
$conid=$con['id'];
$nextconid=$conid + 1;

//var_error_log($_POST);


$action=$_POST['ajax_request_action'];
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
//$data = $_POST['tabledata'];
$response['year'] = $action;

switch ($tablename) {
    case 'conlist':
        switch ($action) {
            case 'next':
            case 'current':
                $data = $tabledata[0];
                $sql = <<<EOS
INSERT INTO conlist(id, name, label, startdate, enddate, create_date)
VALUES(?,?,?,?,?,NOW())
ON DUPLICATE KEY UPDATE name=?, label=?, startdate=?, enddate=?;
EOS;
                $num_rows = dbSafeInsert($sql, "issssssss", array(
                    $data['id'],
                    $data['name'],
                    $data['label'],
                    $data['startdate'],
                    $data['enddate'],
                    $data['name'],
                    $data['label'],
                    $data['startdate'],
                    $data['enddate']
                ));
                if ($num_rows > 0) {
                    $response['success'] =  "Convention " . $data['id'] . " updated.";
                } else {
                    $response['success'] = "Nothing to change";
                }
                break;
            default:
                $response['error'] = "Invalid Request";
        }
        break;
    case "memlist":
        $data = $tabledata;
        // find keys to delete (somehow)
        $delete_keys = array();
        $delete_keys[$conid] = '';
        $delete_keys[$nextconid] = '';
        $first = array();
        $first[$conid] = true;
        $first[$nextconid] = true;
        $sort_order = 10;
        $yearahead_sortorder = 400;
        $rollover_sortorder = 500;
        foreach ($data as $index => $row ) {
            //$cidfound[$row['conid']] = true;
            if (array_key_exists('to_delete', $row) && $row['to_delete'] == 1 && array_key_exists('memlistkey', $row)) {
                $cid = $row['conid'];
                if (array_key_exists($cid, $first)) {
                    $delete_keys[$cid] .= ($first[$cid] ? "'" : ",'") . sql_safe($row['memlistkey']) . "'";
                    $first[$cid] = false;
                }
            } else {
                if (array_key_exists('sort_order', $row)) { // deal with table add rows now having sort order
                    $roworder = $row['sort_order'];
                } else {
                    $roworder = 10;
                }
                if (($roworder >= 0 && $roworder < 900) || ($roworder == -99999)) {
                    if ($row['memCategory'] == 'rollover') {
                        $data[$index]['sort_order'] = $rollover_sortorder;
                        $rollover_sortorder += 2;
                    } else if ($row['memCategory'] == 'yearahead') {
                        $data[$index]['sort_order'] = $yearahead_sortorder;
                        $yearahead_sortorder += 2;
                    } else {
                        $data[$index]['sort_order'] = $sort_order;
                        $sort_order += 2;
                    }
                }
            }
        }
        //error_log("Keys to delete =");
        //var_error_log($delete_keys);
        $deleted = 0;
        $inserted = 0;
        $updated = 0;
        if ($delete_keys[$conid] != '') {
            $delSQL = "DELETE FROM memList WHERE conid = ? AND id IN (" . $delete_keys[$conid] . ");";
            web_error_log("conid: $conid, delSQL = /$delSQL/");
            $deleted += dbSafeCmd($delSQL,  'i', array($conid));
        }
        if ($delete_keys[$nextconid] != '') {
            $delSQL = 'DELETE FROM memList WHERE conid = ? AND id IN (' . $delete_keys[$nextconid] . ');';
            web_error_log("conid: $nextconid, delSQL = /$delSQL/");
            $deleted += dbSafeCmd($delSQL, 'i', array($nextconid));
        }

        $addSQL = <<<EOS
INSERT INTO memList(conid,sort_order,memCategory,memType,memAge,label,notes,price,startdate,enddate,atcon,online)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
        $addtypes = 'iissssssssss';
        $updSQL = <<<EOS
UPDATE memList
SET sort_order = ?,memCategory = ?,memType = ?,memAge = ?,label = ?,notes = ?,price = ?,startdate = ?,enddate = ?,atcon = ?,online = ?
WHERE id = ?
EOS;
        $updtypes = 'issssssssssi';

        foreach ($data as $row) {
            if (!array_key_exists('notes', $row))
                $row['notes'] = null;
            if ($row['id'] < 0) {
                $paramarray= array($row['conid'],$row['sort_order'],$row['memCategory'],
                    $row['memType'],$row['memAge'],$row['shortname'],$row['notes'],$row['price'],$row['startdate'],
                    $row['enddate'],$row['atcon'],$row['online']);
                //web_error_log("add row: /$addSQL/, types '$addtypes', values:");
                //var_error_log($paramarray);
                $newid = dbSafeInsert($addSQL, $addtypes, $paramarray);
                if ($newid)
                    $inserted++;
            } else {
                $paramarray = array($row['sort_order'],$row['memCategory'],
                    $row['memType'],$row['memAge'],$row['shortname'],$row['notes'],$row['price'],$row['startdate'],
                    $row['enddate'],$row['atcon'],$row['online'], $row['id']);
                //web_error_log("update row: /$updSQL/, types = '$updtypes', values:");
                //var_error_log($paramarray);
                $updated += dbSafeCmd($updSQL, $updtypes, $paramarray);
            }
        }
        $response['success'] = "memList updated: $inserted added, $updated changed, $deleted removed.";
        //error_log($response['success']);
        break;
    case 'breaklist':
        if ($action == 'current')
            $year = $conid;
        else
            $year = $nextconid;

        // create next + 1 year conlist entry if it doesn't exist
        $sql = 'SELECT id FROM conlist WHERE id = ?';
        $r = dbSafeQuery($sql, 'i', array($year + 1));
        if ($r->num_rows == 0) {
            $sql = <<<EOS
INSERT INTO conlist(id, name, label, startdate, enddate, create_date)
SELECT
	id + 1 as id,
    CASE
		WHEN id > 900 THEN REPLACE(name, MOD(id, 100), MOD(id + 1, 100))
        ELSE REPLACE(name, id, id + 1)
	END AS name,
    REPLACE(label, id, id + 1) as label,
    CASE
		WHEN WEEK(startdate) = WEEK(date_add(startdate, INTERVAL 52 WEEK)) then DATE_ADD(startdate, INTERVAL 52 WEEK)
        ELSE DATE_ADD(startdate, INTERVAL 53 WEEK)
	END AS startdate,
    CASE
		WHEN WEEK(enddate) = WEEK(DATE_ADD(enddate, INTERVAL 52 WEEK)) THEN DATE_ADD(enddate, INTERVAL 52 WEEK)
        ELSE DATE_ADD(enddate, INTERVAL 53 WEEK)
	END AS enddate,
    NOW() AS create_date
FROM conlist
WHERE id = ?;
EOS;
            $newid = dbSafeInsert($sql, 'i', array($year));
        }

        // now create any missing agelist entries
        $inssql = <<<EOS
INSERT INTO ageList(conid, ageType, label, shortname, sortorder)
SELECT ?, a1.ageType, a1.label, a1.shortname, a1.sortorder
FROM ageList a1
LEFT OUTER JOIN ageList a2 ON (a2.conid = ? AND a2.ageType = a1.ageType)
WHERE a1.conid = ? and a2.conid IS NULL;
EOS;
        $paramarray = array($year + 1, $year + 1, $year);
        web_error_log("$inssql, types='ii',params:");
        var_error_log($paramarray);
        $numages = 0;
        $numages = dbSafeCmd($inssql, 'iii', $paramarray);
        if ($numages === false) {
            $response['error'] = 'Error creating new age table entries, see logs';
            ajaxSuccess($response);
            exit();
        }
        // create table of existing rows for
        $tmpsql = <<<EOS
CREATE TEMPORARY TABLE existing_memList
SELECT conid, memCategory,memType,memAge,label,startdate, enddate,atcon,`online`
FROM memList
WHERE conid >= ?;
EOS;
        $paramarray = array($year);
        web_error_log("$tmpsql, types='i',params:");
        var_error_log($paramarray);
        $numrows = dbSafeCmd($tmpsql, 'i', $paramarray);
        if ($numrows === false) {
            $response['error'] = 'Error creating temporary table, see logs';
            ajaxSuccess($response);
            exit();
        }
        $inssql = <<<EOS
INSERT INTO memList(conid,sort_order,memCategory,memType,memAge,label,price,startdate,enddate,atcon,online)
SELECT ? AS conid,m.sort_order,m.memCategory,m.memType,m.memAge,replace(m.label, ?, ?) AS label,m.price,? AS startdate,? AS enddate,m.atcon,m.online
FROM memList m
LEFT OUTER JOIN existing_memList e ON (
    e.memCategory = m.memCategory AND e.memType = m.memType AND e.memAge = m.memAge AND REPLACE(m.label, ?, ?) = e.label
    AND e.startdate = ? AND e.enddate = ? AND e.atcon = m.atcon AND e.online = m.online)
WHERE m.conid = ? AND e.conid IS NULL AND m.startdate = ? AND m.enddate = ?;
EOS;
        $typelist = 'issssssssiss';
        $data = $tabledata;
        $numrows = 0;
        foreach ($data as $row ) {
            $paramarray = array(
                $row['newconid'], // conid
                $row['oldconid'], // label prior str
                $row['newconid'], // label new str
                $row['newstart'], // startdate
                $row['newend'],  // enddate
                $row['oldconid'], // m.label prior string
                $row['newconid'], // m.label current string
                $row['newstart'], // e.startdate
                $row['newend'], // e.enddate
                $row['oldconid'], // m.conid
                $row['oldstart'], // m.startdate
                $row['oldend'] // m.enddate
            );
            web_error_log("$inssql, $typelist, params:");
            var_error_log($paramarray);
            $numrows += dbSafeCmd($inssql, $typelist, $paramarray);
        }
        $response['success'] = "ageList updated: $numages added, memList updated: $numrows added";
        break;

    default:
        $response['error'] = 'Invalid table';
}

ajaxSuccess($response);
?>
