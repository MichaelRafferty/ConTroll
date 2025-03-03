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
INSERT INTO memList(conid,sort_order,memCategory,memType,memAge,label,notes,price,startdate,enddate,atcon,online,glNum,glLabel)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?);
EOS;
        $addtypes = 'iissssssssssss';
        $updSQL = <<<EOS
UPDATE memList
SET sort_order = ?,memCategory = ?,memType = ?,memAge = ?,label = ?,notes = ?,price = ?,startdate = ?,enddate = ?,atcon = ?,online = ?,
    glNum = ?, glLabel = ?
WHERE id = ?
EOS;
        $updtypes = 'issssssssssssi';

        foreach ($data as $row) {
            if (!array_key_exists('notes', $row))
                $row['notes'] = null;
            if ($row['id'] < 0) {
                $paramarray= array($row['conid'],$row['sort_order'],$row['memCategory'],
                    $row['memType'],$row['memAge'],$row['shortname'],$row['notes'],$row['price'],$row['startdate'],
                    $row['enddate'],$row['atcon'],$row['online'],$row['glNum'],$row['glLabel']);
                //web_error_log("add row: /$addSQL/, types '$addtypes', values:");
                //var_error_log($paramarray);
                $newid = dbSafeInsert($addSQL, $addtypes, $paramarray);
                if ($newid)
                    $inserted++;
            } else {
                $paramarray = array($row['sort_order'],$row['memCategory'],
                    $row['memType'],$row['memAge'],$row['shortname'],$row['notes'],$row['price'],$row['startdate'],
                    $row['enddate'],$row['atcon'],$row['online'],$row['glNum'],$row['glLabel'],$row['id']);
                //web_error_log("update row: /$updSQL/, types = '$updtypes', values:");
                //var_error_log($paramarray);
                $updated += dbSafeCmd($updSQL, $updtypes, $paramarray);
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
