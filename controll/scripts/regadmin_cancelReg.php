<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "reg_admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('user_perid', $_SESSION)) {
    $user_perid = $_SESSION['user_perid'];
} else {
    ajaxError('Invalid credentials passed');
    return;
}

if (!isset($_POST) || !isset($_POST['cancelList']) || !isset($_POST['direction'])|| !isset($_POST['action'])
    || $_POST['action'] != 'cancel') {
    $response['error'] = "Invalid Parameters";
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid = $con['id'];

$cancelList = $_POST['cancelList'];
$direction = $_POST['direction'];

// loop over change list and update the status to cancelled
    if ($direction == 0) {
        $noteMsg = "$user_perid cancelled the membership";
        $updQ = <<<EOS
UPDATE reg
SET status = 'cancelled', updatedBy = ?
WHERE id IN (?);
EOS;
    } else {
        $noteMsg = "$user_perid restored the membership";
        $updQ = <<<EOS
UPDATE reg
SET status = CASE 
    WHEN price = paid + couponDiscount THEN 'paid'
    WHEN planId IS NOT NULL THEN 'plan'
    ELSE 'unpaid'
END, updatedBy = ?
WHERE id IN (?);
EOS;
    }
$cancelIn = implode(',', $cancelList);
$num_upd = dbSafeCmd($updQ, 'is', array($user_perid, $cancelIn));
if ($num_upd === false || $num_upd < 0) {
    $response['error'] = "Error running $updQ on $cancelIn";
} else {
    $response['success'] = "$num_upd registrations changed";
    // insert a reg note for the successful action
    $insQ = <<<EOS
INSERT INTO regActions(userid, regid, action, notes)
VALUES (?, ?, ?, ?);
EOS;
    $typestr = 'iiss';
    foreach ($x as $regId) {
        $paramarray = array($user_perid, $regId, 'notes', $noteMsg);
        $new_history = dbSafeInsert($insQ, $typestr, $paramarray);
    }
}

ajaxSuccess($response);
?>
