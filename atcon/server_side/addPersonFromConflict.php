<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$perm="data_entry";
$con = get_con();
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}


if(!isset($_POST) || !isset($_POST['newID'])) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$newPersonQ = "INSERT INTO perinfo (last_name, first_name, middle_name, suffix"
        . ", email_addr, phone, badge_name, address, addr_2, city, state, zip"
        . ", country)"
    . "SELECT last_name, first_name, middle_name, suffix, email_addr, phone"
        . ", badge_name, address, addr_2, city, state, zip, country"
    . " FROM newperson"
    . " WHERE id='" . sql_safe($_POST['newID']) . "';";


$id = dbInsert($newPersonQ);
$resolveInsert = "UPDATE newperson SET perid=$id WHERE id='"
    . sql_safe($_POST['newID']) . "';";
dbQuery($resolveInsert);

$perQ = "SELECT banned, concat_ws(' ', first_name, middle_name, last_name) as full_name, email_addr, address, addr_2, concat_ws(' ', city, state, zip) as locale, badge_name, id FROM perinfo where id = $id;";

$updateRegQ = "UPDATE reg SET perid='".$id . "'"
    . " WHERE " . "newperid='".sql_safe($_POST['newID']) . "';";

dbQuery($updateRegQ);

$updateTransQ = "UPDATE transaction SET perid='".$id . "'"
    . " WHERE newperid='".sql_safe($_POST['newID']) . "';";
dbQuery($updateTransQ);

$response['id'] = $id;
$response['results'] = fetch_safe_assoc(dbQuery($perQ));

ajaxSuccess($response);
?>
