<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET, "session" => $_SESSION);

$con = get_con();
$conid=$con['id'];
$perm='artinventory';
$check_auth = check_atcon($perm, $conid);
if($check_auth == false) {
    ajaxSuccess(array('error' => "Authentication Failure"));
}


$locQ = <<<EOS
SELECT art_key, a_panel_list, p_panel_list, a_table_list
FROM artshow
WHERE conid=?;
EOS;
$locR = dbSafeQuery($locQ, 'i', array($conid));

$locations = array();
while($loc = fetch_safe_assoc($locR)) {
    $locations[$loc['art_key']] = array_merge(
        explode(',', $loc['a_panel_list']),
        explode(',', $loc['p_panel_list']),
        explode(',', $loc['a_table_list']),
    ); 
}

ajaxSuccess($locations);
?>
