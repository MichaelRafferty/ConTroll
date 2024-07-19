<?php
require_once "../lib/base.php";

$need_login = google_init("page");
$page = "reports";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];
$artid=100;

if(!isset($_GET) || !isset($_GET['artid'])) {
    #echo "Artist #, Item #, Title, Number Sold, Item Price, Total" . "\n";
    #exit();
} else {
    $artid = $_GET['artid'];
}

$nameQuery = <<<EOS
select exhibitorName, exhibitorNumber 
from exhibitors e 
    join exhibitorYears eY on eY.exhibitorId=e.id 
    join exhibitorRegionYears eRY on eRY.exhibitorYearId = eY.id 
    join exhibitsRegionYears xRY on xRY.id = eRY.exhibitsRegionYearId 
    JOIN exhibitsRegions xR on xR.id=xRY.exhibitsRegion 
where eY.conid=58 and xR.regionType='Art Show' and exhibitorNumber=?;
EOS;

$nameR = fetch_safe_array(dbSafeQuery($nameQuery, 'i', array($artid)));
$name=$nameR[0];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="checkout_'.$name.'.csv"');

$query = <<<EOS
SELECT A.art_key, I.item_key, I.title, 
    CASE I.quantity < I.original_qty
        WHEN true THEN I.original_qty - I.quantity
        ELSE 1 
    END as number_sold,
    CASE I.type
        WHEN 'art' THEN I.final_price
        ELSE I.sale_price
    END as item_price
FROM artItems I
JOIN artshow A ON A.id=I.artshow
WHERE I.artshow = ? AND (I.quantity < I.original_qty OR I.final_price IS NOT null OR status='Sold Bid Sheet');
EOS;

//echo $query; exit();

echo "Artist #, Item #, Title, Number Sold, Item Price, Total"
    . "\n";

$reportR = dbSafeQuery($query, 'i', array($artid));
$total = 0;
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    printf("\"%s\"", $reportL[3]*$reportL[4] );
    $total = $total + ($reportL[3]*$reportL[4]);
    echo "\n";
}
echo ",,$name TOTAL,,,$total\n";

$query = <<<EOS
SELECT A.exhibitorNumber as art_key, I.item_key, I.title, I.quantity
FROM artItems I
JOIN exhibitorRegionYears A ON (A.id=I.exhibitorRegionYearId)
WHERE I.exhibitorRegionYearId = ? AND (
    (I.type = 'print' AND I.quantity >0)
    OR (I.type='art' AND I.status='Checked In')
    OR I.type='nfs'
);
EOS;

echo "\n\n\n";

echo "Artist #, Item #, Title, Number Returned" . "\n";
$reportR = dbSafeQuery($query, 'i', array($artid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    echo "\n";
}
echo ",,$name RETURNED\n";

//echo $query; exit();

?>
