<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET); //, "session" => $_SESSION);

$con = get_con();
$conid=$con['id'];
$perm='artinventory';
$check_auth = check_atcon($perm, $conid);
if($check_auth == false) {
    ajaxSuccess(array('error' => "Authentication Failure"));
}


$region = '';

if(!array_key_exists('region', $_GET)) {
    ajaxError("No Region");
} else {
    $region = $_GET['region'];
}

$locQ = <<<EOS
SELECT exhibitorNumber, eS.locations
FROM exhibitorRegionYears eRY
    JOIN exhibitorYears eY ON eY.id=eRY.exhibitorYearId
    JOIN exhibitorSpaces eS on eS.exhibitorRegionYear=eRY.id
    JOIN exhibitsRegionYears xRY on xRY.id=eRY.exhibitsRegionYearId
    JOIN exhibitsRegions xR on xR.id=xRY.exhibitsRegion
WHERE eY.conid=? and eRY.exhibitorNumber is not null and xR.shortName=?;
EOS;

$locR = dbSafeQuery($locQ, 'ii', array($conid, $region));

$locations = array();
while($loc = fetch_safe_assoc($locR)) {
    if(!array_key_exists($loc['exhibitorNumber'], $locations)) {
        $locations[$loc['exhibitorNumber']] = array();
    }
    $locations[$loc['exhibitorNumber']] = array_merge($locations[$loc['exhibitorNumber']], explode(',',$loc['locations']));
}

foreach($locations as $key => $value) {
    sort($value, SORT_NATURAL);
    $locations[$key] = $value;
}

$response['locations'] = $locations;

ajaxSuccess($response);
?>
