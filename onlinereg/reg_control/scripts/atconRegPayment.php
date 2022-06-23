<?php
global $ini;
if (!$ini)
    $ini = parse_ini_file(__DIR__ . "/../../../config/reg_conf.ini", true);
if ($ini['reg']['https'] <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";

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
    . " (transid, userid, type, category, description, source, amount)"
    . " VALUES"
    . " ($transid, $user, '$type', 'reg', '$description'"
    . ", '$user_s', $amount);";
$response['paymentQ'] = $paymentQ;

$payid = dbInsert($paymentQ);

$transQ = "UPDATE transaction SET price=$total, tax=0, withtax=$total"
    . ", paid=".($paid + $amount)
    . " WHERE id=$transid";
dbQuery($transQ);

$resultQ = "SELECT type, description, cc_approval_code, amount FROM payments where id=$payid;";
$resultA = dbQuery($resultQ);
$response['result'] = fetch_safe_assoc($resultA);

ajaxSuccess($response);
?>
