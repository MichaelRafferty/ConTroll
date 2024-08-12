<?php
global $db_ini;

require_once "../lib/base.php";
$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('rid', $_POST)) {
    $regid = $_POST['rid'];
    $nQ = <<<EOS
SELECT logdate, userid, tid, notes
FROM regActions
WHERE regid = ?;
EOS;
    $nR = dbSafeQuery($nQ, 'i', array($regid));
    if ($nR === false) {
        $response['error'] = 'Error retrieving notes records';
        ajaxSuccess($response);
        exit();
    }
    $notes = [];
    while ($note = $nR->fetch_assoc()) {
        $notes[] = $note;
    }
    $nR->free();
    $response['notes'] = $notes;
} else {
    $response['error'] = 'Improper calling sequence';
}

ajaxSuccess($response);
exit();
