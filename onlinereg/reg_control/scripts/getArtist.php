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
$con = get_con();
$conid=$con['id'];
$conf=get_conf('con');

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if($_SERVER['REQUEST_METHOD'] != "GET") { ajaxError("No Data"); }
if(!isset($_GET['perid'])) { ajaxError("No Data"); }

$perid=sql_safe($_GET['perid']);
$perQ = "SELECT perinfo.id, first_name, middle_name, last_name, suffix, badge_name, email_addr, phone, address, addr_2, city, state, zip, country"
    . ", reg.id as badge, memList.label as label"
    . " FROM perinfo"
    . " LEFT JOIN reg on reg.perid=perinfo.id AND reg.conid=$conid"
    . " LEFT JOIN memList on memList.id=reg.memId"
    . " WHERE perinfo.id=$perid";

$artistQ = "SELECT artist.id, vendor, login, ship_addr, ship_addr2, ship_city, ship_state, ship_zip, ship_country"
    . ", agent, agent_request"
    . " FROM artist"
        . " LEFT JOIN artshow as S on S.artid=artist.id"
    . " WHERE artist=$perid;";
$person = fetch_safe_assoc(dbQuery($perQ));
$artist = fetch_safe_assoc(dbQuery($artistQ));

$agent = null;
if(isset($artist['agent']) && $artist['agent']!="") {
  $agentQ = "SELECT perinfo.id, first_name, middle_name, last_name, suffix, badge_name, email_addr, phone, address, addr_2, city, state, zip, country"
    . ", reg.id as badge, memList.label as label"
    . " FROM perinfo"
    . " LEFT JOIN reg on reg.perid=perinfo.id AND reg.conid=$conid"
    . " LEFT JOIN memList on memList.id=reg.memId"
    . " WHERE perinfo.id=". $artist['agent'] . ";";
  $agent = fetch_safe_assoc(dbQuery($agentQ));
}

$vendor = null;
if(isset($artist['vendor']) && $artist['vendor']!="") {
  $vendorQ = "SELECT id, name, website, description, email FROM vendors WHERE id=". $artist['vendor']. ";";
  $vendor = fetch_safe_assoc(dbQuery($vendorQ));
} else {
  $vendorQ = "SELECT id, name, website, description, email FROM vendors WHERE email='". $person['email_addr']. "';";
  $vendor = fetch_safe_assoc(dbQuery($vendorQ));

}


$response['person']=$person;
$response['artist']=$artist;
$response['agent']=$agent;
$response['vendor']=$vendor;

ajaxSuccess($response);
?>
