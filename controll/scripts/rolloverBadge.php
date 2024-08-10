<?php
global $db_ini;

require_once '../lib/base.php';

$check_auth = google_init('ajax');
$perm = 'reg_admin';
// note: old perm check was $perm = 'registration';

$response = array('post' => $_POST, 'get' => $_GET, 'perm'=>$perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('user_perid', $_SESSION)) {
    $user_perid = $_SESSION['user_perid'];
} else {
    ajaxError('Invalid credentials passed');
    return;
}

if (!isset($_POST) || !isset($_POST['perid']) || !isset($_POST['badge'])
    || ($_POST['badge'] == '') || ($_POST['perid'] == '')) {
    $response['error'] = 'Missing Information';
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid = $con['id'];

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);
$nextcon = $conid + 1;

if (isset($_POST['badge'])) {
    $badgeid = $_POST['badge'];
} else {
    ajaxError("No current badge");
    return false;
}

if (isset($_POST['type'])) {
    $rolloverType = $_POST['type'];
} else {
    $rolloverType = 'rollover';
}

// validate rollover request
//  rules:
//  type rollover
//      badgetype is a paid badge (and temporarly an upgraded, untl the upgrade chain is done) and is not one day
//      badge has not been picked up or printed
//      no membership for this person for conid + 1
// type volunteer
//      badge type is a paid badge (and temporarly an upgraded, untl the upgrade chain is done) or volunteer
//      no reg for this person for conid + 1

$validateSQL = <<<EOS
WITH prints AS (
SELECT count(h.action) AS printCnt
FROM reg r
LEFT OUTER JOIN reg_history h ON h.regid = r.id AND h.action = 'print'
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
$result = dbSafeQuery($validateSQL, 'iiii', array($badgeid, $nextcon, $conid, $badgeid));
if ($result->num_rows > 1) {
    $response['error'] = "Error: Need to resolve existing next year registrations for this person, " . $result->num_rows . " registrations found.";
    ajaxSuccess($response);
    return false;
}
if ($result->num_rows < 1) {
    $response['error'] = "Error: Issue retrieving registrations for this person with badge id $badgeid";
    ajaxSuccess($response);
    return false;
}
$membership = fetch_safe_assoc($result);
$result->free();
$perid = $membership['perid'];
$label = $membership['label'];
$status = $membership['status'];
$nextlabel = $membership['nextlabel'];
$nextstatus = $membership['nextstatus'];
$first_name = $membership['first_name'];
$last_name = $membership['last_name'];
$printCnt = $membership['printCnt'];

// handle common case that membership cannot exist already for next con
if ($membership['nextid'] != null) {
    $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) of type $label as it already has a membership of type $nextlabel of status $nextstatus for $nextcon";
    ajaxSuccess($response);
    return false;
}

if ($status != 'paid' && $status != 'upgraded') {
    $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) of type $label as its status is $status";
    ajaxSuccess($response);
    return false;
}

$paid = $membership['paid'];
$couponDiscount = $membership['couponDiscount'];
$price = $membership['price'];
$category = strtolower($membership['memCategory']);
$memtype = strtolower($membership['memType']);
$lclabel = strtolower($label);
$memId = $membership['memId'];
$age = strtolower($membership['memAge']);

if ($status == 'rolled-over') {
    $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it already been rolled over";
    ajaxSuccess($response);
    return false;
}

if ($memtype == 'oneday') {
    $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it is a one-day membership";
    ajaxSuccess($response);
    return false;
}
$upgradePrior = false;
switch ($rolloverType) {
    case 'rollover':
        if ($status != 'paid' && $status != 'upgraded') {
            $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it not a paid or upgraded membership";
            ajaxSuccess($response);
            return false;
        }
        if ($membership['printCnt'] > 0) {
            $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it was already printed";
            ajaxSuccess($response);
            return false;
        }

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

        $upgradePrior = true;
        $newlabel = $membership['label'];
        break;

    case 'volunteer':
        if ($status != 'paid') {
            $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it not a paid membership";
            ajaxSuccess($response);
            return false;
        }

        $newlabel = 'volunteer';
        // insert a controlling transaction to cover this rollover
        $tType = 'regctl-adm-volroll/' . $user_perid;
        $notes = "Volunteer Rollover from $conid to $nextcon by $user_perid";
        $insertT = <<<EOS
INSERT INTO transaction(conid, perid, userid, create_date, complete_date, price, couponDiscount, paid, type, notes ) 
VALUES (?, ?, ?, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), 0, 0, 0, ?, ?);
EOS;
        $newtid = dbSafeInsert($insertT, 'iiiss', array($conid, $perid, $user_perid, $tType, $notes));
        if ($newtid === false) {
            $response['error'] = 'Failed to insert rollover transaction';
            ajaxSuccess($response);
            return;
        }
        break;

    default:
        $response['error'] = "Improper rollover type received: $rolloverType";
        ajaxSuccess($response);
        return false;
}

// now insert the new reg for the next year
// TODO: add reg chain for tracking or consider status rollover
$memidSQL = <<<EOS
WITH memEndDate AS (
    SELECT MIN(endDate) AS endDate
    FROM memList
    WHERE conid = ? AND LOWER(label) = ? AND endDate > NOW()
) 
SELECT id
FROM memList m
JOIN memEndDate me
WHERE m.conid = ? AND LOWER(m.label) = ? AND m.endDate = me.endDate;
EOS;

$memidR = dbSafeQuery($memidSQL, 'isis', array($nextcon, $newlabel, $nextcon, $newlabel));
if ($memidR === false || $memidR->num_rows != 1) {
    $response['error'] = "Cannot find $nextcon membership item for label $newlabel";
    ajaxSuccess($response);
    return false;
}

$newMemId = $memidR->fetch_row()[0];
$memidR->free();

if ($upgradePrior) {
    // mark prior membership as rolled-over
    $upgsql = <<<EOS
UPDATE reg
SET reg.status = 'rolled-over'
WHERE reg.id = ? AND reg.conid = ?;
EOS;
    $numrows = dbSafeCmd($upgsql, 'ii', array($badgeid, $conid));
    if ($numrows != 1) {
        $response['error'] = "Failed altering rollover $badgeid ($perid: $first_name $last_name) to rolled-over status";
        ajaxSuccess($response);
        return false;
    }
}

    $insSql = <<<EOS
INSERT INTO reg(conid, perid, create_date, price, couponDiscount, paid, status, memId, create_trans)
VALUES(?, ?, CURRENT_TIMESTAMP, 0, 0, 0, 'paid', ?, ?);
EOS;
$newid = dbSafeInsert($insSql, 'iiii', array($nextcon, $perid, $newMemId, $newtid));
if ($newid === false) {
    $response['error'] = "Failed inserting rolled over badge for $badgeid ($perid: $first_name $last_name) of type $newlabel ($newMemId)";
    ajaxSuccess($response);
    return false;
}

$response['success'] = "Badge $badgeid of type $label rolled over to $newid of type $nextcon $newlabel ($newMemId) for $perid: $first_name $last_name";
if ($rolloverType == 'rollover') {
    $response['newlabelid'] = $newid;
}

ajaxSuccess($response);
return true;
?>
