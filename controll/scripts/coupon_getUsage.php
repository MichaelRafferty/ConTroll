<?php
require_once "../lib/base.php";
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

if (!array_key_exists('id', $_POST)) {
    $response['status'] = 'error';
    $response['error'] = 'Invalid Calling Sequence';
    ajaxSuccess($response);
    exit();
}

$id = $_POST['id'];

$couponQ = <<<EOS
SELECT C.*, P.last_name, P.first_name, P.badge_name, P.badgeNameL2
FROM couponUsage C
LEFT OUTER JOIN perinfo P ON (P.id = C.perid)
WHERE C.couponId = ? AND C.paid IS NOT NULL;
EOS;

$couponR = dbSafeQuery($couponQ, 'i', array($id));
if ($couponR == false) {
    $response['status'] = 'error';
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}
$couponUsage = array();
while ($couponL = $couponR->fetch_assoc()) {
    $couponL['badgename'] = badgeNameDefault($coupon['badge_name'], $coupon['badgeNameL2'], $coupon['first_name'], $coupon['last_name']);
    $couponUsage[] = $couponL;
}

$response['status'] = 'success';
$response['used'] = $couponUsage;

ajaxSuccess($response);
