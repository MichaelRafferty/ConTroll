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
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="dealers.csv"');

$query = "SELECT concat(P.first_name, ' ', P.last_name) as name"
        #. ", concat(NP1.first_name, ' ', NP1.last_name) as name1"
        #. ", concat(NP2.first_name, ' ', NP2.last_name) as name2"
        . ", R1.id, M1.label, R1.paid, R1.price"
        . ", R2.id, M2.label, R2.paid, R2.price"
    . " FROM reg as R1"
        . " JOIN perinfo as P on P.id=R1.perid"
        . " JOIN memList as M1 on M1.id=R1.memId"
        . " JOIN reg as R2 on R2.conid=R1.conid and R2.perid=R1.perid and R2.id>R1.id"
        . " JOIN memList as M2 on M2.id=R2.memId"
        . " LEFT JOIN newperson as NP1 on NP1.id=R1.newperid"
        . " LEFT JOIN newperson as NP2 on NP2.id=R2.newperid"
    . " WHERE R1.conid=$conid"
    . ";";


echo "Name"
#   . ", name1, name2
    . ", first badge, label, price, paid, second badge, label, price, paid"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
