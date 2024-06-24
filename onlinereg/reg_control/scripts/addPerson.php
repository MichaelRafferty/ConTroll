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

$query = <<<EOS
INSERT INTO newperson (last_name, first_name, middle_name, suffix, email_addr, phone, legalName, badge_name,
    address, addr_2, city, state, zip, country, share_reg_ok, contact_ok,updatedBy)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

$parmtypes = 'ssssssssssssssss';
$last_name = "";
if (array_key_exists('lname', $_POST)) {
    $last_name = trim($_POST['lname']);
    if (is_null($last_name))
        $last_name = "";
}
$first_name = "";
if (array_key_exists('fname', $_POST)) {
    $first_name = trim($_POST['fname']);
    if (is_null($first_name))
        $first_name = "";
}
$middle_name = "";
if (array_key_exists('mname', $_POST)) {
    $middle_name = trim($_POST['mname']);
    if (is_null($middle_name))
        $middle_name = "";
}
$suffix = "";
if (array_key_exists('suffix', $_POST)) {
    $suffix = trim($_POST['suffix']);
    if (is_null($suffix))
        $suffix = "";
}
$email = "";
if (array_key_exists('email', $_POST)) {
    $email = trim($_POST['email']);
    if (is_null($email))
        $email = "";
}
$phone = "";
if (array_key_exists('phone', $_POST)) {
    $phone = trim($_POST['phone']);
    if (is_null($phone))
        $phone = "";
}
$badge = "";
if (array_key_exists('badge', $_POST)) {
    $badge = trim($_POST['badge']);
    if (is_null($badge))
        $badge = "";
}
$legalName = '';
if (array_key_exists('legalName', $_POST)) {
    $legalName = trim($_POST['legalName']);
    if (is_null($legalName))
        $legalName = '';
}
$address = "";
if (array_key_exists('address', $_POST)) {
    $address = trim($_POST['address']);
    if (is_null($address))
        $address = "";
}
$addr2 = "";
if (array_key_exists('addr2', $_POST)) {
    $addr2 = trim($_POST['addr2']);
    if (is_null($addr2))
        $addr2 = "";
}
$city = "";
if (array_key_exists('city', $_POST)) {
    $city = trim($_POST['city']);
    if (is_null($city))
        $city = "";
}
$state = "";
if (array_key_exists('state', $_POST)) {
    $state = trim($_POST['state']);
    if (is_null($state))
        $state = "";
}
$zip = "";
if (array_key_exists('zip', $_POST)) {
    $zip = trim($_POST['zip']);
    if (is_null($zip))
        $zip = "";
}
$country = "";
if (array_key_exists('country', $_POST)) {
    $country = trim($_POST['country']);
    if (is_null($country))
        $country = "";
}
$share_ok = 'Y';
if (array_key_exists('share_reg', $_POST)) {
    $share_ok = trim($_POST['share_reg']);
    if (is_null($share_ok))
        $share_ok = "Y";
}
$contact_ok = 'Y';
if (array_key_exists('contact_ok', $_POST)) {
    $contact_ok = trim($_POST['contact_ok']);
    if (is_null($contact_ok))
        $contact_ok = "Y";
}


$values = array($last_name, $first_name, $middle_name, $suffix, $email, $phone, $legalName, $badge, $address, $addr2, $city, $state, $zip, $country, $share_ok, $contact_ok, $_SESSION['user_id']);

$id = dbSafeInsert($query, $parmtypes, $values);

$response['id'] = $id;

ajaxSuccess($response);
?>
