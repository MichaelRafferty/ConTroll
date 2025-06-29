<?php
global $db_ini;

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = "reg_staff";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET) || !isset($_GET['year'])) {
    $response['error'] = "Invalid Query";
    ajaxSuccess($response);
    exit();
}

$con=get_con();
$conid= $con['id'];

$id = 0;
$year = $_GET['year'];
$type= $_GET['type'];
if ($year == 'current') {
    $id = $conid;
} else if ($year == 'next') {
    $id = $conid + 1;
} else {
    $response['error'] = "Invalid year";
    ajaxSuccess($response);
    exit();
}

$response['conlist'] = null;
$response['year'] = $year;
$response['conid'] = $id;

$conlistSQL = <<<EOS
SELECT id, name, label, startdate, enddate
FROM conlist
WHERE id = ?;
EOS;

$result = dbSafeQuery($conlistSQL, 'i', array($id));
if($result->num_rows == 1) {
    $currentcondata = $result->fetch_assoc();
    if ($type == 'all' || $type = 'conlist') {
    $response['conlist'] = $currentcondata;
    }
} else {
    $response['conlist'] = null;
}



if ($type == 'all' || $type = 'memlist') {
    $memSQL = <<<EOS
SELECT m.id, m.id AS memlistkey,
    m.conid,
    m.sort_order,
    m.memCategory,
    m.memType,
    m.memAge,
    m.shortname,
    m.label,
    m.notes,
    m.price,
    m.startdate,
    m.enddate,
    m.atcon,
    m.online,
    m.glNum,
    m.glLabel,
    count(r.id) as uses
FROM memLabel m
LEFT OUTER JOIN reg r ON (r.memId = m.id)
WHERE ((m.conid = ? and m.memCategory != 'yearahead') OR (m.conid = ? AND m.memCategory in ('rollover', 'yearahead')))
GROUP BY m.id, m.conid,m.sort_order,m.memCategory,m.memType,m.memAge,m.shortname,m.label,m.price,m.startdate,m.enddate,m.atcon,m.online,
         m.glNum,m.glLabel
ORDER BY m.conid, m.sort_order, m.memCategory, m.memType, m.memAge, m.startdate;
EOS;
    $result = dbSafeQuery($memSQL, 'ii', array($id, $id+1));
    $memlist = array();
    if($result->num_rows >= 1) {
        while($memtype = $result->fetch_assoc()) {
            array_push($memlist, $memtype);
        }
        $response['memlist'] = $memlist;
    } else {
        $response['memlist'] = null;
    }

    $result = dbQuery("SELECT memType FROM memTypes WHERE active = 'Y' ORDER BY sortorder;");
    $memTypes = array();
    if($result->num_rows >= 1) {
        while($memtype = $result->fetch_assoc()) {
            array_push($memTypes, $memtype['memType']);
        }
        $response['memTypes'] = $memTypes;
    } else {
        $response['memTypes'] = null;
    }

    $result = dbQuery("SELECT memCategory FROM memCategories WHERE active = 'Y' ORDER BY sortorder;");
    $memCats = array();
    if($result->num_rows >= 1) {
        while($memcat = $result->fetch_assoc()) {
            array_push($memCats, $memcat['memCategory']);
        }
        $response['memCats'] = $memCats;
    } else {
        $response['memCats'] = null;
    }

    $result = dbSafeQuery("SELECT ageType FROM ageList WHERE conid = ? ORDER BY sortorder;", 'i', array($id));
    $ageTypes = array();
    if($result->num_rows >= 1) {
        while($agetype = $result->fetch_assoc()) {
            array_push($ageTypes, $agetype['ageType']);
        }
        $response['ageTypes'] = $ageTypes;
    } else {
        $response['ageTypes'] = null;
    }
}
ajaxSuccess($response);
?>
