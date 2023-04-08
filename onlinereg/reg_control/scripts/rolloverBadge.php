<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "registration";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || (!checkAuth($check_auth['sub'], $perm) &&
                   (!checkAuth($check_auth['sub'], 'atcon')))) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$user = $check_auth['email'];
$response['user'] = $user;
$userQ = "SELECT id FROM user WHERE email=?;";
$userR = fetch_safe_assoc(dbSafeQuery($userQ, 's', array($user)));
$userid = $userR['id'];
$con = get_conf('con');
$conid=$con['id'];
$nextcon = $conid + 1;

if (isset($_POST['id'])) {
    $badgeid = $_POST['id'];
} else {
    ajaxError("No current badge");
    return false;
}

if (isset($_POST['type'])) {
    $rolloverType = $_POST['type'];
} else {
    ajaxError("No rollover type");
    return false;
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
SELECT DISTINCT r.id, r.perid, r.price, r.paid, m.label, m.memAge, m.memCategory, m.memType, r1.id AS nextid, m1.label as nextlabel, H.action
FROM reg r
JOIN memList m ON (r.memId = m.id)
LEFT OUTER JOIN reg r1 ON (r1.conid = ? AND r.perid = r1.perid)
LEFT OUTER JOIN memList m1 ON (r1.memId = m1.id)
LEFT OUTER JOIN atcon_history H ON (H.regid = r.id and action = 'print')
WHERE r.conid = ? AND r.id = ?
EOS;
$result = dbSafeQuery($validateSQL, 'iii', array($nextcon, $conid, $badgeid));
if ($result->num_rows > 1) {
    $response['error'] = "Error: Need to resolve duplicate resistrations for this person, " . $result->num_rows . " registrations found.";
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

// handle common case that membership cannot exist already for next con
if ($membership['nextid'] != null) {
    $response['error'] = "Cannot rollover $badgeid ($perid)  of type $label as it alrady has a membershop of type $nextlabel for $nextcon";
    ajaxSuccess($response);
    return false;
}

$paid = $membership['paid'];
$price = $membership['price'];
$category = strtolower($membership['memCategory']);
$memtype = strtolower($membership['memType']);
$lclabel = strtolower($label);
$age = strtolower($membership['memAge']);

if ($lclabel == 'rollover-cancel') {
    $response['error'] = "Cannot rollover $badgeid ($perid) as it alrady has a roll over of $nextlabel for $nextcon";
    ajaxSuccess($response);
    return false;
}

if ($memtype == 'oneday') {
    $response['error'] = "Cannot rollover $badgeid ($perid) as it is a one-day membership";
    ajaxSuccess($response);
    return false;
}

$paidbadge = ($paid > 0 && $price > 0) || ($age == 'kit');  // consider kit (a $0 badge to be paid)

switch ($rolloverType) {
    case 'rollover':
        if (! ($paidbadge || $category == 'rollover')) { // paid padge or category rollover allowed to be rolled over)
            $response['error'] = "Cannot rollover $badgeid ($perid) as it not a paid or rollover membership";
            ajaxSuccess($response);
            return false;
        }
        if ($membership['action'] == 'print') {
            $response['error'] = "Cannot rollover $badgeid ($perid) as it was already printed";
            ajaxSuccess($response);
            return false;
        }

        if ($category == 'rollover') {
            $newlabel = $label;
        } else {
            $newlabel = 'rollover-' . $lclabel;
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
            $response['error'] = "Failed altering rollover $badgeid ($perid) to rollover-cancel";
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
            $response['error'] = "Cannot rollover $badgeid ($perid) as it not a paid or rolled-over volunteer membership";
            ajaxSuccess($response);
            return false;
        }

        $newlabel = 'rollover-volunteer';
        break;

    default:
        $response['error'] = "Improper rollover type received: $rolloverType";
        ajaxSuccess($response);
        return false;
}

// now insert the new reg for 2023
$inssql = <<<EOS
INSERT INTO reg(conid, perid, create_date, price, paid, memId)
SELECT ?, ?, NOW(), 0, 0, id
FROM memList
WHERE conid = ? AND LOWER(label) = ?;
EOS;

$newid = dbSafeInsert($inssql, 'iiis', array($nextcon, $perid, $nextcon, $newlabel));
if ($newid === false) {
    $response['error'] = "Failed inserting rolled over badge for $badgeid ($perid) of type $newlabel";
    ajaxSuccess($response);
    return false;
}

$response['success'] = "Badge $badgeid of type $label rolled over to $newid of type $nextcon $newlabel for $perid";
if ($rolloverType == 'rollover') {
    $response['newlabelid'] = $newlabelid;
}

ajaxSuccess($response);
return true;
?>
