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

$query = <<<EOS
SELECT e.id, e.exhibitorName, eRY.exhibitorNumber, sum(a.a_total) as a_total, sum(p.p_total) as p_total,
TRIM(concat_ws(' ', e.addr, e.addr2, e.city, e.state, e.zip, e.country)) as address,
TRIM(concat_ws(' ', e.shipCompany, e.shipAddr, e.shipAddr2, e.shipCity, e.shipState, e.shipZip, e.shipCountry)) as shipAddr
FROM exhibitors e 
    join exhibitorYears eY on eY.exhibitorId=e.id
    join exhibitorRegionYears eRY on eRY.exhibitorYearId = eY.id
    join exhibitsRegionYears xRY on xRY.id = eRY.exhibitsRegionYearId
    JOIN exhibitsRegions xR on xR.id=xRY.exhibitsRegion
    LEFT JOIN (SELECT eRY.id as eRY, I.item_key, sum(I.final_price) as a_total 
            FROM artItems I 
                JOIN exhibitorRegionYears eRY ON eRY.id=I.exhibitorRegionYearId
                join exhibitorYears eY on eRY.exhibitorYearId = eY.id
            WHERE I.type='art' and eY.conId=? and I.status!='Checked Out'
            GROUP BY eRY.id) as a 
        ON a.eRY = eRY.id
    LEFT JOIN (SELECT eRY.id as eRY, I.item_key, sum(I.sale_price * (I.original_qty-I.quantity)) as p_total 
            FROM artItems I 
                JOIN exhibitorRegionYears eRY ON eRY.id=I.exhibitorRegionYearId
                join exhibitorYears eY on eRY.exhibitorYearId = eY.id
            WHERE I.type='print' and eY.conId=?
            GROUP BY eRY.id) as p 
        ON p.eRY = eRY.id
where eY.conid=? and xR.regionType='Art Show' group by e.id;
EOS;

//echo $query; exit();


echo "Artist, Total, Address"
    . "\n";

$reportR = dbSafeQuery($query, 'iii', array($conid, $conid, $conid));
while($reportL = $reportR->fetch_assoc()) {
    #echo '"' . $reportL['exhibitorName'] . '","' . $reportL['a_total'] . '","' . $reportL['p_total'] . '","n/a","' . $reportL['address'] . "\"\n";
    if($reportL['a_total'] == NULL) $reportL['a_total'] = 0;
    if($reportL['p_total'] == NULL) $reportL['p_total'] = 0;
    $total = $reportL['a_total'] + $reportL['p_total'];
    if($total > 0) {
        echo '"' . $reportL['exhibitorName'] . '","'. $total. '","' . $reportL['address'] . "\"\n";
    }
}
