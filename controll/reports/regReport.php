<?php
require_once "../lib/base.php";
require_once '../../lib/outputCSV.php';


$need_login = google_init("page");
$page = "reg_staff";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$con = get_conf("con");
$conid=$con['id'];

$query = <<<EOS
SELECT R.id,
    TRIM(REGEXP_REPLACE(CONCAT(P.first_name, ' ', P.middle_name, ' ', P.last_name, ' ', P.suffix), '  *', ' ')) AS fullName,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', P.address, P.addr_2, P.city, P.state, P.zip, P.country), '  *', ' ')) AS fullAddr,
    P.zip as locale, P.country, P.email_addr, M.label, R.price, R.paid, R.status, R.create_date, MIN(H.logdate) AS date
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
LEFT OUTER JOIN regActions H ON (H.regid=R.id AND H.action='print')
WHERE R.conid=?
GROUP BY id, fullName, fullAddr, locale, country, email_addr, label, M.price, paid, status, create_date;
EOS;

$reportR = dbSafeQuery($query, 'i', array($conid));
$tableData = [];
while ($reportL = $reportR->fetch_assoc()) {
   $tableData[] = $reportL;
}

outputCSV('reg_report', $tableData);
