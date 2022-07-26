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
$page = "reg_admin";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="canceled_memberships.csv"');

$query = <<<EOS
SELECT T.id, P.first_name, P.last_name, P.email_addr, P.address, P.addr_2, P.city, P.state, P.zip, P.country, A.label, R.paid
    , Y.type, Y.description, Y.amount, Y.txn_time, Y.cc_txn_id
FROM memList M
JOIN ageList A ON (M.memAge = A.ageType AND M.conid = A.conid)
JOIN reg R ON (R.memId=M.id)
JOIN transaction T ON (T.id=R.create_trans)
JOIN perinfo P ON (P.id=R.perid)
JOIN payments Y ON (Y.transid=T.id)
WHERE M.memCategory in ('cancel') and M.conid=?
ORDER BY txn_time;
EOS;

echo "ID, First Name, Last Name, Email, Addr_1, Addr_2, City, State, Zip, Country, Action, Amount, Method, Source, Amount2, Time, Transaction\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
