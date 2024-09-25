<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../../lib/policies.php";
require_once "../../lib/interests.php";

$check_auth = google_init("ajax");
$perm = "people";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('perid', $_POST)) && array_key_exists('action', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$action = $_POST['action'];
$perid = $_POST['perid'];
$updatedBy = $_SESSION['user_perid'];
if ($action != 'saveedit' || $perid == null || is_numeric($perid) == false || $perid <= 0) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conid=$con['id'];

// this updates the database for a people screen edit of a person
// it has several sections...
//  1. profile
//  2. policies
//  3. interests
//  4. manages

//  1. Profile, update the perinfo record

$last_name = $_POST['lastName'] == null ? '' : trim($_POST['lastName']);
$first_name = $_POST['firstName'] == null ? '' : trim($_POST['firstName']);
$middle_name = $_POST['middleName'] == null ? '' : trim($_POST['middleName']);
$suffix = $_POST['suffix'] == null ? '' : trim($_POST['suffix']);
$legalName = $_POST['legalName'] == null ? '' : trim($_POST['legalName']);
$pronouns = $_POST['pronouns'] == null ? '' : trim($_POST['pronouns']);
$badge_name = $_POST['badgeName'] == null ? '' : trim($_POST['badgeName']);
$address = $_POST['address'] == null ? '' : trim($_POST['address']);
$addr_2 = $_POST['addr2'] == null ? '' : trim($_POST['addr2']);
$city = $_POST['city'] == null ? '' : trim($_POST['city']);
$state = $_POST['state'] == null ? '' : trim($_POST['state']);
$zip = $_POST['zip'] == null ? '' : trim($_POST['zip']);
$country = $_POST['country'] == null ? '' : trim($_POST['country']);
$email_addr = $_POST['emailAddr'] == null ? '' : trim($_POST['emailAddr']);
$phone = $_POST['phone'] == null ? '' : trim($_POST['phone']);
$managedBy = $_POST['managerId'] == '' ? null : trim($_POST['managerId']);
$active = $_POST['active'] == null ? 'Y' : trim($_POST['active']);
$banned = $_POST['banned'] == null ? 'N' : trim($_POST['banned']);
$admin_notes = $_POST['adminNotes'] == null ? '' : trim($_POST['adminNotes']);
$open_notes = $_POST['openNotes'] == null ? '' : trim($_POST['openNotes']);

$uP = <<<EOS
UPDATE perinfo
SET last_name = ?, first_name = ?, middle_name = ?, suffix = ?, email_addr = ?, phone = ?, badge_name = ?, legalName = ?, pronouns = ?,
    address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, banned = ?,
    active = ?, open_notes = ?, admin_notes = ?, managedBy = ?, updatedBy = ?, 
    managedByNew = NULL, lastVerified = NULL, update_date = NOW(), change_notes = CONCAT(change_notes, '<br/>Updated by People Edit screen')
WHERE id = ?;
EOS;

$typeStr = 'sssssssssssssssssssiii';
$valArray = array($last_name, $first_name, $middle_name, $suffix, $email_addr, $phone, $badge_name, $legalName, $pronouns, $address, $addr_2,
    $city, $state, $zip, $country, $banned, $active, $open_notes, $admin_notes, $managedBy, $updatedBy, $perid);

$upd = dbSafeCmd($uP, $typeStr, $valArray);
if ($upd === false) {
    $response['error'] = 'Error updating Perinfo Record';
    ajaxSuccess($response);
    return;
}

$message = 'Perinfo Record Updated';

//  2. Policies

$policy_upd =  updateMemberPolicies($conid, $perid, 'p', $updatedBy, 'p');
if ($policy_upd > 0) {
    $message .= "<br/>$policy_upd policy responses updated";
}

//  3. interests
$interest_upd =  updateMemberInterests($conid, $perid, 'p', $updatedBy, 'p');
if ($interest_upd > 0) {
    $message .= "<br/>$interest_upd interest responses updated";
}

//  4. manages
    // TODO

    $response['success'] = $message;
    ajaxSuccess($response);
?>
