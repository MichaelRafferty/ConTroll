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
    // default to rollover type if not specified
    $rolloverType = 'rollover';
    //ajaxError("No rollover type");
    //return false;
}

// validate rollover request
//  rules:
//  type rollover
//      badgetype is a paid badge or rollover other than rollover-cancel and is not one day
//      badge has not been picked up or printed
//      no membership for this person for conid + 1
// type volunteer
//      badge type is a paid badge or rollover-volunteer
//      no reg for this person for conid + 1

$validateSQL = <<<EOS
SELECT DISTINCT r.id, r.perid, r.price, r.couponDiscount, r.paid, m.label, m.memAge, m.memCategory, m.memType, r1.id AS nextid, m1.label as nextlabel, H.action,
                p.first_name, p.last_name
FROM reg r
JOIN memList m ON (r.memId = m.id)
JOIN perinfo p ON (r.perid = p.id)
LEFT OUTER JOIN reg r1 ON (r1.conid = ? AND r.perid = r1.perid)
LEFT OUTER JOIN memList m1 ON (r1.memId = m1.id)
LEFT OUTER JOIN reg_history H ON (H.regid = r.id and action = 'print')
WHERE r.conid = ? AND r.id = ?
EOS;
$result = dbSafeQuery($validateSQL, 'iii', array($nextcon, $conid, $badgeid));
if ($result->num_rows > 1) {
    $response['error'] = "Error: Need to resolve duplicate registrations for this person, " . $result->num_rows . " registrations found.";
    ajaxSuccess($response);
    return false;
}
if ($result->num_rows < 1) {
    $response['error'] = "Error: Issue retrieving registrations for this person with badge id $badgeid";
    ajaxSuccess($response);
    return false;
}
$membership = fetch_safe_assoc($result);
$perid = $membership['perid'];
$label = $membership['label'];
$nextlabel = $membership['nextlabel'];
$first_name = $membership['first_name'];
$last_name = $membership['last_name'];

// handle common case that membership cannot exist already for next con
if ($membership['nextid'] != null) {
    $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) of type $label as it already has a membership of type $nextlabel for $nextcon";
    ajaxSuccess($response);
    return false;
}

$paid = $membership['paid'];
$couponDiscount = $membership['couponDiscount'];
$price = $membership['price'];
$category = strtolower($membership['memCategory']);
$memtype = strtolower($membership['memType']);
$lclabel = strtolower($label);
$age = strtolower($membership['memAge']);

if ($lclabel == 'rollover-cancel') {
    $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it already has a roll over of $nextlabel for $nextcon";
    ajaxSuccess($response);
    return false;
}

if ($memtype == 'oneday') {
    $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it is a one-day membership";
    ajaxSuccess($response);
    return false;
}

$paidbadge = (($paid + $couponDiscount) > 0 && $price > 0) || ($age == 'kit');  // consider kit (a $0 badge to be paid)

switch ($rolloverType) {
    case 'rollover':
        if (! ($paidbadge || $category == 'rollover')) { // paid badge or category rollover allowed to be rolled over)
            $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it not a paid or rollover membership";
            ajaxSuccess($response);
            return false;
        }
        if ($membership['action'] == 'print') {
            $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it was already printed";
            ajaxSuccess($response);
            return false;
        }

        if ($category == 'rollover') {
            $newlabel = $label;
        } else {
            $newlabel = 'rollover-' . $lclabel;
        }

        // insert a controlling transaction to cover this rollover
        $tType = 'regctl-adm-roll/' . $user_perid;
        $notes = "Rollover from $conid to $nextcon by $user_perid";
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
        // mark prior membership as rollover-cancel
        $upgsql = <<<EOS
UPDATE reg
JOIN memList m ON (m.conid = ? AND LOWER(m.label) = 'rollover-cancel')
SET reg.memID = m.id
WHERE reg.id = ? AND reg.conid = ?;
EOS;
        $numrows = dbSafeCmd($upgsql, 'iii', array($conid, $badgeid, $conid));
        if ($numrows != 1) {
            $response['error'] = "Failed altering rollover $badgeid ($perid: $first_name $last_name) to rollover-cancel";
            ajaxSuccess($response);
            return false;
        }
        $sql = <<<EOS
SELECT memId
FROM reg
WHERE conid = ? and id = ?
EOS;
        $result = fetch_safe_assoc(dbSafeQuery($sql, 'ii', array($conid, $badgeid)));
        $newlabelid = $result['memId'];
        break;

    case 'volunteer':
        if (! ($paidbadge || $lclabel == 'rollover-volunteer')) { // paid padge or volunteer badge earned a volunteer rollover
            $response['error'] = "Cannot rollover $badgeid ($perid: $first_name $last_name) as it not a paid or rolled-over volunteer membership";
            ajaxSuccess($response);
            return false;
        }

        $newlabel = 'rollover-volunteer';
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

// now insert the new reg for 2023
$inssql = <<<EOS
INSERT INTO reg(conid, perid, create_date, price, couponDiscount, paid, memId, create_trans)
SELECT ?, ?, NOW(), 0, 0, 0, id, ?
FROM memList
WHERE conid = ? AND LOWER(label) = ?;
EOS;

$newid = dbSafeInsert($inssql, 'iiiis', array($nextcon, $perid, $newtid, $nextcon, $newlabel));
if ($newid === false) {
    $response['error'] = "Failed inserting rolled over badge for $badgeid ($perid: $first_name $last_name) of type $newlabel";
    ajaxSuccess($response);
    return false;
}

$response['success'] = "Badge $badgeid of type $label rolled over to $newid of type $nextcon $newlabel for $perid: $first_name $last_name";
if ($rolloverType == 'rollover') {
    $response['newlabelid'] = $newid;
}

ajaxSuccess($response);
return true;
?>
