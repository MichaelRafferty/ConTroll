<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!isset($_POST) || !isset($_POST['perid']) || !isset($_POST['badge'])
    || ($_POST['badge'] == '') || ($_POST['perid'] == '')) {
    $response['error'] = "Missing Information";
    ajaxSuccess($response);
    exit();
}

$from = $_POST['badge'];
$to = $_POST['perid'];

$query = "UPDATE reg SET perid=? WHERE id=?;";

$response['query'] = $query;
$num_rows = dbSafeCmd($query, 'ii', array($to, $from));

if ($num_rows == false) {
    $response['error'] = 'Database error transferring badge';
} else if ($num_rows == 1) {
    $response['message'] = "Badge transferred from $from to $to";
}

ajaxSuccess($response);
?>
