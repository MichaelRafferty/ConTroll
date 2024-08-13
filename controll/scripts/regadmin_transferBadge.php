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

if (!isset($_POST) || !isset($_POST['perid']) || !isset($_POST['badge'])
    || ($_POST['badge'] == '') || ($_POST['perid'] == '')) {
    $response['error'] = "Missing Information";
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid = $con['id'];

$from = $_POST['badge'];
$to = $_POST['perid'];
$from_person = $_POST['from_perid'];

$checkR = dbSafeQuery("SELECT id FROM perinfo WHERE id=?;", 'i', array($to));
if ($checkR->num_rows < 1) {
    $response['error'] = "Person $to does not exist";
    ajaxSuccess($response);
    return;
}
$checkR->free();

$checkR = dbSafeQuery("SELECT status FROM reg WHERE id=?;", 'i', array($from));
if ($checkR->num_rows < 1) {
    $response['error'] = "From registration $from does not exist";
    ajaxSuccess($response);
    return;
}
$status = $checkR->fetch_row()[0];
$checkR->free();
if ($status != 'paid' && $status != 'upgraded') {
    $response['error'] = "From registration status of $status is not 'paid' or 'upgraded', it cannot be transferred";
    ajaxSuccess($response);
    return;
}

$tType = 'regctl-adm-tfr/' . $user_perid;
$notes = "Transfer from $from_person to $to by $user_perid";
$insertT = <<<EOS
INSERT INTO transaction(conid, perid, userid, create_date, complete_date, price, couponDiscount, paid, type, notes ) 
VALUES (?, ?, ?, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), 0, 0, 0, ?, ?);
EOS;
$newtid = dbSafeInsert($insertT, 'iiiss', array($conid, $to, $user_perid, $tType, $notes));
if ($newtid === false) {
    $response['error'] = "Failed to insert transfer transaction";
    ajaxSuccess($response);
    return;
}

$nQ = <<<EOS
INSERT INTO reg(conid, perid, oldperid, create_date, change_date, pickup_date, price, couponDiscount, paid,
                create_trans, complete_trans, create_user, memId, coupon, printable, status)
SELECT conid, ?, ?, create_date, CURRENT_TIMESTAMP(), pickup_date, price, couponDiscount, paid, 
       ?, ?, ?, memId, coupon, printable, status
FROM reg
WHERE id = ?;
EOS;
$uQ = <<<EOS
UPDATE reg
SET status = 'transfered', change_date=CURRENT_TIMESTAMP()
WHERE id = ?;
EOS;
$iN = <<<EOS
INSERT INTO regActions(logdate,userid,tid,regid,action,notes)
VALUES (NOW(), ?, ?, ?, 'notes', ?);
EOS;

$response['query'] = $nQ . PHP_EOL . $uQ . PHP_EOL . $iN;
$newRegId = dbSafeInsert($nQ, 'iiiiii', array($to, $from_person, $newtid, $newtid, $user_perid, $from));
$num_rows = dbSafeCmd($uQ, 'i', array($from));
$notes = "Transfer membership $from from $from_person to $to by $user_perid";
$notesKey = dbSafeInsert($iN, 'iiis', array($user_perid, $newtid, $newRegId, $notes));

if ($num_rows === false) {
    $response['error'] = 'Database error transferring membership';
} else if ($num_rows == 1) {
    $response['success'] = "Membership $from transferred from $from_person to $to as reg $newRegId";
} else {
    $response['warning'] = "Error updating old membership $to";
}

ajaxSuccess($response);
?>
