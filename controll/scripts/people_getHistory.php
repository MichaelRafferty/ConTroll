<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init('ajax');
$perm = 'people';

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
SELECT 999999999 AS historyId, id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, pronouns, 
       address, addr_2, city, state, zip, country, banned, creation_date, update_date, change_notes, active, open_notes, admin_notes, 
       old_perid, contact_ok, share_reg_ok, managedBy, managedByNew, lastVerified, managedReason, updatedBy
FROM perinfo
WHERE id = ?
UNION SELECT  historyId, id, last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, legalName, pronouns, 
       address, addr_2, city, state, zip, country, banned, creation_date, update_date, change_notes, active, open_notes, admin_notes, 
       old_perid, contact_ok, share_reg_ok, managedBy, managedByNew, lastVerified, managedReason, updatedBy
FROM perinfoHistory
WHERE id = ?
ORDER BY historyId desc
EOS;
$bR = dbSafeQuery($bQ, 'ii', array($perid, $perid));
if ($bR === false) {
    $response['error'] = 'Database error retrieving person history';
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
?>
