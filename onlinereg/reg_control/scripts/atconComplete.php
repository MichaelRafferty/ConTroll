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

$sub = $check_auth['sub'];
$email = $check_auth['email'];

$user = get_user($sub);
$userid = $user;
$con = get_con();
$conid = $con['id'];

$transid = sql_safe($_GET['id']);

$totalPrice = 0;
$badgeQ = "SELECT DISTINCT R.id, M.label, R.price, R.paid, P.badge_name"
    . ", concat_ws(' ', first_name, last_name) as full_name"
    . ", S.action"
    . " FROM atcon as A"
        . " JOIN atcon_badge as B on B.atconId = A.id and action='attach'"
        . " JOIN reg as R on R.id = B.badgeId"
        . " JOIN memList as M ON M.id=R.memId"
        . " JOIN perinfo as P on P.id=R.perid"
        . " LEFT JOIN atcon_badge as S ON S.badgeId=R.id and S.action='pickup'"
    . " WHERE A.transid = $transid";

$badgeRes = dbQuery($badgeQ);
$paidBadges=array();
$newBadges=array();
$oldBadges=array();
if($badgeRes) {
  while($badge = fetch_safe_assoc($badgeRes)) {
    $totalPrice += $badge['price']-$badge['paid'];
    if($badge['price'] > $badge['paid']) { array_push($newBadges, $badge); }
    else if($badge['action']=='pickup') {
        array_push($oldBadges, $badge);
    } else {
        array_push($paidBadges, $badge);
    }
  }
} else { $response["error"]="No Badges!<br/>"; }
$response['total'] = $totalPrice;
$response['printBadges'] = $paidBadges;
$response['newBadges'] = $newBadges;
$response['oldBadges'] = $oldBadges;


$totalPaid = 0;
$paymentRes = dbQuery("SELECT amount FROM payments WHERE transid=$transid");
if($paymentRes) {
  while($payment = fetch_safe_array($paymentRes)) {
    $totalPaid += $payment[0];
  }
}
$response['paid'] = $totalPaid;

if($totalPrice < $totalPaid) {
  $response["error"] = "Over Payment by $".($totalPaid-$totalPrice)."<br/>";
}

if($totalPrice <= $totalPaid) {
  $query0 = "UPDATE transaction SET price=$totalPrice, paid=$totalPaid, complete_date=current_timestamp(), userid=$userid WHERE id=$transid;";
  $query1 = "UPDATE reg as R"
    . " JOIN atcon_badge as B ON R.id=B.badgeId"
    . " JOIN atcon as A"
    . " SET R.paid=R.price WHERE A.transid=$transid;";

  dbQuery($query0);
  dbQuery($query1);
  $response['success']='true';

    $badgeRes = dbQuery($badgeQ);
    $paidBadges=array();
    $newBadges=array();
    $oldBadges=array();
    if($badgeRes) {
        while($badge = fetch_safe_assoc($badgeRes)) {
            $totalPrice += $badge['price']-$badge['paid'];
                if($badge['price'] > $badge['paid']) { array_push($newBadges, $badge); }
                else if($badge['action']=='pickup') {
                    array_push($oldBadges, $badge);
                } else {
                    array_push($paidBadges, $badge);
                }
            }
    } else { $response["error"]="No Badges!<br/>"; }
    $response['printBadges'] = $paidBadges;
    $response['newBadges'] = $newBadges;
    $response['oldBadges'] = $oldBadges;

}

ajaxSuccess($response);
?>
