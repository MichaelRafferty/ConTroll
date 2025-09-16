<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "club";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="clubMember.csv"');

$year = date("Y")-5;

$query = "SELECT P.first_name, P.middle_name, P.last_name, P.address"
        . ", P.city, P.state, P.zip, P.phone, P.email_addr, P.badge_name"
        . ", B.type, B.year"
    . " FROM club as B JOIN perinfo as P ON P.id=B.perid"
    . " WHERE type in ('eternal', 'life', 'child')"
        . " OR (type = 'annual' and year >= $year)"
    . " ORDER BY type, year DESC"
    . ";";


echo "first_name, middle_name, last_name, address, city, state, zip, phone, email_addr, badge_name, member type, year"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
