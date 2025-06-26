<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init('ajax');
$perm = 'reg_staff';

$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}
if (!array_key_exists('regid', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}
$con = get_conf('con');
$conid = $con['id'];
$regid = $_POST['regid'];
$response['conid'] = $conid;
$response['regid'] = $regid;

$bQ = <<<EOS
SELECT 999999999 AS historyId, id, conid, perid, newperid, oldperid, priorRegId, create_date, change_date, pickup_date, price, couponDiscount, 
       paid, create_trans, complete_trans, locked, create_user, updatedBy, memId, coupon, planId, printable, status
FROM reg
WHERE id = ?
UNION SELECT historyId, id, conid, perid, newperid, oldperid, priorRegId, create_date, change_date, pickup_date, price, couponDiscount, 
             paid, create_trans, complete_trans, locked, create_user, updatedBy, memId, coupon, planId, printable, status
FROM regHistory
WHERE id = ?
ORDER BY historyId desc
EOS;
$bR = dbSafeQuery($bQ, 'ii', array($regid, $regid));
if ($bR === false) {
    $response['error'] = 'Database error retrieving memberships';
    ajaxSuccess($response);
    exit();
}
$history = [];
while ($bL = $bR->fetch_assoc()) {
    $history[] = $bL;
}
$bR->free();
$response['history'] = $history;
$response['query']=$bQ;

ajaxSuccess($response);
