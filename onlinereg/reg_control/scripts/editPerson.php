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
$perm = "search";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || (!checkAuth($check_auth['sub'], $perm) &&
                            !checkAuth($check_auth['sub'], 'atcon'))) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == "GET") {
    if(!isset($_GET['id'])) { ajaxError("Need ID"); }
    $idquery = <<<EOS
SELECT perinfo.*, conlist.label as last_reg
FROM perinfo
LEFT OUTER JOIN reg ON (reg.perid=perinfo.id)
LEFT JOIN conlist ON (conlist.id=reg.conid)
WHERE perinfo.id=?
ORDER BY conlist.id;
EOS;
    $res = dbSafeQuery($idquery, 'i', array($_GET['id']));
    $perinfo = fetch_safe_assoc($res);
    if (isset($_GET['prefix'])) {
        $perinfo["prefix"] = htmlspecialchars($_GET['prefix']);
    }
    ajaxSuccess($perinfo);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != "POST") {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$changeLog = $check_auth['email'] . ": " . date(DATE_ATOM) . ": " ;
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
if(isset($_POST['share_reg'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "share_reg_ok, ";
  $query .= "share_reg_ok='" . sql_safe($_POST['share_reg']) . "'";
}
if(isset($_POST['contact_ok'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "contact_ok, ";
  $query .= "contact_ok='" . sql_safe($_POST['contact_ok']) . "'";
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
