<?php
global $db_ini;

require_once '../lib/base.php';
$check_auth = google_init('ajax');
$perm = 'vendor';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid = $con['id'];
if (!array_key_exists('vendorId', $_POST)) {
    ajaxError("No Data");
}

$vendor = $_POST['vendorId'];

$str = str_shuffle(
"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#"
.
"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#"
);

$len = rand(7,12);
$start = rand(0,strlen($str)-$len);


$newpasswd = substr($str, $start, $len);

$hash = password_hash($newpasswd, PASSWORD_DEFAULT);
$pwQ = "UPDATE vendors SET password=?, need_new=true where id=?;";
$num_rows = dbSafeCmd($pwQ, 'si', array($hash, $vendor));
if ($num_rows != 1) {
    $response['error'] = 'Database update failed';
}

$response['password'] = $newpasswd;

ajaxSuccess($response);
?>
