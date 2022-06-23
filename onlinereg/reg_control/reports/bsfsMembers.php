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

$need_login = google_init("page");
$page = "bsfs";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="bsfsMember.csv"');

$year = date("Y")-5;

$query = "SELECT P.first_name, P.middle_name, P.last_name, P.address"
        . ", P.city, P.state, P.zip, P.phone, P.email_addr, P.badge_name"
        . ", B.type, B.year"
    . " FROM bsfs as B JOIN perinfo as P ON P.id=B.perid"
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

?>
