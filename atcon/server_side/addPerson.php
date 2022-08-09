<?php
require_once "lib/base.php";

$perm="data_entry";
$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}


if(!isset($_POST)) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$query = <<<EOS
INSERT INTO newperson (last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, address, addr_2, city, state, zip, country)
VALUES (?, ?,?,?,?,?,?,?,?,?,?,?,?);
EOS;

$datatypes = 'sssssssssssss';
$values[] = blankifnotset($_POST['lname']);
$values[] = blankifnotset($_POST['fname']);
$values[] = blankifnotset($_POST['mname']);
$values[] = blankifnotset($_POST['suffix']);
$values[] = blankifnotset($_POST['email']);
$values[] = blankifnotset($_POST['phone']);
$values[] = nullifnotsetempty($_POST['badge']);
$values[] = blankifnotset($_POST['address']);
$values[] = blankifnotset($_POST['addr2']);
$values[] = blankifnotset($_POST['city']);
$values[] = blankifnotset($_POST['state']);
$values[] = blankifnotset($_POST['zip']);
$values[] = blankifnotset($_POST['country']);

$id = dbSafeInsert($query, $datatypes, $values);

$response['id'] = $id;

ajaxSuccess($response);
?>
