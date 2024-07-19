<?php
require_once "../lib/base.php";
require_once "../lib/getCouponData.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['status'] = 'error';
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$response = getCouponData($response);

ajaxSuccess($response);
?>
