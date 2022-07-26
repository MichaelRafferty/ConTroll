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
header('Content-Disposition: attachment; filename="reg_transactions.csv"');

$query = <<<EOS
SELECT T.id, Y.type, Y.description, COUNT(DISTINCT R.perid) AS people, COUNT(DISTINCT P.email_addr) AS emails, T.create_date, SUM(R.paid) AS reg_paid, T.paid, Y.amount
FROM memList M
JOIN reg R ON (R.memId=M.id)
JOIN perinfo P ON (P.id=R.perid)
JOIN transaction T ON (T.id=R.create_trans)
JOIN payments Y ON (Y.transid=T.id)
WHERE M.conid=? and M.memCategory IN ('standard', 'yearahead')
GROUP BY T.id 
ORDER BY emails;
EOS;

echo "First Name, Last Name, Email, Type, Price, Transaction, Total, Method, Description, Paid\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
