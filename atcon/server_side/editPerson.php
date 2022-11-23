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

if(isset($_POST) && array_key_exists('method', $_POST) && $_POST['method'] == 'GET') {
    if(!(array_key_exists('id', $_POST) && isset($_POST['id']))) { ajaxError("Need ID"); }
    $res = dbSafeQuery("SELECT * FROM perinfo WHERE id=?;", 'i', array($_POST['id']));
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

$changeLog = "Atcon Edit ". $_POST['user'] . ": " . date(DATE_ATOM) . ": " ;
$change = false;
$datatypes = '';
$values = array();

$query = "UPDATE perinfo SET ";
if(isset($_POST['fname'])) {
  $change = true;
  $changeLog .= "first_name, ";
  $query .= "first_name=?";
  $datatypes .= 's';
  $values[] = $_POST['fname'];
}
if(isset($_POST['mname'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "middle_name, ";
  $query .= "middle_name=?";
  $datatypes .= 's';
  $values[] = $_POST['mname'];
}
if(isset($_POST['lname'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "last_name, ";
  $query .= "last_name=?";
  $datatypes .= 's';
  $values[] = $_POST['lname'];
}
if(isset($_POST['suffix'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "suffix, ";
  $query .= "suffix=?";
  $datatypes .= 's';
  $values[] = $_POST['suffix'];
}
if(isset($_POST['email'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "email_addr, ";
  $query .= "email_addr=?";
  $datatypes .= 's';
  $values[] = $_POST['email'];
}
if(isset($_POST['phone'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "phone, ";
  $query .= "phone=?";
  $datatypes .= 's';
  $values[] = $_POST['phone'];
}
if(isset($_POST['badge'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "badge_name, ";
  $query .= "badge_name=?";
  $datatypes .= 's';
  $values[] = $_POST['badge'];
}
if(isset($_POST['address'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "address, ";
  $query .= "address=?";
  $datatypes .= 's';
  $values[] = $_POST['address'];
}
if(isset($_POST['addr2'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "addr_2, ";
  $query .= "addr_2=?";
  $datatypes .= 's';
  $values[] = $_POST['addr_2'];
}
if(isset($_POST['city'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "city, ";
  $query .= "city=?";
  $datatypes .= 's';
  $values[] = $_POST['city'];
}
if(isset($_POST['state'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "state, ";
  $query .= "state=?";
  $datatypes .= 's';
  $values[] = $_POST['state'];
}
if(isset($_POST['zip'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "zip, ";
  $query .= "zip=?";
  $datatypes .= 's';
  $values[] = $_POST['zip'];
}
if(isset($_POST['country'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "country, ";
  $query .= "country=?";
  $datatypes .= 's';
  $values[] = $_POST['country'];
}
if(isset($_POST['share_mail'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "sharemail_ok, ";
  $query .= "sharemail_ok=?";
  $datatypes .= 's';
  $values[] = $_POST['share_mail'];
}
if(isset($_POST['banned'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "banned, ";
  $query .= "banned=?";
  $datatypes .= 's';
  $values[] = $_POST['banned'];
}
if(isset($_POST['active'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "active, ";
  $query .= "active=?";
  $datatypes .= 's';
  $values[] = $_POST['active'];
}
if(isset($_POST['open_notes'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "open_notes, ";
  $query .= "open_notes=?";
  $datatypes .= 's';
  $values[] = $_POST['open_notes'];
}
if(isset($_POST['admin_notes'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "admin_notes, ";
  $query .= "admin_notes=?";
  $datatypes .= 's';
  $values[] = $_POST['admin_notes'];
}
if($change) {
    $query .= " WHERE id=?;";
    $datatypes .= 'i';
    $values[] = $_POST['id'];

    $res = dbSafeCmd($query, $datatypes, $values);
    $query2 = "UPDATE perinfo SET change_notes=CONCAT(change_notes, '<br/>', ?) WHERE id=?;";
    $res = dbSafeCmd($query2, 'si', array($changeLog, $_POST['id']));
}

$response['changed'] = $change;
$response['changeLog'] = $changeLog;

ajaxSuccess($response);
?>
