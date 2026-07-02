<?php
require_once '../../lib/paymentPlans.php';
// function to clean up when a user is marked as deceased

function cleanDeceasedUser($perid): string {
    $message = '';

    // things that need cleaning
    // 1. unmanaged everyone they manage

    $unCMD = <<<EOS
UPDATE perinfo
SET managedBy = NULL
WHERE managedBy = ?;
EOS;
    $del = dbSafeCmd($unCMD, 'i', array($perid));
    $message .= "$del matched users unmanaged from $perid<br/>\n";

    $unCMD = <<<EOS
UPDATE newperson
SET managedBy = NULL
WHERE managedBy = ?;
EOS;

    $del = dbSafeCmd($unCMD, 'i', array($perid));
    $message .= "$del unmatched users unmanaged from $perid<br/>\n";

    // 2. cancel payment plans held by this user
    $ppQ = <<<EOS
SELECT status, perid, id
FROM payorPlans
WHERE perid = ? AND status = 'active'; 
EOS;
    $ppR = dbSafeQuery($ppQ, 'i', array($perid));
    while ($pp = $ppR->fetch_assoc()) {
        $message .= cancelPaymentPlan($perid, $pp['id']);
    }
    $ppR->free();

    // 3. delete there controll user and auth table entries
    // 3a. get user id of this user
    // 3b. delete the user_auth entries
    // 3c. delete the user entry
    $uidQ = <<<EOS
SELECT id
FROM user
WHERE perid = ?;
EOS;
    $uidR = dbSafeQuery($uidQ, 'i', array($perid));
    if ($uidR === false) {
        $message .= "Error: Unable to retrieve possible back end user id for $perid";
    } else if ($uidR->num_rows > 0) {
        $uid = $uidR->fetch_row()[0];
        $uidR->free();
        $delAuth = <<<EOS
DELETE FROM user_auth WHERE user_id = ?;
EOS;
        $del = dbSafeCmd($delAuth, 'i', array($uid));
        $message .= "$del user auth entries deleted for user $uid ($perid)<br/>\n";
        $delUser = <<<EOS
DELETE FROM user WHERE id = ?;
EOS;
        $del = dbSafeCmd($delUser, 'i', array($uid));
        $message .= "$del user deleted for user $uid ($perid)<br/>\n";
    }

    // 4. delete any atcon_user and atcon_auth entries
    // 4a. get the atcon auth user id for this perid for this conid
    // 4b. delete the auth entries
    // 4c. delete the user entry
    $conid = getConfValue('con', 'id', '-1');
    $uidQ = <<<EOS
SELECT id
FROM atcon_user
WHERE perid = ? AND conid = ?;
EOS;
    $uidR = dbSafeQuery($uidQ, 'ii', array($perid, $conid));
    if ($uidR === false) {
        $message .= "Error: Unable to retrieve possible atcon user id for $perid";
    } else if ($uidR->num_rows > 0) {
        $uid = $uidR->fetch_row()[0];
        $uidR->free();
        $delAuth = <<<EOS
DELETE FROM atcon_auth WHERE authuser = ?;
EOS;
        $del = dbSafeCmd($delAuth, 'i', array ($uid));
        $message .= "$del atcon auth entries deleted for user $uid ($perid)<br/>\n";
        $delUser = <<<EOS
DELETE FROM atcon_user WHERE id = ? AND conid = ?;
EOS;
        $del = dbSafeCmd($delUser, 'ii', array ($uid, $conid));
        $message .= "$del atcon user deleted for user $uid ($perid) for conid $conid<br/>\n";
    }

    // 5. Delete from any watchlists
    $delWatch = <<<EOS
DELETE FROM badgeList WHERE perid = ? AND conid = ?;
EOS;
    $del = dbSafeCmd($delWatch, 'ii', array ($perid, $conid));
    $message .= "$del badgeList (watch list) entries deleted for user $perid<br/>\n";

   return $message;
}
