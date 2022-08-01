<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

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

$trans_key = sql_safe($_POST['trans_key']);
$amount = sql_safe($_POST['amount']);
$type = sql_safe($_POST['type']);
$description = sql_safe($_POST['description']);

if($type == 'offline') { $type = 'credit'; }

$response['type']=$type;

$transid=$trans_key;

$complete = false;

$badgeListQ = "SELECT DISTINCT R.id, M.label, (R.price-R.paid) as remainder"
    . " FROM atcon as A"
        . " JOIN atcon_badge as B on B.atconId = A.id and action='attach'"
        . " JOIN reg as R on R.id = B.badgeId"
        . " JOIN memList as M ON M.id=R.memId"
    . " WHERE A.transid = $transid";

$total = 0;

$badgeListR = dbQuery($badgeListQ);
$badgeList= array();
while($badge = $badgeListR->fetch_assoc()) {
    array_push($badgeList, $badge);
    $total += $badge['remainder'];
}

$paid = 0; 

$paidQ = "SELECT amount FROM payments WHERE transid=$transid;";
$paidR = dbQuery($paidQ);
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

$paymentQ = "INSERT INTO payments"
    . " (transid, cashier, type, category, description, source, amount)"
    . " VALUES"
    . " ($transid, $userid, '$type', 'reg', '$description'"
    . ", '$user_s', $amount);";
#$response['paymentQ'] = $paymentQ;

if($_POST['type']=='credit') {
    $paymentQ = "UPDATE payments SET"
        . " transid = $transid, description =''"
        . " WHERE id=" . sql_safe($_POST['payment']) . ";";
    dbQuery($paymentQ);
    $payid = $_POST['payment'];
} else {
    $payid = dbInsert($paymentQ);
}

$response['payid'] = $payid;

$transQ = "UPDATE transaction SET price=$total, tax=0, withtax=$total"
    . ", paid=".($paid + $amount)
    . " WHERE id=$transid";
dbQuery($transQ);

if($_POST['type']=='offline') {
    $paymentQ2 = "UPDATE payments SET"
        . " cc_approval_code='" . sql_safe($_POST['cc_approval_code']) . "'"
        . " WHERE id=$payid;";
    dbQuery($paymentQ2);
#$response['paymentUpdate'] = $paymentQ2;
}

$resultQ = "SELECT type, description, cc_approval_code, amount FROM payments where id=$payid;";
$resultA = dbQuery($resultQ);
$response['result'] = fetch_safe_assoc($resultA); 

ajaxSuccess($response);
?>
