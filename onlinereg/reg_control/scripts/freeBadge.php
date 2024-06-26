<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "badge";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conf = get_conf('con');

if(!isset($_POST) || count($_POST) == 0) { ajaxError("No Data"); }
if(!isset($_POST['regid']) || !isset($_POST['id'])) { ajaxSuccess(array('error'=>'Missing ID value in POST')); exit(); }
//ajaxSuccess(print_r($_POST, true));


$user = $check_auth['email'];
$response['user'] = $user;
$user_perid = $_SESSION['user_perid'];

$transQ = <<<EOS
SELECT *
FROM transaction
WHERE userid=? AND type = 'staff' and conid = ?;
EOS;
$transR = dbSafeQuery($transQ, 'ii', array($user_perid, $con['id']));
$transid=0;

if ($transR->num_rows > 0) {
  $trans=$transR->fetch_assoc();
  $transid=$trans['id'];
} else {
  $transQ = "INSERT INTO transaction (conid, userid, price, paid, type, notes, complete_date) VALUES (?, ?, 0, 0, 'staff', 'Free memberships', NOW());";
  $transid=dbSafeInsert($transQ, 'ii', array($con['id'] ,$user_perid));
}

if((!array_key_exists('regid', $_POST)) || (!isset($_POST['regid'])) || $_POST['regid'] == ''  || $_POST['regid'] == 'null') {
    if((!array_key_exists('memId', $_POST)) || (!isset($_POST['memId']))) {
        ajaxSuccess(array('error'=>'Missing Membership Type'));
        exit();
    }

    $perQ = "SELECT perid FROM badgeList WHERE id=?;";
    $perid = dbSafeQuery($perQ, 'i', array($_POST['id']))->fetch_assoc();

    $reg = array(
      'conid'=>sql_safe($con['id']),
      'memId'=>sql_safe($_POST['memId']),
      'perid'=>$perid['perid'],
      'trans'=>$transid
    );

    $regQ = "INSERT INTO reg (conid, perid, memId, create_trans, complete_trans, paid, price, locked, create_user) VALUES (?, ?, ?, ?, ?, 0, 0, 'N', ?);";

    $regId = dbSafeInsert($regQ, 'iiiiii', array( $reg['conid'], $reg['perid'], $reg['memId'], $reg['trans'], $reg['trans'], $user_perid));

    $rows_modified = dbSafeCmd("UPDATE transaction SET perid = ? WHERE id = ?;", 'ii', array($reg['perid'], $reg['trans']));
} else {

    $reg= array(
      'conid'=>sql_safe($con['id']),
      'regid'=>sql_safe($_POST['regid']),
    );
}

$response['reg']=$reg;

ajaxSuccess($response);
?>
