<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conf = get_conf('con');
$artshow = get_conf('artshow');

$artistQ = "SELECT art_name, perid, other_email FROM artist where id=?;";
$artist = fetch_safe_assoc(dbSafeQuery($artistQ, 's', array($_GET['id'])));
$perQ = "SELECT  first_name, middle_name, last_name, suffix, email_addr FROM perinfo where id='".$artist['perid']."';";
$showQ = "SELECT id, art_key FROM artshow WHERE artid='".sql_safe($_GET['id'])."' AND conid='".$con['id']."';";

$per = fetch_safe_assoc(dbQuery($perQ));
$show = fetch_safe_assoc(dbQuery($showQ));

$toAddr = array();
if($artist['other_email']!='') { array_push($toAddr, $artist['other_email']); }
if($per['email_addr']!='') { array_push($toAddr, $per['email_addr']); }

$intro = $per['first_name'] . " " . $per['last_name'];

if($artist['art_name']!='') { $intro .= "(".$artist['art_name'].")"; }

$email = array(
    'ToAddresses' => $toAddr,
    "Data" => "The URL is: " . $artshow['url'] . "<br/>" .
        "Your Artist id is: ". $show["art_key"]."<br/>" .
        "Your ArtShow Pin is: ". $show["id"]
  );

$response['body'] = $email;
$response['to'] = $toAddr;


ajaxSuccess($response);
?>
