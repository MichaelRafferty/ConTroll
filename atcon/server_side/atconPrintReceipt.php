<?php
require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$response = array("post" => $_POST, "get" => $_GET);


ajaxSuccess($response);
?>
