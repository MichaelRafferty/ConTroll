<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET, "session" => $_SESSION);


ajaxSuccess($response);
?>
