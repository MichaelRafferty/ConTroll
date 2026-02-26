<?php
require_once "../lib/base.php";
require_once '../lib/getCouponData.php';
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'finance';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
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
