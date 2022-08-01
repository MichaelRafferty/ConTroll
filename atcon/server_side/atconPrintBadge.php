<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

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

$transid = sql_safe($_POST['transid']);
$transQ = "SELECT id FROM atcon where transid=$transid";
$transR = fetch_safe_assoc(dbQuery($transQ));
$atconid = $transR['id'];

$owner = sql_safe($_POST['owner']);
$action= sql_safe($_POST['action']);



$badges = json_decode($_POST['badgeList'], true);

if(isset($_POST['printed']) && $_POST['printed'] == 'true') {
  $badgeQ = "INSERT IGNORE INTO atcon_badge"
    . " (atconId, badgeId, action, comment) VALUES ";

  $multi = false;
  foreach($badges as $badge) {
    if($multi) { $badgeQ .= ", "; }
    $badgeQ .= "($atconid, " . $badge['badgeId'] . ", '$action', 'By: $owner')";
    $multi=true;
  }

  $result = dbInsert($badgeQ);
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
