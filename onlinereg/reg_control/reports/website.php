<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reg_admin";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: text/plain');
//header('Content-Disposition: attachment; filename="website.txt"');

$query = "SELECT CASE WHEN (contact_ok='N' OR P.badge_name is NULL or P.badge_name = '') THEN CONCAT(P.last_name,P.first_name) ELSE P.badge_name END as order_tag,"
    . " CASE WHEN (contact_ok='N' OR P.badge_name is NULL or P.badge_name = '') THEN CONCAT(P.first_name, ' ', P.last_name) ELSE P.badge_name END as output_name"
    . " FROM perinfo P JOIN reg R ON R.perid=P.id"
    . " WHERE R.conid=? and P.share_reg_ok='Y' or P.contact_ok='Y'"
    . " ORDER BY order_tag"
    . ";";


//echo $query . "\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = $reportR->fetch_array()) {
	echo $reportL[1] . "\n";
}

?>
