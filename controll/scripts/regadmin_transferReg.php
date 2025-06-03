<?php
global $db_ini;

require_once "../lib/base.php";

$check_auth = google_init('ajax');
$perm = 'reg_staff';

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

if (!isset($_POST) || !isset($_POST['transferList']) || !isset($_POST['action'])
    || !isset($_POST['from']) || !isset($_POST['to']) || $_POST['action'] != 'transfer') {
    $response['error'] = 'Invalid Parameters';
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('source', $_POST)) {
    $message_error = 'Source Missing';
    RenderErrorAjax($message_error);
    exit();
}
$source = $_POST['source'];

$con = get_conf('con');
$conid = $con['id'];

$transferList = $_POST['transferList'];
$from_person = $_POST['from'];
$to_person = $_POST['to'];

$checkR = dbSafeQuery("SELECT id FROM perinfo WHERE id=?;", 'i', array($to_person));
if ($checkR->num_rows < 1) {
    $response['error'] = "Person $to_person does not exist";
    ajaxSuccess($response);
    return;
}
$checkR->free();

// build string of items to cancel, cannot use '?' prepared notation for an IN clause
$inString = '';
foreach ($transferList as $id) {
    if (is_numeric($id)) {
        $inString .= $id . ',';
    }
}

if ($inString == '') {
    $response['error'] = 'No items to transfer';
    ajaxSuccess($response);
    exit();
}

    $inString = substr($inString, 0, -1);

$denyTransfer = ['rolled-over', 'cancelled','refunded', 'transfered'];

$checkR = dbQuery("SELECT id, status FROM reg WHERE id IN ($inString);");
if ($checkR->num_rows != count($transferList)) {
    $response['error'] = "From registration count does not matched passed value.";
    ajaxSuccess($response);
    return;
}
$message = '';
while ($row = $checkR->fetch_assoc()) {
    if (in_array($row['status'], $denyTransfer)) {
        $message .= "Registration " . $row['id'] . " of status " . $row['status'] . " is denied for transfers.<br/>";
    }
}
$checkR->free();
if ($message != '') {
    $response['error'] = $message;
    ajaxSuccess($response);
    return;
}

$tType = 'regctl-adm-tfr/' . $user_perid;
$notes = "Transfer regs from $from_person to $to_person by $user_perid";
$insertT = <<<EOS
INSERT INTO transaction(conid, perid, userid, create_date, complete_date, price, couponDiscountCart, couponDiscountReg, paid, type, notes ) 
VALUES (?, ?, ?, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), 0, 0, 0, 0, ?, ?);
EOS;
$newtid = dbSafeInsert($insertT, 'iiiss', array($conid, $to_person, $user_perid, $tType, $notes));
if ($newtid === false) {
    $response['error'] = "Failed to insert transfer transaction";
    ajaxSuccess($response);
    return;
}

$nQ = <<<EOS
INSERT INTO reg(conid, perid, oldperid, create_date, change_date, pickup_date, price, couponDiscount, paid,
                create_trans, complete_trans, create_user, memId, coupon, printable, status, priorRegId, updatedBy)
SELECT conid, ?, ?, create_date, CURRENT_TIMESTAMP(), pickup_date, price, couponDiscount, paid, 
       ?, ?, ?, memId, coupon, printable, status, ?, ?
FROM reg
WHERE id = ?;
EOS;
$uQ = <<<EOS
UPDATE reg
SET status = 'transfered', change_date=CURRENT_TIMESTAMP(), updatedBy = ?
WHERE id = ?;
EOS;
$iN = <<<EOS
INSERT INTO regActions(logdate,source,userid,tid,regid,action,notes)
VALUES (NOW(), ?, ?, ?, ?, 'notes', ?);
EOS;

foreach ($transferList as $from) {
    $response['query'] = $nQ . PHP_EOL . $uQ . PHP_EOL . $iN;
    $newRegId = dbSafeInsert($nQ, 'iiiiiiii', array ($to_person, $from_person, $newtid, $newtid, $user_perid, $from, $user_perid, $from));
    $num_rows = dbSafeCmd($uQ, 'ii', array ($user_perid, $from));
    $notes = "Transfer membership $from from $from_person to $to_person by $user_perid";
    $notesKey = dbSafeInsert($iN, 'siiis', array ($source, $user_perid, $newtid, $newRegId, $notes));

    if ($num_rows === false) {
        $response['error'] .= 'Database error transferring membership $from<br/>';
    }
    else if ($num_rows == 1) {
        $response['success'] = "Membership $from transferred from $from_person to $to_person as reg $newRegId<br/>";
    }
    else {
        $response['warning'] = "Error updating old membership $from<br/>";
    }
}

ajaxSuccess($response);
?>
