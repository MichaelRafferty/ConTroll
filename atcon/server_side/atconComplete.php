<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

$response = array("post" => $_POST, "get" => $_GET);

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$perm="data_entry";
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

$sub = "not a sub";
$email = "regadmin@bsfs.org";

$user = get_user($sub);
$userid = $user;
$con = get_con();
$conid = $con['id'];

$transid = sql_safe($_POST['id']);

$totalPrice = 0;
$badgeQ = "SELECT DISTINCT P.id, M.label, M.memAge as age, M.memType as type"
    . ", M.memCategory as category, DAYNAME(M.startdate) as day, R.price, R.paid, P.badge_name"
    . ", R.id as badgeId, concat_ws(' ', first_name, last_name) as full_name"
    . ", S.action"
    . " FROM atcon as A"
        . " JOIN atcon_badge as B on B.atconId = A.id and B.action='attach'"
        . " JOIN reg as R on R.id = B.badgeId"
        . " JOIN memList as M ON M.id=R.memId"
        . " JOIN perinfo as P on P.id=R.perid"
        . " LEFT JOIN atcon_badge as S on B.atconId= S.id and S.action='pickup'"
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

if($totalPrice <= $totalPaid && $totalPaid > 0) {
  $query0 = "UPDATE transaction SET price=$totalPrice, paid=$totalPaid, complete_date=current_timestamp(), userid=$userid WHERE id=$transid;";
  $query1 = "UPDATE reg as R"
    . " JOIN atcon_badge as B ON R.id=B.badgeId"
    . " JOIN atcon as A on A.id=B.atconid" 
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
