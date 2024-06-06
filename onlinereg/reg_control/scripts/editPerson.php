<?php
global $db_ini;

require_once "../lib/base.php";

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
$types = '';
$values = array();

$query = "UPDATE perinfo SET ";
if(isset($_POST['fname'])) {
  $change = true;
  $changeLog .= "first_name, ";
  $query .= "first_name=?";
  $types .= 's';
  $values[] = $_POST['fname'];
}
if(isset($_POST['mname'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "middle_name, ";
  $query .= "middle_name=?";
  $types .= 's';
  $values[] = $_POST['mname'];
}
if(isset($_POST['lname'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "last_name";
  $query .= "last_name='" . sql_safe($_POST['lname']) . "'";
}
if(isset($_POST['suffix'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "suffix, ";
  $query .= "suffix=?";
  $types .= 's';
  $values[] = $_POST['suffix'];
}
if(isset($_POST['email'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "email_addr, ";
  $query .= "email_addr=?";
  $types .= 's';
  $values[] = $_POST['email'];
}
if(isset($_POST['phone'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "phone, ";
  $query .= "phone=?";
  $types .= 's';
  $values[] = $_POST['phone'];
}
if(isset($_POST['legalName'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "legalName, ";
  $query .= "legalName=?";
  $types .= 's';
  $values[] = $_POST['legalName'];
}
if (isset($_POST['badge'])) {
  if ($change) {
    $query .= ', ';
  }
  $change = true;
  $changeLog .= 'badge_name, ';
  $query .= 'badge_name=?';
  $types .= 's';
  $values[] = $_POST['badge'];
}
if(isset($_POST['address'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "address, ";
  $query .= "address=?";
  $types .= 's';
  $values[] = $_POST['address'];
}
if(isset($_POST['addr2'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "addr_2, ";
  $query .= "addr_2=?";
  $types .= 's';
  $values[] = $_POST['addr2'];
}
if(isset($_POST['city'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "city, ";
  $query .= "city=?";
  $types .= 's';
  $values[] = $_POST['city'];
}
if(isset($_POST['state'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "state, ";
  $query .= "state=?";
  $types .= 's';
  $values[] = $_POST['state'];
}
if(isset($_POST['zip'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "zip, ";
  $query .= "zip=?";
  $types .= 's';
  $values[] = $_POST['zip'];
}
if(isset($_POST['country'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "country, ";
  $query .= "country=?";
  $types .= 's';
  $values[] = $_POST['country'];
}
if(isset($_POST['share_reg'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "share_reg_ok, ";
  $query .= "share_reg_ok=?";
  $types .= 's';
  $values[] = $_POST['share_reg'];
}
if(isset($_POST['contact_ok'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "contact_ok, ";
  $query .= "contact_ok=?";
  $types .= 's';
  $values[] = $_POST['contact_ok'];
}
if(isset($_POST['banned'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "banned, ";
  $query .= "banned=?";
  $types .= 's';
  $values[] = $_POST['banned'];
}
if(isset($_POST['active'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "active, ";
  $query .= "active=?";
  $types .= 's';
  $values[] = $_POST['active'];
}
if(isset($_POST['open_notes'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "open_notes, ";
  $query .= "open_notes=?";
  $types .= 's';
  $values[] = $_POST['open_notes'];
}
if(isset($_POST['admin_notes'])) {
  if($change) { $query .= ", "; }
  $change = true;
  $changeLog .= "admin_notes, ";
  $query .= "admin_notes=?";
  $types .= 's';
  $values[] = $_POST['admin_notes'];
}
// updated by
if ($change) {
  $query .= ', ';
  $changeLog .= 'updatedBy, ';
  $query .= 'updatedBy=?';
  $types .= 'i';
  $values[] = $_SESSION['user_id'];
}

if($change) {
  $query .= " WHERE id=?";
  $types .= 'i';
  $values[] = $_POST['id'];

  $res = dbSafeCmd($query, $types, $values);
  $query2 = <<<EOS
UPDATE perinfo SET change_notes=CONCAT(IFNULL(change_notes, ''), '<br/>', ?)
WHERE id=?;
EOS;

  $res = dbSafeCmd($query2, 'si', array($changeLog, $_POST['id']));
}

$response['changed'] = $change;
$response['changeLog'] = $changeLog;

ajaxSuccess($response);
?>
