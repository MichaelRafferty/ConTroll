<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "atcon";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$user = get_user($check_auth['sub']);
$user_s = $check_auth['email'];
$userid = $user;

$con = get_con();
$conid = $con['id'];
$conf = get_conf('con');
$taxRate = $conf['taxRate'];

$trans_key = sql_safe($_POST['trans_key']);
$amount = sql_safe($_POST['amount']);
$type = sql_safe($_POST['type']);
$description = sql_safe($_POST['description']);

$response['type']=$type;

$transid=$trans_key;

$complete = false;

$badgeListQ = <<<EOS
SELECT DISTINCT R.id, M.label, (R.price-R.paid) AS remainder
FROM atcon A
JOIN atcon_badge B ON (B.atconId = A.id and action='attach')
JOIN reg R ON (R.id = B.badgeId)
JOIN memLabel M ON (M.id=R.memId)
WHERE A.transid = ?;
EOS;

$total = 0;

$badgeListR = dbSafeQuery($badgeListQ, 'i', array($transid));
$badgeList= array();
while($badge = $badgeListR->fetch_assoc()) {
    array_push($badgeList, $badge);
    $total += $badge['remainder'];
}

$paid = 0;

$paidQ = "SELECT amount FROM payments WHERE transid=?;";
$paidR = dbSafeQuery($paidQ, 'i', array($transid));
if(isset($paidR) && $paidR->num_rows > 0) {
    while($paidA = $paidR->fetch_array()) {
        $paid += $paidA[0];
    }
}

$response['transid']=$transid;
$response['badgeList']=$badgeList;
$response['total'] = $total;
$response['paid'] = $paid;
$response['change'] = 0;

if(($paid + $amount) > $total) {
    $complete = true;
    $response['change'] = ($paid + $amount) - $total;
    $amount = $total - $paid;
} else if(($paid + $amount) == $total) {
    $complete = true;
}
if($complete) { $response['complete']='true'; } else { $response['complete']='false'; }

$paymentQ = <<<EOS
INSERT INTO payments(transid, userid, type, category, description, source, amount)
VALUES(?,?,?,'reg', ?, ?, ?);
EOS;

$response['paymentQ'] = $paymentQ;

$payid = dbSafeInsert($paymentQ, 'iisssd', array($transid, $user, $type, $description, $user_s, $amount));

$transQ = <<<EOS
UPDATE transaction SET price=?, tax=0, withtax=?, paid=?
WHERE id=?;
EOS;

dbSafeCmd($transQ, 'dddi', array($total, $total, $paid + $amount, $transid);

$resultQ = "SELECT type, description, cc_approval_code, amount FROM payments where id=?;";
$resultA = dbSafeQuery($resultQ, 'i', $payid);
$response['result'] = fetch_safe_assoc($resultA);

ajaxSuccess($response);
?>
