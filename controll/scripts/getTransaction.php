<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "registration";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || (!checkAuth($check_auth['sub'], $perm) &&
                            !checkAuth($check_auth['sub'], 'atcon'))) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_GET['id'])) { $resonse['error'] = "No Data"; ajaxSuccess($response); exit(); }

$transactionId=$_GET['id'];
$con = get_con();
$conid=$con['id'];

$query = <<<EOQ
SELECT T.id as tID, T.create_date as tCreate, T.complete_date as tComplete, T.notes as tNotes, T.paid as tPaid, P.banned
    , P.id as ownerId, concat_ws(' ', P.first_name, P.middle_name, P.last_name) as ownerName
    , P.address as ownerAddr, P.addr_2 as ownerAddr2, concat_ws(' ', P.city, P.state, P.zip) as ownerLocale
    , P.badge_name as ownerBadge, P.email_addr as ownerEmail,R.id as badgeId, R.price, R.paid
    , (R.price - R.paid) as cost, concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type
    , R.locked, R.create_trans
FROM transaction as T
JOIN perinfo as P ON (P.id=T.perid)
LEFT OUTER JOIN reg as R ON (R.perid=T.perid AND (R.conid=T.conid OR R.conid=?))
LEFT OUTER JOIN memList as M ON (M.id=R.memId)
WHERE T.id=? AND (T.conid=? or R.conid=?);
EOQ;

$response['transQ'] = $query;
$res = dbSafeQuery($query, "iiii", array($conid, $transactionId, $conid, $conid));
$transaction = null;
if($res->num_rows >= 1) {
    $transaction = fetch_safe_assoc($res);
}

$paymentRes = dbSafeQuery("SELECT type, description, cc_approval_code, amount FROM payments WHERE transid=?;", 'i', array($transactionId));
$payments=array();
$total = array(0);
if ($paymentRes) {
    while($payment = fetch_safe_assoc($paymentRes)) {
        $payments[count($payments)] = $payment;
    }
    $sumres = dbSafeQuery("select sum(amount) FROM payments where transid=?;", 'i', array($transactionId));
    if ($sumres)
        $total = fetch_safe_array($sumres);
}

$badgeQuery = <<<EOQ
SELECT DISTINCT P.id as id
    , concat_ws(' ', P.first_name, P.middle_name, P.last_name) as full_name
    , P.banned, P.address as address, P.addr_2
    , concat_ws(' ', P.city, P.state, P.zip) as locale
    , P.badge_name as badge_name, P.email_addr as ownerEmail
    , R.id as badgeId
FROM transaction as T

EOQ;

$atconR = dbSafeQuery("SELECT id from reg_history where tid=?;", 'i', array($transactionId));
if($atconR->num_rows == 1) {
    $atcon = fetch_safe_assoc($atconR);
    $atconId = $atcon['id'];
    $badgeQuery .= <<<EOQ
JOIN reg_history H ON (H.tid=T.id AND H.action='attach')
JOIN reg as R ON (R.id = H.regid AND R.perid != T.perid
    AND (R.newperid != T.newperid OR T.newperid IS NULL))
JOIN perinfo as P ON P.id=R.perid
JOIN memList as M on M.id=R.memId
EOQ;
} else {
    $badgeQuery .= <<<EOQ
JOIN reg as R ON (R.create_trans = T.id AND R.perid != T.perid AND R.newperid != T.newperid)
JOIN perinfo as P on P.id=R.perid
JOIN memList as M on M.id=R.memId
EOQ;
}
$badgeQuery .= " WHERE T.id = ?;";
$response['badgeQ'] = $badgeQuery;
$badgeRes = dbSafeQuery($badgeQuery, 'i', array($transactionId));
$badges=array();
if ($badgeRes) {
    while($badgeid = fetch_safe_assoc($badgeRes)) {
        $badges[count($badges)]=$badgeid;
    }
}

$response['result']=$transaction;
$response['badges']= $badges;
$response['payments'] = $payments;
$repsonse['total'] = $total[0];

ajaxSuccess($response);
?>
