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

if(!isset($_POST) || !isset($_POST['newID'])) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$changeLog = "Atcon Edit $user: ". date(DATE_ATOM)
    . ": updating from conflict " . $_POST['newID'] . "=>"
    . $_POST['oldID'] . ": ";
$change = false;

$newData = dbSafeQuery("SELECT * FROM newperson WHERE id=?;", 'i', array($_POST['newID']))->fetch_assoc();

dbSafeCmd("UPDATE newperson SET perid=? WHERE id=?;", 'ii', array($_POST['oldID'], $_POST['newID']));

$datatypes = '';
$values = array();
$query = "UPDATE perinfo SET ";
if(isset($_POST['conflictFormName'])) {
  $change = true;
  $changeLog .= "first_name, middle_name, last_name, suffix ";
  $query .= "first_name=?, middle_name=?, last_name=?, suffix=?";
  $datatypes .= 'ssss';
  $values[] = blankifnotset($newData['first_name']);
  $values[] = blankifnotset($newData['middle_name']);
  $values[] = blankifnotset($newData['last_name']);
  $values[] = blankifnotset($newData['suffix']);
}
if(isset($_POST['conflictFormEmail'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "email_addr, ";
  $query .= "email_addr=?";
  $datatypes .= 's';
  $values[] = blankifnotset($newData['email_addr']);
}
if(isset($_POST['conflictFormPhone'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "phone, ";
  $query .= "phone=?";
  $datatypes .= 's';
  $values[] = blankifnotset($newData['phone']);
}
if(isset($_POST['conflictFormBadge'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "badge_name, ";
  $query .= "badge_name=?";
  $datatypes .= 's';
  $values[] = $newData['badge_name'];
}
if(isset($_POST['conflictFormAddr'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "address, addr_2, city, state, zip, country";
  $querty .= "address=?, addr_2=?, city=?, state=?, zip=?, country=?";
  $datatypes .= 'ssssss';
  $values[] = blankifnotset($newData['address']);
  $values[] = blankifnotset($newData['addr_2']);
  $values[] = blankifnotset($newData['city']);
  $values[] = blankifnotset($newData['state']);
  $values[] = blankifnotset($newData['zip']);
  $values[] = blankifnotset($newData['address']);
  $values[] = blankifnotset($newData['country']);
}
if($change) {
  if($change) { $query .= ", "; }
  $changeLog .= "active, ";
  $query .= "active='Y'";
}

if($change) {
    $query .= " WHERE id=?;";
    $datatypes .= 'i';
    $values[] = $_POST['oldID'];

    $res = dbSafeCmd($query, $datatypes, $values);
    $query2 = "UPDATE perinfo SET change_notes=CONCAT(change_notes, '<br/>', ?) WHERE id=?;";
    $res = dbSafeCmd($query2, 'si', array($chamgeLog, $_POST['oldID']));
}

$setQ = "UPDATE reg SET perid=? WHERE newperid?;";
$setR = dbSafeCmd($setQ, 'ii', array($_POST['oldID'], $_POST['newID']));
$setQ = "UPDATE transaction SET perid=? WHERE newperid=?;";
$setR = dbSafeCmd($setQ, 'ii', array($_POST['oldID'], $_POST['newID']));

$response['id'] = $_POST['oldID'];
$response['changeLog'] = $changeLog;
$response['change'] = $change;

ajaxSuccess($response);
?>
