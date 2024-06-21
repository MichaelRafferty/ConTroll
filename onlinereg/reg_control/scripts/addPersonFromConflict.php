<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "search";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}


if(!isset($_POST) || !isset($_POST['newID'])) {
    $response['error'] = "No Data";
    ajaxSuccess($response);
    exit();
}

$newPersonQ = <<<EOQ
INSERT INTO perinfo (last_name, first_name, middle_name, suffix
    , email_addr, phone, badge_name, address, addr_2, city, state, zip
    , country, contact_ok, share_reg_ok, active, banned,updatedBy)
SELECT last_name, first_name, middle_name, suffix, email_addr, phone
    , badge_name, address, addr_2, city, state, zip, country, contact_ok, share_reg_ok, 'Y', 'N', ?
FROM newperson
WHERE id = ?;
EOQ;

$id = dbSafeInsert($newPersonQ, "ii", array($_POST['newID'], $_SESSION['user_id']));
$rows = dbSafeCmd("UPDATE newperson SET perid=?, updatedBy = ? WHERE id=?;", 'iii', array($id, $_SESSION['user_id'], $_POST['newID']));
$rows = dbSafeCmd("UPDATE reg SET perid=? WHERE newperid=?;", 'ii', array($id, $_POST['newID']));
$rows = dbSafeCmd("UPDATE transaction SET perid=? WHERE newperid=?;", 'ii', array($id, $_POST['newID']));
$rows = dbSafeCmd("UPDATE exhibitors SET perid=? WHERE newperid=?;", 'ii', array($id, $_POST['newID']));
$rows = dbSafeCmd("UPDATE memberInterests SET perid=? WHERE newperid=?;", 'ii', array($id, $_POST['newID']));
$rows = dbSafeCmd("UPDATE payorPlans SET perid=? WHERE newperid=?;", 'ii', array($id, $_POST['newID']));

$perQ = <<<EOQ
SELECT banned, concat_ws(' ', first_name, middle_name, last_name) as full_name, email_addr
    , address, addr_2, concat_ws(' ', city, state, zip) as locale, badge_name, id
FROM perinfo where id = ?;
EOQ;

$response['id'] = $id;
$response['results'] = dbSafeQuery($perQ, 'i', array($id))->fetch_assoc();

ajaxSuccess($response);
?>
