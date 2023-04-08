<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

if(isset($_GET) && isset($_GET['conid'])) { $conid=$_GET['conid']; }

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="participants.csv"');

// query had commented out field of " // , min(B.date)"
// and commented out where of //  and B.action='pickup'"
$query = <<<EOS
SELECT DISTINCT P.first_name, P.last_name, P.email_addr, P.id, M.label
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memLabel M ON (M.id=R.memId)
WHERE R.conid=? AND M.label LIKE '%Participant%' AND M.memAge='all';
EOS;

echo "First Name, Last Name, Email, ID, Reg type\n";

$reportR = dbSafeQuery($query, 'i', array($conid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    echo "\n";
}

?>
