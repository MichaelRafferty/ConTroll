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
SELECT C.code, C.name, K.*, 
    PC.first_name, PC.last_name, PC.badge_name,
    PU.first_name AS u_first_name, PU.last_name AS u_last_name, PU.badge_name AS u_badge_name
FROM coupon C
JOIN couponKeys K ON (K.couponId = C.id)
LEFT OUTER JOIN perinfo PC ON (PC.id = K.perid)
LEFT OUTER JOIN perinfo PU ON (PU.id = K.usedBy)
WHERE C.id = ?;
EOS;

$couponR = dbSafeQuery($couponQ, 'i', array($id));
if ($couponR == false) {
    $response['status'] = 'error';
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}
$couponKeys = array();
while ($couponL = fetch_safe_assoc($couponR)) {
    $couponKeys[] = $couponL;
}

$response['status'] = 'success';
$response['keys'] = $couponKeys;

ajaxSuccess($response);
?>
