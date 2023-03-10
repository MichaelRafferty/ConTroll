<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "../lib/base.php";
require_once "../../../lib/ajax_functions.php";

$check_auth = google_init("ajax");
$perm = "artist";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET) or !isset($_GET['id'])) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$vendor = sql_safe($_GET['id']);

$perQuery = "SELECT P.id, concat_ws(' ', P.first_name, P.middle_name, P.last_name) as full_name, P.address, P.addr_2, concat_ws(' ', P.city, P.state, P.zip) as locale, P.badge_name, P.email_addr, P.phone, P.active, P.banned";

$perQuery .= " FROM vendors as V JOIN perinfo as P on P.email_addr=V.email"
    . " WHERE V.id='$vendor';";


$res = dbQuery($perQuery);
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
