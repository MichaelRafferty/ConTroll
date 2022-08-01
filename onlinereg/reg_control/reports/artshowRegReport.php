<?php
require_once "../lib/base.php";
$need_login = google_init("page");
$page = "artshow";

if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}


$con = get_conf("con");
$conid=$con['id'];

header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="artshowReport.csv"');

$query = "SELECT S.art_key, concat_ws(',', S.a_panel_list, S.p_panel_list, S.a_table_list) as loc, V.name"
    . ", S.a_panels, S.a_tables, S.p_panels"
    . ", count(I.id) as itemCount"
    . ", V.email, P.phone, V.website"
    . ", CASE WHEN V.publicity THEN 'Yes' ELSE 'No' END"
    . ", concat_ws(', ', A.ship_addr, A.ship_addr2, A.ship_city, A.ship_state, A.ship_zip) as address"
    . ", S.agent_request, S.description, S.artid"
    //. ", S.agent_request, G.phone"
    . " FROM artshow as S"
        . " JOIN artist as A on A.id=S.artid"
        . " JOIN perinfo as P on P.id=A.artist"
        . " JOIN vendors as V on V.id=A.vendor"
        . " LEFT JOIN artItems as I on I.artshow=S.id"
        //. " LEFT JOIN perinfo as G on G.id=S.agent"
    . " WHERE S.conid=53"
    . " GROUP BY art_key"
    . " ORDER BY art_key"
    . ";";


echo "Artist #, Locs, Artist Name, Panels, Tables, Printshop, Items, Email, Artist Phone, Website, Publicity OK?, Address, Agent, Description, Artid, Date Registered"
    . "\n";

$reportR = dbQuery($query);
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < 3; $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    $w = floor($reportL[3] / 3);
    $f = $reportL[3] % 3;
    if($w > 0) {
        if($f > 0) {
            echo "\"$w $f/3\",";
        } else { echo "\"$w\","; }
    } else if ($f > 0) {
        echo "\" $f/3\",";
    } else { echo ","; }

    $w = floor($reportL[4] / 4);
    $f = $reportL[4] % 4;
    if($w > 0) {
        if($f > 0) {
            echo "\"$w $f/4\",";
        } else { echo "\"$w\","; }
    } else if ($f > 0) {
        echo "\" $f/4\",";
    } else { echo ","; }

    $w = floor($reportL[5] / 3);
    $f = $reportL[5] % 3;
    if($w > 0) {
        if($f > 0) {
            echo "\"$w $f/3\",";
        } else { echo "\"$w\","; }
    } else if ($f > 0) {
        echo "\" $f/3\",";
    } else { echo ","; }

    for($i = 6 ; $i < count($reportL); $i++) {
        printf("\"%s\",", $reportL[$i]);
    }
    echo "\n";
}

?>
