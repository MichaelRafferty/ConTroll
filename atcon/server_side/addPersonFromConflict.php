<?php
require_once "lib/base.php";

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

$newPersonQ = <<<EOS
INSERT INTO perinfo (last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, address, addr_2, city, state, zip, country)
SELECT last_name, first_name, middle_name, suffix, email_addr, phone, badge_name, address, addr_2, city, state, zip, country
FROM newperson
WHERE id=?;
EOS;

$id = dbSafeInsert($newPersonQ, 'i', array($_POST['newID']));
$resolveInsert = "UPDATE newperson SET perid=? WHERE id=?";
dbSafeCmd($resolveInsert, 'ii', array($id, $_POST['newID']));

$perQ = <<<EOS
SELECT banned, concat_ws(' ', first_name, middle_name, last_name) as full_name, email_addr, address, addr_2,
    concat_ws(' ', city, state, zip) as locale, badge_name, id
FROM perinfo
WHERE id = ?;
EOS;

$updateRegQ = "UPDATE reg SET perid=? WHERE newperid=?;";
dbSafeCmd($updateRegQ, 'ii', array($id, $_POST['newID']));

$updateTransQ = "UPDATE transaction SET perid=? WHERE newperid=?;";
dbSafeCmd($updateTransQ, 'ii', array($id, $_POST['newID']));

$response['id'] = $id;
$response['results'] = fetch_safe_assoc(dbSafeQuery($perQ, 'i', array($id)));

ajaxSuccess($response);
?>