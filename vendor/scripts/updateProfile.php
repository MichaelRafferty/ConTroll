<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);
$vendor = 0;

if(isset($_SESSION['id'])) {
    $vendor = $_SESSION['id'];
} else {
    $response['status']='error';
    $response['message']='Authentication Failure';
    ajaxSuccess($response);
    exit();
}

$updateQ = "UPDATE vendors SET website='"
    . sql_safe($_POST['website']) . "'"
    . ", description='"
    . sql_safe($_POST['description']) . "'"
    . " WHERE id=$vendor";
dbQuery($updateQ);

ajaxSuccess($response);
?>
