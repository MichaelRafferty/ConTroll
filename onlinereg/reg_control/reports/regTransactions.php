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
header('Content-Disposition: attachment; filename="members.csv"');

$query = "SELECT T.id, Y.type, Y.description"
        . ", COUNT(DISTINCT R.perid) as people, COUNT(DISTINCT P.email_addr) as emails"
        . ", T.create_date, SUM(R.paid) as reg_paid, T.paid, Y.amount"
    . " FROM memList as M"
        . " JOIN reg as R on R.memId=M.id"
        . " JOIN perinfo as P on P.id=R.perid"
        . " JOIN transaction as T on T.id=R.create_trans"
        . " JOIN payments as Y on Y.transid=T.id"
    . " WHERE M.conid=54 and M.memCategory in ('standard', 'yearahead')"
    . " GROUP BY T.id ORDER BY emails;"

echo "First Name, Last Name, Email, Type, Price, Transaction, Total, Method, Description, Paid "
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
