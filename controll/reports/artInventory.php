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
header('Content-Disposition: attachment; filename="inventory.csv"');

$query = "SELECT"
    . " i.location, a.art_key, i.item_key, i.title, i.status, i.quantity"
    . ", DATE_SUB(i.time_updated, INTERVAL 4 HOUR) as time_updated"
    . " FROM artItems AS i JOIN artshow AS a ON a.id=i.artshow"
    . " WHERE i.conid=$conid"
    . " ORDER BY i.location, a.art_key, i.item_key;";

echo "Location, Artist #, Item #, Title, Status, Quantity, Time Changed"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}
