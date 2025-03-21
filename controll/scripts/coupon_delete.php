<?php
require_once "../lib/base.php";
require_once '../lib/getCouponData.php';

$check_auth = google_init("ajax");
$perm = "finance";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['status'] = 'error';
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('couponId', $_POST)) {
    $response['status'] = 'error';
    $response['error'] = 'Calling sequence error';
    ajaxSuccess($response);
    exit();
}

$couponId = $_POST['couponId'];
$delSQL = <<<EOS
DELETE FROM coupon
WHERE id = ?;
EOS;

$numdel = dbSafeCmd($delSQL, 'i', array($couponId));
if ($numdel != 1) {
    $response['status'] = 'error';
    $response['error'] = 'Delete failed';
    ajaxSuccess($response);
    exit();
}

$response['message'] = "Coupon " . $couponId . " deleted";
// reload the new array of coupons
$response = getCouponData($response);

ajaxSuccess($response);
?>
