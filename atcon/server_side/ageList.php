<?php
require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$response = array("post" => $_POST, "get" => $_GET);

$con = sql_safe($_POST['con']);

$query = "SELECT memAge, label, price FROM memList WHERE conid='".((int)$con+1)."' AND memCategory='yearahead';";
$agePricesR = dbQuery($query);
$ageList = array();
while($ageItem = fetch_safe_assoc($agePricesR)) {
    array_push($ageList, $ageItem);
}

ajaxSuccess($ageList);
?>
