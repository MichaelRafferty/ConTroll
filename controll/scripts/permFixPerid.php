<?php
global $db_ini;
require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('perid', $_POST) && array_key_exists('userid', $_POST))) {
    ajaxError("Calling Sequence Error");
    exit();
}
$perid = $_POST['perid'];
$userid = $_POST['userid'];

// check if perid valid
$checkQ = <<<EOS
SELECT COUNT(*) FROM perinfo where id = ?;
EOS;
$checkR = dbSafeQuery($checkQ, 'i', array($perid));
$checkL = $checkR->fetch_row();
$count = $checkL[0];

if ($count != 1) {
    $response['error'] = "Person doesn't exist";
    ajaxSuccess($response);
    exit();
}

// check if the user exists already in the user list
$checkQ = <<<EOS
SELECT COUNT(*) FROM user where perid = ?;
EOS;
$checkR = dbSafeQuery($checkQ, 'i', array($perid));
$checkL = $checkR->fetch_row();
$count = $checkL[0];

if ($count > 0) {
    $response['error'] = "User already exists with that perid";
    ajaxSuccess($response);
    exit();
}

$updateQ = <<<EOS
UPDATE user
SET perid = ?
WHERE id = ?
EOS;

$numrows = dbSafeCmd($updateQ, 'ii', array($perid, $userid));
if ($numrows == 1) {
    $response['success'] = "User $userid updated for perid $perid";
} else {
    $response['error'] = "Error updating user $userid";
}

ajaxSuccess($response);
?>
