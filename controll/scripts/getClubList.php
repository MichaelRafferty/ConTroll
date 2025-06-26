<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "club";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];

$entryQ_fields = "SELECT concat_ws(' ', P.first_name, P.middle_name, P.last_name, P.suffix) as name, B.type, B.year, B.perid, B.id ";
$entryQ_tables = "FROM perinfo as P"
    . " JOIN club as B ON B.perid=P.id ";
$entryQ_where = "";
$entryQ_order = "ORDER BY B.type ASC, P.last_name DESC;";

$entryQ = $entryQ_fields . $entryQ_tables . $entryQ_where . $entryQ_order;

$response['query']=$entryQ;
$response['club']=array();

$entryR = dbQuery($entryQ);
while($club = fetch_safe_assoc($entryR)) {
  array_push($response['club'], $club);
}

ajaxSuccess($response);
