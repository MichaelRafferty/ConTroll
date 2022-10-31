<?php
global $db_ini;

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = "search";

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
if ($year == 'current') {
    $id = $conid;
} else if ($year == 'next') {
    $id = $conid + 1;
} else {
    $response['error'] = "Invalid year";
    ajaxSuccess($response);
    exit();
}

$result = dbSafeQuery("SELECT id, name, label, CAST(startdate AS DATE) AS startdate, CAST(enddate as DATE) AS enddate FROM conlist WHERE id = ?;", 'i', array($id));

if($result->num_rows == 1) {
    $response['conlist'] = fetch_safe_assoc($result);
} else {
    $response['conlist'] = null;
}

$memSQL = <<<EOS
SELECT id,
    conid,
    sort_order,
    memCategory,
    memType,
    memAge,
    shortname,
    label,
    memGroup,
    price,
    startdate,
    enddate,
    atcon,
    online
FROM memLabel
WHERE conid = ?
ORDER BY sort_order, memCategory, memType, memAge, startdate ;
EOS;

$result = dbSafeQuery($memSQL, 'i', array($id));
$memlist = array();
if($result->num_rows >= 1) {
    while($badgetype = $result->fetch_assoc()) {
        array_push($memlist, $badgetype);
    }
    $response['memlist'] = $memlist;
} else {
    $response['memlist'] = null;
}

ajaxSuccess($response);
?>
