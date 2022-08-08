<?php
require_once "lib/base.php";

$response = array("post" => $_POST, "get" => $_GET);

$con = $_POST['con'];

$query = "SELECT memAge, label, price FROM memList WHERE conid=? AND memCategory='yearahead';";
$agePricesR = dbSafeQuery($query, 'i', array($con+1));
$ageList = array();
while($ageItem = fetch_safe_assoc($agePricesR)) {
    array_push($ageList, $ageItem);
}

ajaxSuccess($ageList);
?>
