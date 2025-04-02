<?php
global $db_ini;

require_once '../lib/base.php';

$check_auth = google_init('ajax');
$perm = 'reg_admin';

$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ( $check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('user_perid', $_SESSION)) {
    $user_perid = $_SESSION['user_perid'];
}
else {
    ajaxError('Invalid credentials passed');
    return;
}

if (!isset($_POST) || !isset($_POST['rolloverList']) || !isset($_POST['action']) || $_POST['action'] != 'rollover') {
    $response['error'] = 'Invalid Parameters';
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid = $con['id'];
$nextcon = $conid + 1;

$rolloverList = $_POST['rolloverList'];

$validateSQL = <<<EOS
WITH prints AS (
SELECT count(h.action) AS printCnt
FROM reg r
LEFT OUTER JOIN regActions h ON h.regid = r.id AND h.action = 'print'
WHERE r.id = ?
)
SELECT DISTINCT r.id, r.perid, r.price, r.couponDiscount, r.paid, r.status, r.memId, m.label, m.memAge, m.memCategory, m.memType,
                m.glNum, m.glLabel,
                r1.id AS nextid, r1.status as nextstatus, m1.label as nextlabel, h.printCnt, p.first_name, p.last_name
FROM reg r
JOIN memList m ON (r.memId = m.id)
JOIN perinfo p ON (r.perid = p.id)
JOIN prints h
LEFT OUTER JOIN reg r1 ON (r1.conid = ? AND r.perid = r1.perid AND r1.status IN ('paid', 'unpaid', 'plan', 'upgraded'))
LEFT OUTER JOIN memList m1 ON (r1.memId = m1.id)
WHERE r.conid = ? AND r.id = ?; 
EOS;

$matchMem = <<<EOS
SELECT CASE WHEN m.price = ? THEN 1 ELSE 999 END AS priceMatch, m.price, m.id, glNum glLabel
FROM memList m
WHERE m.memCategory = ? AND m.memType = ? AND m.memAge = ? AND m.label = ? AND m.conid = ?
ORDER BY 1,2,3;
EOS;

$newMemI = <<<EOS
INSERT INTO memList(conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online, glNum, glLabel)
VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;

foreach ($rolloverList as $badgeId => $rollover) {
    $result = dbSafeQuery($validateSQL, 'iiii', array($badgeId, $nextcon, $conid, $badgeId));
    if ($result->num_rows < 1) {
        $response['error'] = "Error: Issue retrieving registrations for this person with badge id $badgeId";
        ajaxSuccess($response);
        return false;
    }
    $membership = $result->fetch_assoc();
    $result->free();
    $perid = $membership['perid'];
    $label = $membership['label'];
    $status = $membership['status'];
    $nextlabel = $membership['nextlabel'];
    $nextstatus = $membership['nextstatus'];
    $first_name = $membership['first_name'];
    $last_name = $membership['last_name'];
    $printCnt = $membership['printCnt'];
    $memType = $membership['memType'];
    $memId = $membership['memId'];
    $memAge = $membership['memAge'];
    $memCategory = $membership['memCategory'];
    $price = $membership['price'];
    $startdate = $membership['startdate'];
    $glNum = $membership['glNum'];
    $glLabel = $membership['glLabel'];

    // handle common case that membership cannot exist already for next con
    if ($membership['nextid'] != null) {
        $response['error'] = "Cannot rollover $badgeId ($perid: $first_name $last_name) of type $label as it already has a membership of type $nextlabel of status $nextstatus for $nextcon";
        ajaxSuccess($response);
        return false;
    }

    if ($status != 'paid' && $status != 'upgraded') {
        $response['error'] = "Cannot rollover $badgeId ($perid: $first_name $last_name) of type $label as its status is $status";
        ajaxSuccess($response);
        return false;
    }

    if ($memType == 'oneday') {
        $response['error'] = "Cannot rollover $badgeId ($perid: $first_name $last_name) as it is a one-day membership";
        ajaxSuccess($response);
        return false;
    }
    if ($rollover['override'] == 'N' && $membership['printCnt'] > 0) {
        $response['error'] = "Cannot rollover $badgeId ($perid: $first_name $last_name) as it was already printed";
        ajaxSuccess($response);
        return false;
    }
    // check if 'AUTO' and if so, see if it exists or it needs to be created
    if ($rollover['newid'] == 'auto') {
        // try and fetch a matching memId (first exact match)
        $matchR = dbSafeQuery($matchMem, 'dssssi', array($price, $memCategory, $memType, $memAge, $label, $nextcon));
        if ($matchR === false) {
            $response['error'] = "Cannot rollover $badgeId ($perid: $first_name $last_name) due to auto create query failure";
            ajaxSuccess($response);
            return false;
        }
        if ($matchR->num_rows > 0) {
            // a match is found, the matching price is the best choice, but if not, it will use the lowest price/memId
            $matchL = $matchR->fetch_assoc();
            $rolloverList[$badgeId]['newid'] = $matchL['id'];
            $matchR->free();
        } else {
            $matchR->free();
            // none found, create one
            //conid, sort_order, memCategory, memType, memAge, label, notes, price, startdate, enddate, atcon, online)
            $nextYear = substr(startEndDateToNextYear($startdate), 0, 4);
            $newId = dbSafeInsert($newMemI, 'iisssssdssssss', array($nextcon, 999999, $memCategory, $memType, $memAge, $label,
                "Auto created by rollover", $price, $nextYear . "/01/01 00:00", $nextYear . '/01/01 00:00', 'N', 'N', $glNum, $glLabel));
            if ($newId === false) {
                $response['error'] = "Cannot rollover $badgeId ($perid: $first_name $last_name) due to auto create insert failure";
                ajaxSuccess($response);
                return false;
            }
            $rolloverList[$badgeId]['newid'] = $newId;
        }
    }
}
// ok the checks are all done, now do the work
// insert a controlling transaction to cover this rollover
$tType = 'regctl-adm-roll/' . $user_perid;
$notes = "Rollover from $conid to $nextcon by $user_perid";
$insertT = <<<EOS
INSERT INTO transaction(conid, perid, userid, create_date, complete_date, price, couponDiscountCart, CouponDiscountReg, paid, type, notes) 
VALUES (?, ?, ?, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), 0, 0, 0, 0, ?, ?);
EOS;
$newtid = dbSafeInsert($insertT, 'iiiss', array($conid, $perid, $user_perid, $tType, $notes));
if ($newtid === false) {
    $response['error'] = 'Failed to insert rollover transaction';
    ajaxSuccess($response);
    return;
}

        
// now insert the new reg for the next year
// TODO: add reg chain for tracking or consider status rollover

