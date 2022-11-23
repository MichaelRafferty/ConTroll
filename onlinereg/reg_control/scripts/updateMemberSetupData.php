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
$year = $conid;

//var_error_log($_POST);
//ajax_request_action: which save button pushed (memtype, category, curage, nextage
//tabledata: array of table data
//tablename: database table
//indexcol: key column


$action=$_POST['ajax_request_action'];
$table = $_POST['tablename'];
$keyfield = $_POST['indexcol'];
$data = $_POST['tabledata'];
$inserted = 0;
$updated = 0;
$deleted = 0;
$sortorder = 10;

// build list of keys to still exist
$keys = '';
$first = true;
foreach ($data as $row ) {
    $keys .= ($first ? "'" : ",'") . sql_safe($row[$keyfield]) . "'";
    $first = false;
}

//error_log("Keys to keep = ($keys)");
switch ($action) {
    case 'nextage':
        $year = $nextconid;
    case 'curage':
        $delsql = "DELETE FROM ageList WHERE conid = ? AND ageType not in ( $keys );";
        $addupdsql = <<<EOS
INSERT INTO ageList(conid, ageType, label, shortname, sortorder)
VALUES(?,?,?,?,?)
ON DUPLICATE KEY UPDATE label=?, shortname=?, sortorder=?
EOS;
        $instypes = 'isssissi';
        //error_log("Delete sql = /$delsql/");
        $deleted += dbSafeCmd($delsql, 'i', array($year));
        // now the inserts and updates, rows effected = 1 for insert or 2 for update
        $sort_order = 10;
        foreach ($data as $row ) {
            $roworder = $row['sortorder'];
            if ($roworder >= 0 && $roworder < 900) {
                $roworder = $sort_order;
                $sort_order += 10;
            }
            $numrows = dbSafeCmd($addupdsql, $instypes, array(
                $year,
                $row['ageType'],
                $row['label'],
                $row['shortname'],
                $roworder,   
                $row['label'],
                $row['shortname'],
                $roworder
                ));
            switch ($numrows) {
                case 1:
                    $inserted++;
                    break;
                case 2:
                    $updated++;
                    break;
            }
        }
        break;
    case 'memtype':
        // first the deletes
        $delsql = "DELETE FROM memTypes WHERE memType not in ( $keys );";
        $addupdsql = <<<EOS
INSERT INTO memTypes(memType, active, sortorder)
VALUES(?,?,?)
ON DUPLICATE KEY UPDATE active=?, sortorder=?
EOS;
        $instypes = 'ssisi';
        //error_log("Delete sql = /$delsql/");
        $deleted += dbCmd($delsql);
        // now the inserts and updates, rows effected = 1 for insert or 2 for update
        $sort_order = 10;
        foreach ($data as $row ) {
            $roworder = $row['sortorder'];
            if ($roworder >= 0 && $roworder < 900) {
                $roworder = $sort_order;
                $sort_order += 10;
            }
            $numrows = dbSafeCmd($addupdsql, $instypes, array(
                $row['memType'],
                $row['active'],
                $roworder,
                $row['active'],
                $roworder
                ));
            switch ($numrows) {
                case 1:
                    $inserted++;
                    break;
                case 2:
                    $updated++;
                    break;
            }
        }
        break;
    case 'category':
        $delsql = "DELETE FROM memCategories WHERE memCategory not in ( $keys );";
        $addupdsql = <<<EOS
INSERT INTO memCategories(memCategory, active, sortorder)
VALUES(?,?,?)
ON DUPLICATE KEY UPDATE active=?, sortorder=?
EOS;
        $instypes = 'ssisi';
        //error_log("Delete sql = /$delsql/");
        $deleted += dbCmd($delsql);
        // now the inserts and updates, rows effected = 1 for insert or 2 for update
        $sort_order = 10;
        foreach ($data as $row ) {
            $roworder = $row['sortorder'];
            if ($roworder >= 0 && $roworder < 900) {
                $roworder = $sort_order;
                $sort_order += 10;
            }
            $numrows = dbSafeCmd($addupdsql, $instypes, array(
                $row['memCategory'],
                $row['active'],
                $roworder,
                $row['active'],
                $roworder
                ));
            switch ($numrows) {
                case 1:
                    $inserted++;
                    break;
                case 2:
                    $updated++;
                    break;
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
