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

if(!isset($_GET)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$con = get_conf("con");
$conid=$con['id'];

$query = <<<EOQ
SELECT DISTINCT P.id, concat_ws(' ', P.first_name, P.middle_name, P.last_name) as full_name,
    P.address, P.addr_2, concat_ws(' ', P.city, P.state, P.zip) as locale, P.legalName,
    P.badge_name, P.email_addr, P.phone, P.active, P.banned, M.label
FROM perinfo as P
LEFT OUTER JOIN reg R ON (R.perid = P.id AND R.conid = ?)
LEFT OUTER JOIN memLabel M ON (R.memId = M.id)
EOQ;

$query .= " WHERE concat_ws(' ', first_name, middle_name, last_name) LIKE ? OR legalName LIKE ?;";
if(isset($_GET['full_name'])) {
    $searchString = $_GET['full_name'];
    $searchString = str_replace(" ", "%", $searchString);
    }

$response['query'] = $query;
$searchString = "%$searchString%";
$res = dbSafeQuery($query, 'iss', array($conid, $searchString, $searchString));
if(!$res) {
  ajaxSuccess(array(
    "args"=>$_POST,
    "query"=>$query,
    "error"=>"query failed"));
  exit();
}
$results = array('active' => array(), 'inactive' => array());
while ($row = fetch_safe_assoc($res)) {
    if($row['active'] == 'Y') {
        array_push($results['active'], $row);
    } else {
        array_push($results['inactive'], $row);
    }
}

$response['count'] = $res->num_rows;
$response['results'] = $results;

ajaxSuccess($response);
?>