$upgsql = <<<EOS
UPDATE reg
SET reg.status = 'rolled-over', updatedBy = ?
WHERE reg.id = ? AND reg.conid = ?;
EOS;
$insSql = <<<EOS
INSERT INTO reg(conid, perid, create_date, price, couponDiscount, paid, status, memId, create_trans, complete_trans, priorRegId, create_user)
VALUES(?, ?, CURRENT_TIMESTAMP, 0, 0, 0, 'paid', ?, ?, ?, ?, ?);
EOS;
$insNoteSQL = <<<EOS
insert into regActions(logdate, userid, tid, regid, action,notes)
values(now(), ?,?,?,'notes', ?);
EOS;

foreach ($rolloverList as $basdgeId => $rollover) {
    // mark prior membership as rolled-over
    $newMemId = $rollover['newid'];

    // update old reg as rolled over
    $numrows = dbSafeCmd($upgsql, 'iii', array ($user_perid, $badgeId, $conid));
    if ($numrows != 1) {
        $response['error'] = "Failed altering rollover $badgeId ($perid: $first_name $last_name) to rolled-over status";
        ajaxSuccess($response);
        return false;
    }

    // insert new reg
    $newid = dbSafeInsert($insSql, 'iiiiiii', array ($nextcon, $perid, $newMemId, $newtid, $newtid, $badgeId, $user_perid));
    if ($newid === false) {
        $response['error'] = "Failed inserting rolled over badge for $badgeId ($perid: $first_name $last_name) of $newMemId";
        ajaxSuccess($response);
        return false;
    }

    // add reg note
    $logNote = "Rolled over from $conid-$badgeId to $nextcon by $user_perid";
    $noteid = dbSafeInsert($insNoteSQL, 'iiis', array($user_perid, $newtid, $newid, $logNote));
}

$response['message'] = count($rolloverList) . " regs rolled over for $perid: $first_name $last_name";

ajaxSuccess($response);
return true;
?>
