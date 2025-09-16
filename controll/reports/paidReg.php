<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="paidReg.csv"');

// this query had a hard code of conid=54 in it, changed that to $conid
$query = <<<EOS
SELECT P.first_name, P.last_name, P.email_addr, M.label, R.paid, R.create_trans, T.paid, Y.type, Y.description, Y.amount
FROM memLabel M
JOIN reg R ON (R.memId=M.id)
JOIN transaction T ON (T.id=R.create_trans)
JOIN perinfo P ON (P.id=R.perid)
JOIN payments AS Y ON (Y.transid=T.id)
WHERE M.memCategory in ('standard', 'yearahead') and M.conid=?
ORDER BY create_trans;
EOS;

// what is this query that was left in the file
//$query = "SELECT T.id, Y.type, Y.description"
//        . ", COUNT(DISTINCT R.perid) as people, COUNT(DISTINCT P.email_addr) as emails"
//        . ", T.create_date, SUM(R.paid) as reg_paid, T.paid, Y.amount"
//    . " FROM memList as M"
//        . " JOIN reg as R on R.memId=M.id"
//        . " JOIN perinfo as P on P.id=R.perid"
//        . " JOIN transaction as T on T.id=R.create_trans"
//        . " JOIN payments as Y on Y.transid=T.id"
//    . " WHERE M.conid=54 and M.memCategory in ('standard', 'yearahead')"
//    . " GROUP BY T.id ORDER BY emails;";

echo "First Name, Last Name, Email, Type, Price, Transaction, Total, Method, Description, Paid\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
