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

$con=get_con();
$conid= $con['id'];
$nextconid = $conid + 1;

$response['current_agelist'] = null;
$response['next_agelist'] = null;
$response['current_id'] = $conid;
$response['next_id'] = $nextconid;

$typeSQL = <<<EOS
SELECT m.memType, m.active, m.sort_order, count(l.id) uses
FROM memTypes m
LEFT OUTER JOIN memList l ON (l.memType = m.memType)
GROUP BY m.memType, m.active, m.sort_order
ORDER BY active DESC, sort_order, memType
EOS;

$result = dbQuery($typeSQL);
$typelist = array();
if($result->num_rows >= 1) {
    while($memtype = $result->fetch_assoc()) {
        array_push($typelist, $memtype);
    }
}
$response['memtypes'] = $typelist;

$catSQL = <<<EOS
SELECT m.memCategory, m.active, m.sort_order, count(l.id) uses
FROM memCategories m
LEFT OUTER JOIN memList l ON (l.memCategory = m.memCategory)
GROUP BY m.memCategory, m.active, m.sort_order
ORDER BY active DESC, sort_order, memCategory
EOS;

$result = dbQuery($catSQL);
$catlist = array();
if($result->num_rows >= 1) {
    while($memcat = $result->fetch_assoc()) {
        array_push($catlist, $memcat);
    }
}
$response['categories'] = $catlist;

$ageSQL = <<<EOS
SELECT a.conid,a.ageType, a.label, a.shortname, a.sortorder, count(l.id) uses
FROM ageList a
LEFT OUTER JOIN memList l ON (a.conid = l.conid and a.ageType = memAge)
WHERE a.conid = ?
GROUP BY a.conid, a.ageType, a.label, a.shortname, a.sortorder
ORDER BY a.sortorder, a.ageType
EOS;

$result = dbSafeQuery($ageSQL, 'i', array($conid));
$agelist = array();
if($result->num_rows >= 1) {
    while($memage = $result->fetch_assoc()) {
        array_push($agelist, $memage);
    }
}
$response['current_agelist'] = $agelist;

$result = dbSafeQuery($ageSQL, 'i', array($nextconid));
$agelist = array();
if($result->num_rows >= 1) {
    while($memage = $result->fetch_assoc()) {
        array_push($agelist, $memage);
    }
}
$response['next_agelist'] = $agelist;

ajaxSuccess($response);
?>
