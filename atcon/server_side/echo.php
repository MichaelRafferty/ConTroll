<?php
require_once "lib/base.php";

$response = array("post" => $_POST, "get" => $_GET);


ajaxSuccess($response);
?>
