<?php

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "artcontrol";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if(!$check_auth || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}
$response['error'] = "Success, but no code";

ajaxSuccess($response);
?>
