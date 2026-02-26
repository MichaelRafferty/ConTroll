<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'club';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$perid = $_POST['perid'];
$con = get_con();
$conid = $con['id'];
$user_perid = $authToken->getPerid();

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
if(isset($_POST['type'])) {$type=$_POST['type']; }
if(isset($_POST['year'])) {$year=$_POST['year']; }

$linkQ = "SELECT id FROM club where perid=?;";
$linkR = dbSafeQuery($linkQ, 'i', array($perid));
$linId = 0;
if($linkR->num_rows >0) {
  $link = fetch_safe_assoc($linkR);
  $linId = $link['id'];
  $linkQ = "UPDATE club SET type=?, year=? WHERE id=?;";
  dbSafeCmd($linkQ, 'ssi', array($type, $year, $linId));
} else {
  $linkQ = "INSERT IGNORE INTO club (perid, type, year) VALUES (?, ?, ?);";

  $linID = dbSafeInsert($linkQ, 'ssi', array($perid, $type, $year));
}
$response['link']=$linId;

$badgeQ = <<<EOS
SELECT R.id, R.memId, M.label
FROM reg R
JOIN memList M ON ( M.id=R.memId)
WHERE R.perid=?;
EOS;

$badgeR = dbSafeQuery($badgeQ, 'i', array($perid));
$badgeId = '';
// why is this a hardced memId and transaction id???
if($badgeR->num_rows == 0 && check_memType($type, $year)) {
  $badgeQ = "INSERT INTO reg (conid, perid, memId, create_trans, create_user) VALUES (?, ?, ?, ?, ?);";
  $badgeId = dbSafeInsert($badgeQ, 'iiii', array ($conid, $perid, 64, 20985, $user_perid));

} else  {
  $badge = fetch_safe_assoc($badgeR);
  $badgeId = $badge['id'];
}

$response['badge']=$badgeId;



ajaxSuccess($response);
