<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "registration";

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

$trans_key = $_POST['trans_key'];
$amount = $_POST['amount'];
$type = $_POST['type'];
$description = $_POST['description'];
$approvalcode = '';
if (array_key_exists('checkno', $_POST) && $type = 'check') {
    $approvalcode = $_POST['checkno'];
}
if (array_key_exists('approvalcode', $_POST) && $type = 'credit') {
    $approvalcode = $_POST['approvalcode'];
}

$transid=$trans_key;

$complete = false;

$badgeListQ = <<<EOQ
SELECT DISTINCT R.id, M.label, (IFNULL(R.price, 0)-IFNULL(R.paid,0)) AS remainder
FROM regActions H 
JOIN reg R ON (R.id = H.regid)
JOIN memLabel M ON (M.id=R.memId)
WHERE H.tid = ? AND H.action='attach';
EOQ;

$total = 0;

$badgeListR = dbSafeQuery($badgeListQ, 'i', array($transid));
$badgeList= array();
while($badge = $badgeListR->fetch_assoc()) {
    array_push($badgeList, $badge);
    $total += $badge['remainder'];
}

$paid = 0;

$paidR = dbSafeQuery('SELECT amount FROM payments WHERE transid=?;', 'i', array($transid));
if (isset($paidR) && $paidR->num_rows > 0) {
    while($paidA = $paidR->fetch_row()) {
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

$paymentQ = <<<EOQ
INSERT INTO payments (transid, userid, type, category, description, cc_approval_code, source, pretax, tax, amount)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOQ;

$response['paymentQ'] = $paymentQ;
$types = "iisssssd";
$values = array($transid, $user, $type, 'reg', $description, $approvalcode, $user_s, $amount, 0, $amount);

$payid = dbSafeInsert($paymentQ, $types, $values);

// update transaction with price and paid amounts, and if this is complete close it out
$transQ = "UPDATE transaction SET price=?, tax=0, withtax=?, paid=?";
if ($complete) {
    $transQ .= ", complete_date = now()";
}

$transQ .= " WHERE id=?;";

$types = 'dddi';
$values = array($total, $total, ($paid + $amount), $transid);
$rows = dbSafeCmd($transQ, $types, $values);

// now update registrations showing amount paid from oldest to newest, until all used up.
$remainder = $total;
foreach ($badgeList as $badge) {
    $amt = $badge['remainder'];
    if ($amt > 0) {
        $paid = $remainder >= $amt ? $amt : $remainder;
        $rows = dbSafeCmd("UPDATE reg SET paid = IFNULL(paid, 0) + ?, create_trans = ? WHERE id = ?;", "dii", array($paid, $transid, $badge['id']));
        dbSafeCmd("UPDATE reg SET status = 'paid' WHERE id = ? AND paid >= price;", 'i', array($badge['id']));
        $remainder -= $amt;
    }
}

$resultQ = "SELECT type, description, cc_approval_code, amount FROM payments where id=?;";
$resultA = dbSafeQuery($resultQ, 'i', array($payid));
$response['result'] = fetch_safe_assoc($resultA);

ajaxSuccess($response);
?>
