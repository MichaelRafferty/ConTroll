<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "badge";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}


// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$conid = $con['id'];
$action = '';
if ($_POST && $_POST['action']) {
    $action = $_POST['action'];
}
if ($action != 'updateMembership') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$user_perid = $_SESSION['user_perid'];
$response['id'] = $_SESSION['user_id'];
$response['user_perid'] = $user_perid;

$perid = $_POST['perid'];
$memId = $_POST['memId'];

// chech to see if there already is a free membership for this person
$iQ = <<<EOS
SELECT COUNT(*) mem FROM reg
WHERE conid = ? AND perid = ?;
EOS;
$typeStr = 'ii';
$values = array($conid, $perid);

$iR = dbSafeQuery($iQ, $typeStr, $values);
if ($iR === false) {
    $response['error'] = "Check to see if $perid already has a free membership failed, see log.";
    ajaxSuccess($response);
    exit();
}

$numRows = $iR->fetch_row()[0];
if ($numRows > 0) {
    $response['warn'] = "$perid already has a free membership.";
    ajaxSuccess($response);
    exit();
}

$iT = <<<EOS
INSERT INTO transaction(conid,perid,userid,price,tax,withtax,paid,type,create_date)
VALUES (?,?,?,0,0,0,0,'freebadge',now());
EOS;
$dtT = 'iii';

$iR = <<<EOS
INSERT INTO reg(conid,perid,memId,create_date,price,couponDiscount,paid,create_trans,complete_trans,create_user,updatedBy,status)
VALUES(?,?,?,NOW(),0,0,0,?,?,?,?,'paid');
EOS;
$dtR = 'iiiiiii';

$newTid = dbSafeInsert($iT, $dtT, array($conid, $perid, $user_perid));
if ($newTid === false) {
    $response['error'] = "Insert of transaction failed, see log.";
    ajaxSuccess($response);
    exit();
}

$newReg = dbSafeInsert($iR, $dtR, array($conid, $perid, $memId, $newTid, $newTid, $user_perid, $user_perid));
if ($newReg === false) {
    $response['error'] = 'Insert of membership failed, see log.';
    ajaxSuccess($response);
    exit();
}
$response['success'] = "$perid update with $memId";
ajaxSuccess($response);
?>
