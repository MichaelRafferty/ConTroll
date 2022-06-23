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
$perm = "artist";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');

$artid=sql_safe($_POST['artid']);
$perid=sql_safe($_POST['perid']);
$vendor=sql_safe($_POST['vendor']);
$values = array();

if(isset($_POST['agent_only'])) {
  if($artid == "" or $perid == "") { ajaxError("No Data"); exit(); }
  $values = array(
    'agent_perid'=>sql_safe($_POST['agent_id'])
  );
} else {
    $values=array();

    if(isset($_POST['ship_addr'])) {
        $values['ship_addr']=sql_safe($_POST['ship_addr']);
    }
    if(isset($_POST['ship_addr2'])) {
        $values['ship_addr2']=sql_safe($_POST['ship_addr2']);
    }
    if(isset($_POST['ship_city'])) {
        $values['ship_city']=sql_safe($_POST['ship_city']);
    }
    if(isset($_POST['ship_state'])) {
        $values['ship_state']=sql_safe($_POST['ship_state']);
    }
    if(isset($_POST['ship_country'])) {
        $values['ship_country']=sql_safe($_POST['ship_country']);
    }
    if(isset($_POST['ship_zip'])) {
        $values['ship_zip']=sql_safe($_POST['ship_zip']);
    }
}

$emailR = dbQuery("SELECT email_addr FROM perinfo where id='$perid'");
$emailL = fetch_safe_assoc($emailR);
$email = $emailL['email_addr'];

$query = "SET artist='$perid', login='$email', vendor='$vendor'";

foreach ($values as $key=>$value) {
  $query.= ", $key='$value'";
}
if($artid == "") {
  $query = "INSERT INTO artist $query;";
  $artid = dbInsert($query);
} else {
  $query = "UPDATE artist $query WHERE id=$artid;";
  dbQuery($query);
}
$response['perid']=$perid;
$response['artid']=$artid;
$response['query']=$query;

ajaxSuccess($response);
?>
