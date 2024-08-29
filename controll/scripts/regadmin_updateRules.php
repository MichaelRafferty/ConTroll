<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid=$con['id'];
$nextconid=$conid + 1;

//var_error_log($_POST);

if (!(array_key_exists('action', $_POST) && array_key_exists('rules', $_POST) && array_key_exists('items', $_POST))) {
    $response['error'] = 'Argument Error';
    ajaxSuccess($response);
    exit();
}
$action=$_POST['action'];
try {
    $rules = json_decode($_POST['rules'], true, 512, JSON_THROW_ON_ERROR);
    $items = json_decode($_POST['items'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

$response['warn'] = 'NOT YET';

ajaxSuccess($response);
?>
