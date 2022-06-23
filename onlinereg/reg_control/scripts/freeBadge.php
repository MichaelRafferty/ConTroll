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
$userQ = "SELECT id FROM user WHERE email='$user';";
$userR = fetch_safe_assoc(dbQuery($userQ));
$userid = $userR['id'];


$transQ = "SELECT * FROM transaction WHERE userid='" .
    $userid . "' AND type='staff';";
$transR = dbQuery($transQ);

$transid=0;

if($transR->num_rows > 0) {
  $trans=fetch_safe_assoc($transR);
  $transid=$trans['id'];
} else {
  $transQ = "INSERT INTO transaction (conid, userid, price, paid, type, notes) VALUES (".
    $con['id'] . ", " . $userid . ", 0, 0, 'staff', 'Free memberships');";
  $transid=dbInsert($transQ);
}



if($_POST['regid'] == '') {
  if(!isset($_POST['memId'])) {
    ajaxSuccess(array('error'=>'Missing Membership Type'));
    exit();
  }

  $perQ = "SELECT perid FROM badgeList WHERE id='".sql_safe($_POST['id'])."';";
  $perid = fetch_safe_assoc(dbQuery($perQ));



  $reg = array(
    'conid'=>sql_safe($con['id']),
    'memId'=>sql_safe($_POST['memId']),
    'perid'=>$perid['perid'],
    'trans'=>$transid
  );

  $regQ = "INSERT INTO reg (conid, perid, memId, create_trans, paid, price, locked, create_user) VALUES (" .
    $reg['conid'] . ", " . $reg['perid'] . ", " . $reg['memId'] . ", " .
    $reg['trans'] . ", 0, 0, 'N', " . $userid . ");";

  $regId = dbInsert($regQ);
} else {

  $reg= array(
    'conid'=>sql_safe($con['id']),
    'regid'=>sql_safe($_POST['regid']),
    'ribbonList'=>$_POST['ribbon']
  );

    if($reg['ribbonList'] == null) { $reg['ribbonList']=array(); }

  if(isset($_POST['staff'])) {
    switch($_POST['staff']) {
      case 'committee':
        $reg['staff']=sql_safe($_POST['staff']);
        array_push($reg['ribbonList'], sql_safe($_POST['staff']));
        break;
      case 'department head':
        $reg['staff']=sql_safe($_POST['staff']);
        array_push($reg['ribbonList'], sql_safe($_POST['staff']));
        break;
      case 'senior staff':
        $reg['staff']=sql_safe($_POST['staff']);
        array_push($reg['ribbonList'], sql_safe($_POST['staff']));
        break;
      case 'general staff':
        $reg['staff']=sql_safe($_POST['staff']);
        array_push($reg['ribbonList'], sql_safe($_POST['staff']));
        break;
      case 'volunteer':
        $reg['staff']=sql_safe($_POST['staff']);
        array_push($reg['ribbonList'], sql_safe($_POST['staff']));
        break;
      case 'none':
      default:
        $reg['staff']='';
    }



    $regQ = "UPDATE reg SET staff='".$reg['staff']."' WHERE id = '".$reg['regid']."';";
    dbQuery($regQ);
  } else {
    $regQ = "UPDATE reg SET staff=NULL WHERE id='".$reg['regid']."';";
    dbQuery($regQ);
  }

}

$response['reg']=$reg;

ajaxSuccess($response);
?>
