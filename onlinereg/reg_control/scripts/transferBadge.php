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

if(!isset($_POST) || !isset($_POST['perid']) || !isset($_POST['badge'])
    || ($_POST['badge'] == '') || ($_POST['perid'] == '')) {
    $response['error'] = "Missing Information";
    ajaxSuccess($response);
    exit();
}

$query = "UPDATE reg SET perid='" . sql_safe($_POST['perid']) . "'"
    . " WHERE id='" .sql_safe($_POST['badge']) . "';";

$response['query'] = $query;
dbQuery($query);

ajaxSuccess($response);
?>
