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

$response['conlist'] = null;

$result = dbSafeQuery("SELECT id, name, label, CAST(startdate AS DATE) AS startdate, CAST(enddate as DATE) AS enddate FROM conlist WHERE id = ?;", 'i', array($id));

if($result->num_rows == 1) {
    $response['conlist'] = fetch_safe_assoc($result);
    $response['conlist-type'] = 'actual';
} else {

    $sql = <<<EOS
SELECT
	id + 1 as id,
    CASE
		WHEN id > 900 THEN REPLACE(name, MOD(id, 100), MOD(id + 1, 100))
        ELSE REPLACE(name, id, id + 1)
	END AS name,
    REPLACE(label, id, id + 1) as label,
    CAST (CASE
		WHEN WEEK(startdate) = WEEK(date_add(startdate, INTERVAL 52 WEEK)) then DATE_ADD(startdate, INTERVAL 52 WEEK)
        ELSE DATE_ADD(startdate, INTERVAL 53 WEEK)
	END AS DATE) AS startdate,
    CAST (CASE
		WHEN WEEK(enddate) = WEEK(DATE_ADD(enddate, INTERVAL 52 WEEK)) THEN DATE_ADD(enddate, INTERVAL 52 WEEK)
        ELSE DATE_ADD(enddate, INTERVAL 53 WEEK)
	END AS DATE) AS enddate,
    NOW() AS create_date
FROM conlist
WHERE id = ?;
EOS;

    $result = dbSafeQuery($sql, 'i', array($conid));

    if($result->num_rows == 1) {
        $response['conlist'] = fetch_safe_assoc($result);
        $response['conlist-type'] = 'proposed';
    }
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
WHERE ((conid = ? and memCategory != 'yearahead') OR (conid = ? AND memCategory in ('rollover', 'yearahead')))
ORDER BY conid, sort_order, memCategory, memType, memAge, startdate;
EOS;

$result = dbSafeQuery($memSQL, 'ii', array($id, $id+1));
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
