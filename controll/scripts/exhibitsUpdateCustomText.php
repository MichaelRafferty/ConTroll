<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/customText.php";

$check_auth = google_init('ajax');
$perm = 'exhibitor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('user_perid', $_SESSION)) {
    $user_perid = $_SESSION['user_perid'];
}
else {
    ajaxError('Invalid credentials passed');
    return;
}

if (!isset($_POST) || !isset($_POST['ajax_request_action']) || !isset($_POST['tablename'])
    || !isset($_POST['indexcol']) || !isset($_POST['tabledata'])) {
    $response['error'] = 'Invalid Parameters';
    ajaxSuccess($response);
    exit();
}


$con = get_conf('con');
$conid=$con['id'];
$nextconid=$conid + 1;

//var_error_log($_POST);

$action=$_POST['ajax_request_action'];
$tablename=$_POST['tablename'];
$keyfield = $_POST['indexcol'];
try {
    $tabledata = $_POST['tabledata'];
    if ($tablename == 'customText') {
        $tabledata = urldecode(base64_decode($tabledata));
        }
    $tabledata = json_decode($tabledata, true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

$response['table'] = $tablename;
$inserted = 0;
$updated = 0;
$deleted = 0;
$sortorder = 10;

$updated = updateCustomText($tabledata);
$response['success'] = "$tablename updated: $updated changed";
$response['customText'] = getCustomText('exhibitor');
ajaxSuccess($response);
