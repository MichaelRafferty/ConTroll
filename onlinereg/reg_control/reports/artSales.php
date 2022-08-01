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
header('Content-Disposition: attachment; filename="art_sales.csv"');

$query = "SELECT"
    . " V.name"
    . ", s.a_total, p.p_total"
    . ", concat_ws(' ', artist.ship_addr, artist.ship_addr2, artist.ship_city, artist.ship_state, artist.ship_zip, artist.ship_country)"
    . " FROM artshow as A JOIN artist ON artist.id=A.artid"
        . " JOIN vendors AS V on V.id=artist.vendor"
        . " LEFT JOIN (SELECT A.art_key, SUM(I.final_price) as a_total"
            . " FROM artshow as A JOIN artItems as I ON I.artshow=A.id"
            . " WHERE A.conid=$conid and I.type='art'"
            . " GROUP BY A.art_key) as s"
            . " ON s.art_key=A.art_Key"
        . " LEFT JOIN (SELECT A.art_key"
            . ", SUM(I.sale_price * (I.original_qty - I.quantity)) as p_total"
            . " FROM artshow as A JOIN artItems as I ON I.artshow=A.id"
            . " WHERE A.conid=$conid and I.type='print'"
            . " GROUP BY A.art_key) as p"
            . " ON p.art_key=A.art_Key"
    . " WHERE A.conid=$conid"
    . " GROUP BY A.art_key;";

//echo $query; exit();


echo "Artist, Total, Address"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    $total = $reportL[1] + $reportL[2];
    if($total > 0) {
        printf("\"%s\",", html_entity_decode($reportL[0], ENT_QUOTES | ENT_HTML401));
        printf("\"%s\",", $total);
        printf("\"%s\",", html_entity_decode($reportL[3], ENT_QUOTES | ENT_HTML401));
        echo "\n";
    }
}

?>
