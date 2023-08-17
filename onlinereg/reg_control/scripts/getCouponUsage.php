<?php
require_once "../lib/base.php";
//require_once "../../../lib/coupon.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['status'] = 'error';
    $response['error'] = "Authentication Failed";
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
SELECT C.*, T.price, T.couponDiscount, T.paid, T.type, T.complete_date, P.last_name, P.first_name, P.badge_name
FROM couponUsage C
JOIN transaction T ON (T.id = C.transId)
JOIN perinfo P ON (P.id = C.perid)
WHERE couponId = ? AND T.paid IS NOT NULL;
EOS;

$couponR = dbSafeQuery($couponQ, 'i', array($id));
if ($couponR == false) {
    $response['status'] = 'error';
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}
$couponUsage = array();
while ($couponL = fetch_safe_assoc($couponR)) {
    $couponUsage[] = $couponL;
}

$response['status'] = 'success';
$response['used'] = $couponUsage;

ajaxSuccess($response);
?>
