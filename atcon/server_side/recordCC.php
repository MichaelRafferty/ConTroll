<?php
require_once "lib/base.php";

$response = array("post" => $_POST, "get" => $_GET);

$con = get_conf("con");
$conid=$con['id'];
$check_auth=false;

if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])
    && isset($_POST['src']) && isset($_POST['ssl_card_number'])
    && isset($_POST['ssl_txn_id']) && isset($_POST['ssl_approval_code'])
    && isset($_POST['ssl_txn_time'])) {

    $perm = $_POST['src'];
    if($_POST['src'] == 'reg') { $perm = 'cashier'; }

    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
} else {
    $check_auth = false;
}

if(!$check_auth) {
    $response['error']="Bad Call";
    ajaxSuccess($response);
    exit();
}

$source = $_POST['src'];
$user = $_POST['user'];

$paymentQ = <<<EOS
INSERT INTO payments (cashier, type, category, source, amount)
VALUES (?, 'credit', ?, 'cashier', ?);
EOS;

$payment = dbSafeInsert($paymentQ, 'isd', array($user, $source, $_POST['ssl_amount']));


$paymentQ = <<<EOS
UPDATE payments
SET txn_time=?, cc=?, cc_txn_id=?, cc_approval_code=?
WHERE id=$payment";
EOS;

dbSafeCmd($paymentQ, 'ssssi', array($_POST['ssl_txn_time'], $_POST['ssl_card_number'], $_POST['ssl_txn_id'], $_POST['ssl_approval_code'], $payment));

$response['payment'] = $payment;

ajaxSuccess($response);
?>
