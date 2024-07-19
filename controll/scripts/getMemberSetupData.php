<?php
global $db_ini;

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$type = $_POST['type'];

$con=get_con();
$conid= $con['id'];
$nextconid = $conid + 1;

$response['current_agelist'] = null;
$response['next_agelist'] = null;
$response['current_id'] = $conid;
$response['next_id'] = $nextconid;
$ageSQL = <<<EOS
SELECT a.conid,a.ageType, a.label, a.shortname, a.badgeFlag, a.sortorder, count(l.id) uses, a.ageType as agekey
FROM ageList a
LEFT OUTER JOIN memList l ON (a.conid = l.conid and a.ageType = memAge)
WHERE a.conid = ?
GROUP BY a.conid, a.ageType, a.label, a.shortname, a.sortorder
ORDER BY a.sortorder, a.ageType
EOS;

if ($type == 'memType' || $type == 'all') {
    $typeSQL = <<<EOS
SELECT m.memType, m.active, m.sortorder, count(l.id) uses, m.memType AS memtypekey
FROM memTypes m
LEFT OUTER JOIN memList l ON (l.memType = m.memType)
GROUP BY m.memType, m.active, m.sortorder
ORDER BY active DESC, sortorder, memType
EOS;

    $result = dbQuery($typeSQL);
    $typelist = array();
    if($result->num_rows >= 1) {
        while($memtype = $result->fetch_assoc()) {
            array_push($typelist, $memtype);
        }
    }
    $response['memtypes'] = $typelist;
}

if ($type == 'memCat' || $type == 'all') {
    $catSQL = <<<EOS
SELECT m.memCategory, m.badgeLabel, m.onlyOne, m.standAlone, m.variablePrice, m.active, m.sortorder, count(l.id) uses, m.memCategory AS memcatkey
FROM memCategories m
LEFT OUTER JOIN memList l ON (l.memCategory = m.memCategory)
GROUP BY m.memCategory, m.active, m.sortorder
ORDER BY active DESC, sortorder, memCategory
EOS;

    $result = dbQuery($catSQL);
    $catlist = array();
    if($result->num_rows >= 1) {
        while($memcat = $result->fetch_assoc()) {
            array_push($catlist, $memcat);
        }
    }
    $response['categories'] = $catlist;
}

if ($type == 'curage' || $type == 'all') {


    $result = dbSafeQuery($ageSQL, 'i', array($conid));
    $agelist = array();
    if($result->num_rows >= 1) {
        while($memage = $result->fetch_assoc()) {
            array_push($agelist, $memage);
        }
    }
    $response['current_agelist'] = $agelist;
}

if ($type == 'nextage' || $type == 'all') {
    $result = dbSafeQuery($ageSQL, 'i', array($nextconid));
    $agelist = array();
    if($result->num_rows >= 1) {
        while($memage = $result->fetch_assoc()) {
            array_push($agelist, $memage);
        }
    }
    $response['next_agelist'] = $agelist;
}

ajaxSuccess($response);
?>
