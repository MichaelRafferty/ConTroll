<?php

require_once "lib/base.php";

$response = array("post" => $_POST, "get" => $_GET);

$perm="data_entry";
$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";;
    ajaxSuccess($response);
    exit();
}

$sub = "not a sub";
$email = "regadmin@bsfs.org"; // Hardcoded, do we need a different hardcode

$user = get_user($sub);
$userid = $user;
$con = get_con();
$conid = $con['id'];

$transid = $_POST['id'];

$totalPrice = 0;
$badgeQ = <<<EOS
SELECT DISTINCT P.id, M.label, M.memAge as age, M.memType as type, M.memCategory as category, DAYNAME(M.startdate) as day, R.price, R.paid, P.badge_name
    , R.id as badgeId, concat_ws(' ', first_name, last_name) as full_name, S.action
FROM atcon A
JOIN atcon_badge B ON (B.atconId = A.id and B.action='attach')
JOIN reg R ON (R.id = B.badgeId)
JOIN memList M ON (M.id=R.memId)
JOIN perinfo P ON (P.id=R.perid)
LEFT OUTER JOIN atcon_badge S ON (B.atconId= S.id and S.action='pickup')
WHERE A.transid = ?;
EOS;

$badgeRes = dbSafeQuery($badgeQ, 'i', array($transid));
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

if($totalPrice <= $totalPaid && $totalPaid > 0) {
  $query0 = "UPDATE transaction SET price=?, paid=?, complete_date=current_timestamp(), userid=? WHERE id=?;";
  $query1 = <<<EOS
UPDATE reg  R
JOIN atcon_badge B ON (R.id=B.badgeId)
JOIN atcon A ON (A.id=B.atconid)
SET R.paid=R.price
WHERE A.transid=?;
EOS;

  dbSafeCmd($query0, 'ddii', array($totalPrice, $totalPrice, $userid, $transid));
  dbSafeCmd($query1, 'i', array($transid));
  $response['success']='true';

  $badgeRes = dbSafeQuery($badgeQ, 'i', array($transid));
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
