<?php
global $db_ini;

require_once "../lib/base.php";

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

$perid=$_GET['perid'];
$perQ = <<<EOS
SELECT P.id, first_name, middle_name, last_name, suffix, badge_name, email_addr, phone, address, addr_2, city, state, zip, country
    , R.id AS badge, M.label
FROM perinfo P
LEFT OUTER JOIN reg R ON (R.perid=P.id AND R.conid=?)
LEFT OUTER JOIN memLabel M ON (M.id=R.memId)
WHERE P.id=?;
EOS;

$artistQ = <<<EOS
SELECT A.id, vendor, login, ship_addr, ship_addr2, ship_city, ship_state, ship_zip, ship_country, agent, agent_request
FROM artist A
LEFT OUTER JOIN artshow S ON (S.artid=A.id)
WHERE artist=?;
EOS;

$person = fetch_safe_assoc(dbSafeQuery($perQ, 'ii', array($conid, $perid)));
$artist = fetch_safe_assoc(dbSafeQuery($artistQ, 'i', array($perid)));

$agent = null;
if(isset($artist['agent']) && $artist['agent']!="") {
    $agentQ = <<<EOS
SELECT P.id, first_name, middle_name, last_name, suffix, badge_name, email_addr, phone, address, addr_2, city, state, zip, country, R.id as badge, M.label
FROM perinfo P
LEFT OUTER JOIN reg R on (R.perid=P.id AND R.conid=?)
LEFT OUTER JOIN memLabel M ON (M.id=R.memId)
WHERE P.id=?;
EOS;

  $agent = fetch_safe_assoc(dbSafeQuery($agentQ, 'ii', $conid, $artist['agent']));
}

$vendor = null;
if(isset($artist['vendor']) && $artist['vendor']!="") {
  $vendorQ = "SELECT id, name, website, description, email FROM vendors WHERE id=?;";
  $vendor = fetch_safe_assoc(dbSafeQuery($vendorQ, 'i', array($artist['vendor'])));
} else {
  $vendorQ = "SELECT id, name, website, description, email FROM vendors WHERE email=?;";
  $vendor = fetch_safe_assoc(dbSafeQuery($vendorQ, 's', array($person['email_addr'])));
}


$response['person']=$person;
$response['artist']=$artist;
$response['agent']=$agent;
$response['vendor']=$vendor;

ajaxSuccess($response);
?>
