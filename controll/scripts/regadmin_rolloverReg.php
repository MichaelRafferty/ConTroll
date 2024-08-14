<?php
global $db_ini;

require_once '../lib/base.php';

$check_auth = google_init('ajax');
$perm = 'reg_admin';

$response = array ('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
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

if (!isset($_POST) || !isset($_POST['rolloverList']) || !isset($_POST['action']) || !isset($_POST['direction'])
    || $_POST['action'] != 'rollover') {
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
                r1.id AS nextid, r1.status as nextstatus, m1.label as nextlabel, h.printCnt, p.first_name, p.last_name
FROM reg r
JOIN memList m ON (r.memId = m.id)
JOIN perinfo p ON (r.perid = p.id)
JOIN prints h
LEFT OUTER JOIN reg r1 ON (r1.conid = ? AND r.perid = r1.perid AND r1.status IN ('paid', 'unpaid', 'plan', 'upgraded'))
LEFT OUTER JOIN memList m1 ON (r1.memId = m1.id)
WHERE r.conid = ? AND r.id = ?; 
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
}
// ok the checks are all done, now do the work

// insert a controlling transaction to cover this rollover
$tType = 'regctl-adm-roll/' . $user_perid;
$notes = "Rollover from $conid to $nextcon by $user_perid";
$insertT = <<<EOS
INSERT INTO transaction(conid, perid, userid, create_date, complete_date, price, couponDiscount, paid, type, notes) 
VALUES (?, ?, ?, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), 0, 0, 0, ?, ?);
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
INSERT INTO reg(conid, perid, create_date, price, couponDiscount, paid, status, memId, create_trans, priorRegId, create_user)
VALUES(?, ?, CURRENT_TIMESTAMP, 0, 0, 0, 'paid', ?, ?, ?, ?);
EOS;
foreach ($rolloverList as $basdgeId => $rollover) {
    // mark prior membership as rolled-over
    $newMemId = $rollover['newid'];

    $numrows = dbSafeCmd($upgsql, 'iii', array ($user_perid, $badgeId, $conid));
    if ($numrows != 1) {
        $response['error'] = "Failed altering rollover $badgeId ($perid: $first_name $last_name) to rolled-over status";
        ajaxSuccess($response);
        return false;
    }

    $newid = dbSafeInsert($insSql, 'iiiiii', array ($nextcon, $perid, $newMemId, $newtid, $badgeId, $user_perid));
    if ($newid === false) {
        $response['error'] = "Failed inserting rolled over badge for $badgeId ($perid: $first_name $last_name) of $newMemId";
        ajaxSuccess($response);
        return false;
    }
}

$response['message'] = count($rolloverList) . " regs rolled over for $perid: $first_name $last_name";

ajaxSuccess($response);
return true;
?>