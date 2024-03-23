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

$sub = $check_auth['sub'];
$email = $check_auth['email'];

$user = get_user($sub);
$userid = $user;
$con = get_con();
$conid = $con['id'];

$transid = sql_safe($_GET['id']);

$totalPrice = 0;
$badgeQ = <<<EOS
SELECT DISTINCT R.id, M.label, R.price, R.paid, P.badge_name, TRIM(CONCAT_WS(' ', first_name, last_name)) AS full_name, S.action
FROM reg_history H
JOIN reg R ON (R.id = H.regid)
JOIN memLabel M ON (M.id=R.memId)
JOIN perinfo P ON (P.id=R.perid)
LEFT OUTER JOIN reg_history S ON (S.regid=R.id and S.action='print')
WHERE H.tid = ? AND H.action = 'attach';
EOS;
$badgeRes = dbSafeQuery($badgeQ, 'i', array($transid));
$paidBadges=array();
$newBadges=array();
$oldBadges=array();
if($badgeRes) {
  while($badge = fetch_safe_assoc($badgeRes)) {
    $totalPrice += $badge['price']-$badge['paid'];
    if($badge['price'] > $badge['paid']) { array_push($newBadges, $badge); }
    else if($badge['action']=='print') {
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
$paymentRes = dbSafeQuery("SELECT amount FROM payments WHERE transid=?", 'i', array($transid));
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
  $query0 = "UPDATE transaction SET price=?, paid=?, complete_date=current_timestamp(), userid=? WHERE id=?;";
  $query1 = <<<EOS
UPDATE reg R
JOIN reg_history H ON (R.id=H.regid)
SET R.paid=R.price - R.couponDiscount, complete_trans = ?
WHERE H.tid=?;
EOS;

  dbSafeCmd($query0, 'ddii', array($totalPrice, $totalPaid, $userid, $transid));
  dbSafeCmd($query1, 'ii', array($transid, $transid));
  $response['success']='true';

    $badgeRes = dbQuery($badgeQ);
    $paidBadges=array();
    $newBadges=array();
    $oldBadges=array();
    if($badgeRes) {
        while($badge = fetch_safe_assoc($badgeRes)) {
            $totalPrice += $badge['price']-$badge['paid'];
                if($badge['price'] > $badge['paid']) { array_push($newBadges, $badge); }
                else if($badge['action']=='print') {
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
