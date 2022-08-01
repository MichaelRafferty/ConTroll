<?php
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "search";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$query = "INSERT INTO newperson (last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, address, addr_2, city, state, zip, country) VALUES (";


$query .= "'" . sql_safe($_POST['lname']) . "', '" .
    sql_safe($_POST['fname']) . "', '" .
    sql_safe($_POST['mname']) . "', '" .
    sql_safe($_POST['suffix']) . "', ";
$query .= "'" . sql_safe($_POST['email']) . "', '" .
    sql_safe($_POST['phone']) . "', ";
if(isset($_POST['badge']) && $_POST['badge'] != '') {
  $query .= "'" . sql_safe($_POST['badge']) . "', ";
} else {
  $query .= "NULL, ";
}
$query .= "'" . sql_safe($_POST['address']) . "', '" .
    sql_safe($_POST['addr2']) . "', '" .
    sql_safe($_POST['city']) . "', '" .
    sql_safe($_POST['state']) . "', '" .
    sql_safe($_POST['zip']) . "', '" .
    sql_safe($_POST['country']) . "');";

$id = dbInsert($query);

$response['id'] = $id;

ajaxSuccess($response);
?>
