<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

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

$transid=$trans_key;

$complete = false;

$badgeListQ = <<<EOQ
SELECT DISTINCT R.id, M.label, (R.price-R.paid) as remainder
FROM atcon as A
JOIN atcon_badge as B ON (B.atconId = A.id and action='attach')
JOIN reg as R ON (R.id = B.badgeId)
JOIN memList as M ON (M.id=R.memId)
WHERE A.transid = ?;
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

$paymentQ = <<<EOQ
INSERT INTO payments (transid, userid, type, category, description, cc_approval_code, source, amount)
VALUES (?, ?, ?, ?, ?, ?, ?, ?);
EOQ;

$response['paymentQ'] = $paymentQ;
$types = "iisssssd";
$values = array($transid, $user, $type, 'reg', $description, $description, $user_s, $amount);

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
        $rows = dbSafeCmd("UPDATE reg set paid = paid + ? WHERE id = ?", "di", array($paid, $badge['id']));
        $remainder -= $amt;
    }  
}

$resultQ = "SELECT type, description, cc_approval_code, amount FROM payments where id=?;";
$resultA = dbSafeQuery($resultQ, 'i', array($payid));
$response['result'] = fetch_safe_assoc($resultA);

ajaxSuccess($response);
?>
