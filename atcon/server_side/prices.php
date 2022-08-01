<?php
require_once "lib/base.php";
require_once "lib/ajax_functions.php";
require_once "lib/db_functions.php";

$con = get_con();
$conid=$con['id'];

$response = array("post" => $_POST, "get" => $_GET);
$badge_res=dbQuery("SELECT concat_ws('-', id, memCategory, memType, memAge) as type, memAge, memCategory, label, price  FROM memList WHERE conid=$conid and atcon='Y' and current_timestamp() < enddate and current_timestamp() > startdate ORDER BY sort_order, memType, memAge ASC, price DESC;");
$badges=array();
while($row = fetch_safe_assoc($badge_res)) {
    $badges[count($badges)] = $row;
}
$response['badges'] = $badges;
 

ajaxSuccess($response);
?>
