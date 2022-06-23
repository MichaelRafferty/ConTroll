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
$perm = "bsfs";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$perid = sql_safe($_POST['perid']);
$con = get_con();
$conid = $con['id'];

function check_memType($type, $year) {
  $date = Date("Y");
  if($type=='life') { return true; }
  if($type=='child' && $year >=$date) { return true; }
  if($type=='annual' && $year>=$date) { return true;}
  if($type=='eternal')  { return true; }

  return false;
}


$type = 'none';
$year = '';
if(isset($_POST['type'])) {$type=sql_safe($_POST['type']); }
if(isset($_POST['year'])) {$year=sql_safe($_POST['year']); }

$linkQ = "SELECT id FROM bsfs where perid=$perid";
$linkR = dbQuery($linkQ);
$linId = 0;
if($linkR->num_rows >0) {
  $link = fetch_safe_assoc($linkR);
  $linId = $link['id'];
  $linkQ = "UPDATE bsfs SET type='$type', year='$year' WHERE id=$linId;";
  dbQuery($linkQ);
} else {
  $linkQ = "INSERT IGNORE INTO bsfs (perid, type, year) VALUES ($perid, '$type', '$year');";

  $linID = dbInsert($linkQ);
}
$response['link']=$linId;

$badgeQ = "SELECT R.id, R.memId, M.label FROM reg as R, memList as M WHERE M.id=R.memId and R.perid=".sql_safe($perid).";";

$badgeR = dbQuery($badgeQ);
$badgeId = '';

if($badgeR->num_rows == 0 && check_memType($type, $year)) {
  $badgeQ = "INSERT INTO reg (conid, perid, memId, create_trans) VALUES"
    . " ($conid, $perid, 64, 20985);";
  $badgeId = dbInsert($badgeQ);

} else  {
  $badge = fetch_safe_assoc($badgeR);
  $badgeId = $badge['id'];
}

$response['badge']=$badgeId;



ajaxSuccess($response);
?>
