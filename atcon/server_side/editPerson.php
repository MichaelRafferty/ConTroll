<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

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


if($_POST['method'] == 'GET') {
    if(!isset($_POST['id'])) { ajaxError("Need ID"); }
    $res = dbQuery("SELECT * FROM perinfo WHERE id=".sql_safe($_POST['id']).";");
    $perinfo = fetch_safe_assoc($res);
    $perinfo["prefix"] = htmlspecialchars($_POST['prefix']);
    ajaxSuccess($perinfo);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != "POST") { 
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$changeLog = "Atcon Edit ". sql_safe($_POST['user']) . ": " . date(DATE_ATOM) . ": " ;
$change = false;

$query = "UPDATE perinfo SET ";
if(isset($_POST['fname'])) {
  $change = true;
  $changeLog .= "first_name, ";
  $query .= "first_name='" . sql_safe($_POST['fname']) . "'";
}
if(isset($_POST['mname'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "middle_name, ";
  $query .= "middle_name='" . sql_safe($_POST['mname']) . "'";
}
if(isset($_POST['lname'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "last_name, ";
  $query .= "last_name='" . sql_safe($_POST['lname']) . "'";
}
if(isset($_POST['suffix'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "suffix, ";
  $query .= "suffix='" . sql_safe($_POST['suffix']) . "'";
}
if(isset($_POST['email'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "email_addr, ";
  $query .= "email_addr='" . sql_safe($_POST['email']) . "'";
}
if(isset($_POST['phone'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "phone, ";
  $query .= "phone='" . sql_safe($_POST['phone']) . "'";
}
if(isset($_POST['badge'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "badge_name, ";
  $query .= "badge_name='" . sql_safe($_POST['badge']) . "'";
}
if(isset($_POST['address'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "address, ";
  $query .= "address='" . sql_safe($_POST['address']) . "'";
}
if(isset($_POST['addr2'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "addr_2, ";
  $query .= "addr_2='" . sql_safe($_POST['addr2']) . "'";
}
if(isset($_POST['city'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "city, ";
  $query .= "city='" . sql_safe($_POST['city']) . "'";
}
if(isset($_POST['state'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "state, ";
  $query .= "state='" . sql_safe($_POST['state']) . "'";
}
if(isset($_POST['zip'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "zip, ";
  $query .= "zip='" . sql_safe($_POST['zip']) . "'";
}
if(isset($_POST['country'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "country, ";
  $query .= "country='" . sql_safe($_POST['country']) . "'";
}
if(isset($_POST['bid'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "bid_ok, ";
  $query .= "bid_ok='" . sql_safe($_POST['bid']) . "'";
}
if(isset($_POST['share_mail'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "sharemail_ok, ";
  $query .= "sharemail_ok='" . sql_safe($_POST['share_mail']) . "'";
}
if(isset($_POST['address_ok'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "addr_good, ";
  $query .= "addr_good='" . sql_safe($_POST['address_ok']) . "'";
}
if(isset($_POST['checks_ok'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "checks_ok, ";
  $query .= "checks_ok='" . sql_safe($_POST['checks_ok']) . "'";
}
if(isset($_POST['banned'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "banned, ";
  $query .= "banned='" . sql_safe($_POST['banned']) . "'";
}
if(isset($_POST['active'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "active, ";
  $query .= "active='" . sql_safe($_POST['active']) . "'";
}
if(isset($_POST['open_notes'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "open_notes, ";
  $query .= "open_notes='" . sql_safe($_POST['open_notes']) . "'";
}
if(isset($_POST['admin_notes'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "admin_notes, ";
  $query .= "admin_notes='" . sql_safe($_POST['admin_notes']) . "'";
}
if($change) {
  $query .= " WHERE id='" . sql_safe($_POST['id']) . "';";

  $res = dbQuery($query);
  $query2 = "UPDATE perinfo SET change_notes=CONCAT(change_notes, '<br/>$changeLog') WHERE id='".sql_safe($_POST['id'])."';";
  $res = dbQuery($query2);
}

$response['changed'] = $change;
$response['changeLog'] = $changeLog;

ajaxSuccess($response);
?>
