<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reg_admin";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="canceled_memberships.csv"');

$query = "SELECT T.id"
        . ", Y.type, Y.description, Y.amount, Y.txn_time, Y.cc, Y.cc_txn_id"
    . " FROM transaction as T"
        . " JOIN payments as Y ON Y.transid=T.id"
    . " WHERE T.conid=$conid and Y.description like '%Online%'"
    . " ORDER BY txn_time;";

echo "ID, Type, Description, amount, time, id"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
