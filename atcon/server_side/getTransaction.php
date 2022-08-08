<?php
require_once "lib/base.php";

$perm="data_entry";
$con = get_con();
$conid=$con['id'];
$response = array("post" => $_POST, "get" => $_GET);
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}


if(!isset($_POST['id'])) { $resonse['error'] = "No Data"; ajaxSuccess($response); exit(); }

$transactionId=$_POST['id'];
$con = get_con();
$conid=$con['id'];

$query = <<<EOS
SELECT T.id as tID, T.create_date as tCreate, T.complete_date as tComplete, T.notes as tNotes, T.paid as tPaid, P.banned, P.id as ownerId,
    concat_ws(' ', P.first_name, P.middle_name, P.last_name) as ownerName, P.address as ownerAddr, P.addr_2 as ownerAddr2,
    concat_ws(' ', P.city, P.state, P.zip) as ownerLocale, P.badge_name as ownerBadge, P.email_addr as ownerEmail,R.id as badgeId,
    R.price, R.paid, (R.price - R.paid) as cost, M.memAge as age, P.id as perid,
    concat_ws('-', M.id, M.memCategory, M.memType, M.memAge) as type, R.locked, R.create_trans, M.label
FROM transaction T
JOIN perinfo P ON (P.id=T.perid)
LEFT OUTER JOIN reg R ON (R.perid=T.perid AND R.conid=T.conid)
LEFT OUTER JOIN memList M ON (M.id=R.memId)
WHERE T.id=? AND T.conid=?;
EOS;

  $response['transQ'] = $query;
  $res = dbSafeQuery($query, 'ii', array($transactionId, $conid));
  $transaction = null;
  if($res->num_rows >= 1) {
    $transaction = fetch_safe_assoc($res);
  }

  $paymentRes = dbSafeQuery("SELECT type, description, cc_approval_code, amount FROM payments WHERE transid=?;", 'i', array($transactionId));
  $payments=array();
  $total = 0;
  if($paymentRes){
    while($payment = fetch_safe_assoc($paymentRes)) {
      $payments[count($payments)] = $payment;
    }

    $total = fetch_safe_array(dbSafeQuery("select sum(amount) FROM payments where transid=$transactionId;", 'i', array($transactionId)));
  }

  $badgeQuery = <<<EOS
SELECT DISTINCT P.id as id, concat_ws(' ', P.first_name, P.middle_name, P.last_name) as full_name
    , P.banned, P.address as address, P.addr_2, concat_ws(' ', P.city, P.state, P.zip) as locale
    , P.badge_name as badge_name, P.email_addr as ownerEmail, R.id as badgeId, M.memAge as age, P.id as perid
FROM transaction T;
EOS;
    $atconQ = "SELECT id from atcon where transid=?;";
    $atconR = dbSafeQuery($atconQ, , 'i', array($transactionId));
    if($atconR->num_rows == 1) {
        $atcon = fetch_safe_assoc($atconR);
        $atconId = $atcon['id'];
        $badgeQuery .= <<<EOS
JOIN atcon A ON (A.transid=T.id)
JOIN atcon_badge B ON (B.atconid=A.id AND B.action='attach')
JOIN reg R ON (R.id = B.badgeId AND R.perid != T.perid AND (R.newperid != T.newperid OR T.newperid IS NULL))
JOIN perinfo P ON (P.id=R.perid)
JOIN memList M ON (M.id=R.memId)
EOS;

        $actionQ = <<<EOS
SELECT A.action as type, P.id, M.memAge, R.price, concat_ws(' ', P.first_name, P.last_name) as name
FROM atcon_badge A
JOIN reg R ON (R.id=A.badgeId)
JOIN perinfo P ON (P.id=R.perid)
JOIN memList M ON (M.id=R.memId)
WHERE atconId=? AND action in ('yearahead', 'rollover', 'volunteer');
EOS;
        $actionR = dbSafeQuery($actionQ, 'i', array($atconId));
        $actions = array();
        while($action = fetch_safe_assoc($actionR)) {
            array_push($actions, $action);
        }
        $response['actions'] = $actions;
    } else {
        $badgeQuery .= <<<EOS
JOIN reg AS R ON (R.create_trans = T.id AND R.perid != T.perid AND R.newperid != T.newperid)
JOIN perinfo  P ON (P.id=R.perid)
JOIN memList  M ON (M.id=R.memId)
EOS;
    }
    $badgeQuery .= " WHERE T.id=?";
    $response['badgeQ'] = $badgeQuery;
    $badgeRes = dbSafeQuery($badgeQuery, 'i', array($transactionId));
    $badges=array();
    if($badgeRes) {
        while($badgeid = fetch_safe_assoc($badgeRes)) {
            $badges[count($badges)]=$badgeid;
        }
    }

$response['result']=$transaction;
$response['badges']= $badges;
$response['payments'] = $payments;
$repsonse['total'] = $total[0];

ajaxSuccess($response);
?>