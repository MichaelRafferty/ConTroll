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
        error_log("Delsql = /$delSQL/");
        $deleted += dbSafeCmd($delSQL, 'ii', array($conid, $nextconid));

        $addSQL = <<<EOS
INSERT INTO memlist(conid,sort_order,memCategory,memType,memAge,label,price,startdate,enddate,atcon,online)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
        $addtypes = 'iisssssssss';
        $updSQL = <<<EOS
UPDATE memlist
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
                $newid = dbSafeCmd($addSQL, $addtypes, array($row['conid'],$roworder,$row['memCategory'],
                    $row['memType'],$row['memAge'],$row['shortname'],$row['price'],$row['startdate'],
                    $row['enddate'],$row['atcon'],$row['online']));
                if ($newid)
                    $inserted++;
            } else {
                $updated += dbSafeCmd($updSQL, $updtypes, array($roworder,$row['memCategory'],
                    $row['memType'],$row['memAge'],$row['shortname'],$row['price'],$row['startdate'],
                    $row['enddate'],$row['atcon'],$row['online'], $row['id']));
            }
        }
        $response['success'] = "memList updated: $inserted added, $updated changed, $deleted removed.";
        //error_log($response['success']);
        break;
    default:
        $response['error'] = 'Invalid table';
}

ajaxSuccess($response);
?>
