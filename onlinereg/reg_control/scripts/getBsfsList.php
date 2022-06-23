<?php
global $ini;
if (!$ini)
    $ini = parse_ini_file(__DIR__ . "/../../../config/reg_conf.ini", true);
if ($ini['reg']['https'] <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

$check_auth = google_init("ajax");
$perm = "bsfs";

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
    . " JOIN bsfs as B ON B.perid=P.id ";
$entryQ_where = "";
$entryQ_order = "ORDER BY B.type ASC, P.last_name DESC;";

$entryQ = $entryQ_fields . $entryQ_tables . $entryQ_where . $entryQ_order;

$response['query']=$entryQ;
$response['bsfs']=array();

$entryR = dbQuery($entryQ);
while($bsfs = fetch_safe_assoc($entryR)) {
  array_push($response['bsfs'], $bsfs);
}


ajaxSuccess($response);
?>
