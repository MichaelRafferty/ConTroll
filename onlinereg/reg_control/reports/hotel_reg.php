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

if(isset($_GET) && isset($_GET['conid'])) { $conid=sql_safe($_GET['conid']); }

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="registration.csv"');

$query = "SELECT DISTINCT concat_ws(' ', P.first_name, P.last_name)"
        . ", REPLACE(concat_ws('\n', P.address, P.addr_2, concat(P.city, ', ', P.state, ' ', P.zip)), '\n\n', '\n')"
        . ", M.label, U.name, U.email" // , min(B.date)"
    . " FROM reg as R JOIN perinfo as P on P.id=R.perid JOIN memList as M on M.id=R.memId"
        //. " JOIN atcon_badge as B on B.badgeId=R.id"
        . " LEFT JOIN user AS U ON U.id=R.create_user"
    . " WHERE R.conid=$conid" //  and B.action='pickup'"
    . " GROUP BY P.last_name, P.first_name" // , B.action"
    . " ORDER BY P.last_name, P.first_name, M.id"
    . ";";


echo "Name, Address, Member Type, Authorizing User, Authorizing Email"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    echo "\n";
}

?>
