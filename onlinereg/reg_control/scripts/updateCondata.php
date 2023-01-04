<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "admin";

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

//var_error_log($_POST);


$action=$_POST['ajax_request_action'];
$tablename=$_POST['tablename'];
$response['year'] = $action;

switch ($tablename) {
    case 'conlist':
        switch ($action) {
            case 'next':
            case 'current':
                $data = $_POST['tabledata'][0];
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
        $data = $_POST['tabledata'];
        // find keys to delete (somehow)
        $keys = array();
        $keys[$conid] = '';
        $keys[$nextconid] = '';
        $first = array();
        $first[$conid] = true;
        $first[$nextconid] = true;
        foreach ($data as $row ) {
            $cid = $row['conid'];
            $id = $row['id'];
            if (array_key_exists($cid, $first)) {
                $keys[$cid] .= ($first[$cid] ? "'" : ",'") . sql_safe($row['id']) . "'";
                $first[$cid] = false;
            }
        }
        //error_log("Keys to keep =");
        //var_error_log($keys);
        $deleted = 0;
        $inserted = 0;
        $updated = 0;
        $delSQL = "DELETE FROM memList WHERE (conid = ? AND memCategory != 'yearahead'";
        if ($keys[$conid] != '') {
            $delSQL .= " AND id NOT IN (" . $keys[$conid] . ")";
        }
        $delSQL .= ") OR (conid = ? AND memCategory in ('rollover', 'yearahead')";
        if ($keys[$nextconid] != '') {
            $delSQL .= " AND id NOT IN (" . $keys[$nextconid] . ")";
        }
        $delSQL .= ");";
        //web_error_log("Delsql = /$delSQL/, types 'ii', values: $conid, $nextconid");
        $deleted += dbSafeCmd($delSQL, 'ii', array($conid, $nextconid));

        $addSQL = <<<EOS
INSERT INTO memList(conid,sort_order,memCategory,memType,memAge,label,price,startdate,enddate,atcon,online)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
        $addtypes = 'iisssssssss';
        $updSQL = <<<EOS
UPDATE memList
SET sort_order = ?,memCategory = ?,memType = ?,memAge = ?,label = ?,price = ?,startdate = ?,enddate = ?,atcon = ?,online = ?
WHERE id = ?
EOS;
        $updtypes = 'isssssssssi';

        $sort_order = 10;
        $yearahead_sortorder = 400;
        $rollover_sortorder = 500;
        foreach ($data as $row) {
            $roworder = $row['sort_order'];
            if (($roworder >= 0 && $roworder < 900) || ($roworder == -99999)) {
                if ($row['memCategory'] == 'rollover') {
                    $roworder = $rollover_sortorder;
                    $rollover_sortorder += 2;
                } else if ($row['memCategory'] == 'yearahead'){
                    $roworder = $yearahead_sortorder;
                    $yearahead_sortorder += 2;
                } else {
                    $roworder = $sort_order;
                    $sort_order += 2;
                }
            }
            if ($row['id'] < 0) {
                $paramarray= array($row['conid'],$roworder,$row['memCategory'],
                    $row['memType'],$row['memAge'],$row['shortname'],$row['price'],$row['startdate'],
                    $row['enddate'],$row['atcon'],$row['online']);
                //web_error_log("add row: /$addSQL/, types '$addtypes', values:");
                //var_error_log($paramarray);                
                $newid = dbSafeCmd($addSQL, $addtypes, $paramarray);
                if ($newid)
                    $inserted++;
                $paramarray = array($roworder,$row['memCategory'],
                    $row['memType'],$row['memAge'],$row['shortname'],$row['price'],$row['startdate'],
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

        // first create any missing agelist entries
        $inssql = <<<EOS
INSERT INTO ageList(conid, ageType, label, shortname, sortorder)
SELECT ?, a1.ageType, a1.label, a1.shortname, a1.sortorder
FROM ageList a1
LEFT OUTER JOIN ageList a2 ON (a2.conid = ? AND a2.ageType = a1.ageType)
WHERE a1.conid = ? and a2.conid IS NULL;
EOS;
        $numages = dbSafeCmd($inssql, 'iii', array($year + 1, $year + 1, $year));
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
        $numrows = dbSafeCmd($tmpsql, 'i', array($year));
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
        $data = $_POST['tabledata'];
        $numrows = 0;
        foreach ($data as $row ) {
            $paramarr = array(
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
            //$result = dbSafeQuery($inssql, $typelist, $paramarr);
            //var_error_log($result);
            // while ($row = fetch_safe_assoc($result)) {
            //    var_error_log($row);
            //}
            $numrows += dbSafeInsert($inssql, $typelist, $paramarr);
        }
        $response['success'] = "ageList updated, $numages added, memList updated, $numrows added";
        break;

    default:
        $response['error'] = 'Invalid table';
}

ajaxSuccess($response);
?>
