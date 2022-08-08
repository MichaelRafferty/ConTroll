<?php
require_once "lib/base.php";

$response = array("post" => $_POST, "get" => $_GET);

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

$transid = $_POST['transid'];
$transQ = "SELECT id FROM atcon where transid=?";
$transR = fetch_safe_assoc(dbSafeQuery($transQ, 'i', array($transid)));
$atconid = $transR['id'];

$owner = $_POST['owner'];
$action= $_POST['action'];



$badges = json_decode($_POST['badgeList'], true);

if(isset($_POST['printed']) && $_POST['printed'] == 'true') {
  $badgeQ = "INSERT IGNORE INTO atcon_badge(atconId, badgeId, action, comment) VALUES ";

  $datatypes = '';
  $comment = 'By: ' . $owner;
  $values = array();
  $multi = false;
  foreach($badges as $badge) {
    if($multi) { $badgeQ .= ", "; }
    $badgeQ .= "(?,?,?,?)";
    $datatypes .= 'iiss';
    $values[] = $atconid;
    $values[] = $badge['badgeId'];
    $values[] = $action;
    $values[] = $comment;
    $multi=true;
  }

  $result = dbSafeInsert($badgeQ, $datatypes, $values);
  $response['result'] = $result;

  ajaxSuccess($response);
} else {
    $data = "user=" . $_POST['user'] . "&passwd=" . $_POST['passwd'] . "&source=remote"
        . '&badgeList=' . $_POST['badgeList'];


    $printer = get_conf('printer');
    $response = callOut($printer['badge'], $data);

    ajaxSuccess($response);
}

?>