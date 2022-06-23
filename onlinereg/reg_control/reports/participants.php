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
header('Content-Disposition: attachment; filename="participants.csv"');

$query = "SELECT DISTINCT P.first_name, P.last_name, P.email_addr, P.id"
        . ", M.label" // , min(B.date)"
    . " FROM reg as R JOIN perinfo as P on P.id=R.perid JOIN memList as M on M.id=R.memId"
    . " WHERE R.conid=$conid" //  and B.action='pickup'"
    . " AND M.label like '%Participant%' AND M.memAge='all'"
    . ";";


echo "First Name, Last Name, Email, ID, Reg type"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    echo "\n";
}

?>
