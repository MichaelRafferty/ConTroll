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
if (!array_key_exists('perid', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}
$con = get_conf('con');
$conid = $con['id'];
$perid = $_POST['perid'];
$response['conid'] = $conid;
$response['perid'] = $perid;

$bQ = <<<EOS
SELECT r.*, m.label
FROM reg r
JOIN memLabel m ON r.memId = m.id
WHERE r.perid = ? AND r.conid = ?;
EOS;
$bR = dbSafeQuery($bQ, 'ii', array($perid, $conid));
if ($bR === false) {
    $response['error'] = 'Database error retrieving memberships';
    ajaxSuccess($response);
    exit();
}
$memberships = [];
while ($bL = $bR->fetch_assoc()) {
    $memberships[] = $bL;
}
$bR->free();
$response['memberships'] = $memberships;

$response['query']=$bQ;

ajaxSuccess($response);
?>
