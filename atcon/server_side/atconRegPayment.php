<?php
require_once "lib/base.php";

$response = array("post" => $_POST, "get" => $_GET);

$perm="cashier";
$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$user = get_user('not a sub');
$user_s = $perm;
$userid = $_POST['user'];

$con = get_con();
$conid = $con['id'];
$conf = get_conf('con');
$taxRate = $conf['taxRate'];

$trans_key = $_POST['trans_key'];
$amount = $_POST['amount'];
$type = $_POST['type'];
$description = $_POST['description'];

if($type == 'offline') { $type = 'credit'; }

$response['type']=$type;

$transid=$trans_key;

$complete = false;

$badgeListQ = <<<EOS
SELECT DISTINCT R.id, M.label, (R.price-R.paid) as remainder
FROM atcon A
JOIN atcon_badge B ON (B.atconId = A.id and action='attach')
JOIN reg R ON (R.id = B.badgeId)
JOIN memList M ON (M.id=R.memId)
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


if($_POST['type']=='credit') {
    $paymentQ = "UPDATE payments SET transid = ?, description ='' WHERE id=?;";
    dbSafeCmd($paymentQ, 'ii', array($transid, $_POST['payment']));
    $payid = $_POST['payment'];
} else {
    $paymentQ = "INSERT INTO payments(transid, cashier, type, category, description, source, amount) VALUES(?,?,?,'reg',?,?,?);";
    $payid = dbSafeInsert($paymentQ, 'iisssd', array($transid, $userid, $type, $description, $user_s, $amount));
}

$response['payid'] = $payid;
#$response['paymentQ'] = $paymentQ;

$transQ = "UPDATE transaction SET price=?, tax=0, withtax=?, paid=? WHERE id=?";
dbSafeCmd($transQ, 'dddi', array($total, $total, ($paid + $amount, $transid));

if($_POST['type']=='offline') {
    $paymentQ2 = "UPDATE payments SET cc_approval_code=? WHERE id=?;";
    dbSafeCmd($paymentQ2, 'si', array($_POST['cc_approval_code'], $payid));
#$response['paymentUpdate'] = $paymentQ2;
}

$resultQ = "SELECT type, description, cc_approval_code, amount FROM payments where id=?;";
$resultA = dbSafeQuery($resultQ, 'i', array($payid));
$response['result'] = fetch_safe_assoc($resultA);

ajaxSuccess($response);
?>
