<?php
require_once "../../lib/base.php";

$check_auth = google_init('ajax');
$perm = "art_control";
$response = array ('perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if ((!array_key_exists('postVars', $_POST)) || (!array_key_exists('report', $_POST)) || (!array_key_exists('group', $_POST))
    || (!array_key_exists('prefix', $_POST)) || $_POST['action'] != 'fetch') {
    $response['error'] = 'Invalid Arguments';
    ajaxSuccess($response);
    exit();
}

$con = get_conf("con");
$conid=$con['id'];

$group = $_POST['group'];
$reportName = $_POST['report'];
$prefix = $_POST['prefix'];
$groupParams = parse_ini_file(__DIR__ . "/../../reports/$group", true);
$hdrAuth = $groupParams['group']['auth'];
$report = $groupParams[$reportName];

$response['hdrAuth'] = $hdrAuth;
$response['reportName'] = $reportName;
$response['prefix'] = $prefix;
$response['report'] = $report;

$postVars = $_POST['postVars'];
if (array_key_exists('artid', $postVars)) {
    $artid = $postVars['artid'];
} else {
    $artid = 100;
}

$nameQuery = <<<EOS
select exhibitorName, exhibitorNumber 
from exhibitors e 
    join exhibitorYears eY on eY.exhibitorId=e.id 
    join exhibitorRegionYears eRY on eRY.exhibitorYearId = eY.id 
    join exhibitsRegionYears xRY on xRY.id = eRY.exhibitsRegionYearId 
    JOIN exhibitsRegions xR on xR.id=xRY.exhibitsRegion 
where eY.conid=? and xR.regionType='artshow' and exhibitorNumber=?;
EOS;
$nameR = dbSafeQuery($nameQuery, 'ii', array($conid, $artid));
if ($nameR === false) {
    ajaxSuccess(array('status' => 'error', 'message' => 'Error in artist query, get help'));
    exit();
}
if ($nameR->num_rows == 0) {
    ajaxSuccess(array('status' => 'error', 'message' => "Artist $artid not found"));
    exit();
}

$name = $nameR->fetch_row()[0];
$nameR->free();

$query = <<<EOS
SELECT E.exhibitorNumber, I.item_key, I.title, 
    CASE I.quantity < I.original_qty
        WHEN true THEN I.original_qty - I.quantity
        ELSE 1 
    END as number_sold,
    CASE I.type
        WHEN 'art' THEN I.final_price
        ELSE I.sale_price
    END as item_price
FROM artItems I
JOIN exhibitorRegionYears E ON (E.id=I.exhibitorRegionYearId)
JOIN artSales A ON A.artid=I.id
WHERE E.exhibitorNumber = ? AND (I.quantity < I.original_qty OR I.final_price IS NOT null OR I.status='Sold Bid Sheet');
EOS;

$output = '';
//echo $query; exit();

$output .= "Artist #, Item #, Title, Number Sold, Item Price, Total\n";

$reportR = dbSafeQuery($query, 'i', array($artid));
$total = 0;
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        $output .= sprintf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    $output .= sprintf("\"%s\"", $reportL[3]*$reportL[4] );
    $total = $total + ($reportL[3]*$reportL[4]);
    $output .= "\n";
}
$output .= ",,$name TOTAL,,,$total\n";

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

$output .= "\n\n\n";

$output .= "Artist #, Item #, Title, Number Returned" . "\n";
$reportR = dbSafeQuery($query, 'i', array($artid));
while($reportL = fetch_safe_array($reportR)) {
    for($i = 0 ; $i < count($reportL); $i++) {
        sprintf("\"%s\",", html_entity_decode($reportL[$i], ENT_QUOTES | ENT_HTML401));
    }
    $output .= "\n";
}
$output .= ",,$name RETURNED\n";

$output = str_replace("\n", "<br/>", $output);

//echo $query; exit();
$response['output'] = $output;
$response['status'] = 'success';
$response['meesage'] = 'Report Complete';
ajaxSuccess($response);