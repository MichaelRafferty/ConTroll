<?php
require_once "../lib/base.php";
require_once '../lib/sessionAuth.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$perm = 'badge';
$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);
$authToken = new authToken('script');
$response['tokenStatus'] = $authToken->checkToken();
if (!$authToken->isLoggedIn() || !$authToken->checkAuth($perm)) {
    $response['error'] = 'Authentication Failed';
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

$user_perid = $authToken->getPerid();
$response['id'] = $authToken->getUserId();
$response['user_perid'] = $user_perid;

$perid = $_POST['perid'];
$memId = $_POST['memId'];
$regId = $_POST['regId'];

// chech to see if there already is a primary membership for this person
$iQ = <<<EOS
SELECT r.id, r.memId, r.status, m.memAge, m.memCategory, m.memType, m.price, m.conid
FROM reg r
JOIN memList m ON r.memId = m.id AND r.memId = m.id AND m.memType in ('full', 'oneday', 'virtual')
WHERE r.conid = ? AND perid = ? AND r.status IN ('paid', 'unpaid', 'plan')
EOS;
$typeStr = 'ii';
$values = array($conid, $perid);

$iR = dbSafeQuery($iQ, $typeStr, $values);
if ($iR === false) {
    $response['error'] = "Check to see if $perid already has a free membership failed, see log.";
    ajaxSuccess($response);
    exit();
}

$foundFreeId = false;
$numPrimary = 0;
$oldMemId = -1;
while ($row = $iR->fetch_assoc()) {
    if (isPrimary($row, $conid, 'all')) {
        $numPrimary++;
        if ($row['id'] == $regId) {
            $foundFreeId = true;
            $oldMemId = $row['memId'];
        }
    }
}
$iR->free();

if ($numPrimary > 0 && !$foundFreeId) {
    $response['warn'] = "$perid already has a membership.";
    ajaxSuccess($response);
    exit();
}

// check to see if this is a change or an add of a free membership
if ($foundFreeId) {
    $iR = <<<EOS
UPDATE reg
SET memId = ?
WHERE id = ? AND conid = ? AND perid = ? AND memId = ?
EOS;
    $numUpd = dbSafeCmd($iR, 'iiiii', array($memId, $regId, $conid, $perid, $oldMemId));
    if ($numUpd === false) {
        $response['error'] = "Update of registration $regId from $oldMemId to $memId for $perid failed, see log.";
        ajaxSuccess($response);
        exit();
    }
    $iNote = <<<EOS
INSERT INTO regActions(userid, source, tid, regid, action, notes)
VALUES (?, ?, ?, ?, ?, ?);
EOS;
    $numAdd = dbSafeInsert($iNote, 'isiiss', array($user_perid, 'freebadge', NULL, $regId, 'notes', "freebie registration changed in Free Badges from $oldMemId to $memId"));
} else {
    $iT = <<<EOS
INSERT INTO transaction(conid,perid,userid,price,tax,withtax,paid,type,create_date)
VALUES (?,?,?,0,0,0,0,'freebadge',NOW());
EOS;
    $dtT = 'iii';

    $iR = <<<EOS
INSERT INTO reg(conid,perid,memId,create_date,price,couponDiscount,paid,create_trans,complete_trans,create_user,updatedBy,status)
VALUES(?,?,?,NOW(),0,0,0,?,?,?,?,'paid');
EOS;
    $dtR = 'iiiiiii';

    $newTid = dbSafeInsert($iT, $dtT, array ($conid, $perid, $user_perid));
    if ($newTid === false) {
        $response['error'] = "Insert of transaction failed, see log.";
        ajaxSuccess($response);
        exit();
    }

    $newReg = dbSafeInsert($iR, $dtR, array ($conid, $perid, $memId, $newTid, $newTid, $user_perid, $user_perid));
    if ($newReg === false) {
        $response['error'] = 'Insert of membership failed, see log.';
        ajaxSuccess($response);
        exit();
    }
}
$response['success'] = "$perid updated with $memId";
ajaxSuccess($response);
