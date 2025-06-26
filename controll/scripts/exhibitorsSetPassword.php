<?php
global $db_ini;

require_once '../lib/base.php';
$check_auth = google_init('ajax');
$perm = 'exhibitor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];
if (!array_key_exists('type', $_POST)) {
    ajaxError('No Data');
}

$pwtype = $_POST['type'];
if ($pwtype == 'exhibitor') {
    if (!array_key_exists('exhibitorId', $_POST)) {
        ajaxError("No Data");
    }
    $exhibitorId = $_POST['exhibitorId'];
} else {
    if (!array_key_exists('exhibitorYearId', $_POST)) {
        ajaxError('No Data');
    }
    $exhibitorYearId = $_POST['exhibitorYearId'];
}


$str = str_shuffle(
    "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#" .
        "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#"
);

$len = rand(7,12);
$start = rand(0,strlen($str)-$len);

$newpasswd = substr($str, $start, $len);

$hash = password_hash($newpasswd, PASSWORD_DEFAULT);

if ($pwtype == 'exhibitor') {
    $pwQ = 'UPDATE exhibitors SET password=?, need_new=true where id=?;';
    $num_rows = dbSafeCmd($pwQ, 'si', array($hash, $exhibitorId));
} else {
    $pwQ = 'UPDATE exhibitorYears SET contactPassword=?, need_new=true where id=?;';
    $num_rows = dbSafeCmd($pwQ, 'si', array($hash, $exhibitorYearId));
}

if ($num_rows != 1) {
    $response['error'] = 'Database update failed';
}

$response['password'] = $newpasswd;

ajaxSuccess($response);
