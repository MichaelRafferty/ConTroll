<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../../lib/reg_receipt.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('transid', $_POST)) {
    $transid = $_POST['transid'];
    $response = trans_receipt($transid);
} else {
    $response['error'] = 'Improper calling sequence';
}

ajaxSuccess($response);
exit();
