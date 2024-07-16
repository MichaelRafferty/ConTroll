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

// check if this person is managed by a different new person
$checkQ = <<<EOS
SELECT managedByNew
FROM newperson
WHERE id = ?;
EOS;
$checkR = dbSafeQuery($checkQ,'i', array($_POST['newID']));
if ($checkR === false) {
    $response['error'] = "Cannot check new person for who manages them";
    ajaxSuccess($response);
    exit();
}
$manager = $checkR->fetch_row()[0];
$managerPerid = null;
$checkR->free();
if ($manager != NULL) {
    // check if that manager has been resolved
    $checkQ = <<<EOS
SELECT perid
FROM newperson
WHERE id = ?;
EOS;

    $checkR = dbSafeQuery($checkQ,'i', array($manager));
    $managerPerid = $checkR->fetch_row()[0];
    if ($managerPerid == NULL) {
        $response['error'] = "Must first resolve their manager, newid $manager";
        ajaxSuccess($response);
        exit();
    }
}

$newPersonQ = <<<EOQ
INSERT INTO perinfo (last_name, first_name, middle_name, suffix, legalName, pronouns
    , email_addr, phone, badge_name, address, addr_2, city, state, zip
    , country, contact_ok, share_reg_ok, active, banned, updatedBy
    , managedBy, managedReason, lastVerified)
SELECT last_name, first_name, middle_name, suffix, legalName, pronouns
    , email_addr, phone, badge_name, address, addr_2, city, state, zip
    , country, contact_ok, share_reg_ok, 'Y', 'N', ?
    , ?, managedReason, lastVerified
FROM newperson
WHERE id = ?;
EOQ;

$id = dbSafeInsert($newPersonQ, "iii", array($_SESSION['user_perid'], $managerPerid, $_POST['newID']));
if ($id !== false) {
    $rows = dbSafeCmd("UPDATE newperson SET perid=?, updatedBy = ? WHERE id=?;", 'iii', array ($id, $_SESSION['user_id'], $_POST['newID']));
    $rows = dbSafeCmd("UPDATE newperson SET updatedBy = ?, managedBy = ?, managedByNew = null WHERE managedByNew=?;", 'iii',
                      array ($_SESSION['user_perid'], $id, $_POST['newID']));
    $rows = dbSafeCmd("UPDATE reg SET perid=? WHERE newperid=?;", 'ii', array ($id, $_POST['newID']));
    $rows = dbSafeCmd("UPDATE transaction SET perid=? WHERE newperid=?;", 'ii', array ($id, $_POST['newID']));
    $rows = dbSafeCmd("UPDATE exhibitors SET perid=? WHERE newperid=?;", 'ii', array ($id, $_POST['newID']));
    $rows = dbSafeCmd("UPDATE memberInterests SET perid=? WHERE newperid=?;", 'ii', array ($id, $_POST['newID']));
    $rows = dbSafeCmd("UPDATE payorPlans SET perid=? WHERE newperid=?;", 'ii', array ($id, $_POST['newID']));

    $perQ = <<<EOQ
SELECT banned, CONCAT_WS(' ', first_name, middle_name, last_name) AS full_name, email_addr
    , address, addr_2, CONCAT_WS(' ', city, state, zip) AS locale, badge_name, id
FROM perinfo WHERE id = ?;
EOQ;

    $response['id'] = $id;
    $response['results'] = dbSafeQuery($perQ, 'i', array ($id))->fetch_assoc();
} else {
    $response['error'] = "Insert Error";
}

ajaxSuccess($response);
?>
