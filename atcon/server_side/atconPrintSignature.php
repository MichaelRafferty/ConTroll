<?php
require_once "lib/base.php";
require_once "lib/ajax_functions.php";

$response = array("post" => $_POST, "get" => $_GET);

$con = get_con();
$conid=$con['id'];
$check_auth=false;

if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], 'artshow', $conid);
    $check_auth2 = check_atcon($_POST['user'], $_POST['passwd'], 'cashier', $conid);
}

if(!$check_auth && !$check_auth2) {
    $response['error'] = "Auth Error";
    ajaxSuccess($response);
    exit();
}

if(!isset($_POST) || !isset($_POST['payment'])) {
    $response['error'] = "Need Item Info";
    ajaxSuccess($response);
    exit();
}

$payment = $_POST['payment'];

$sigQ = "SELECT cc, cc_txn_id, cc_approval_code, txn_time, amount FROM payments WHERE id=?";
$sigR = dbSafeQuery($sigQ, 'i', array($payment));
$response['ccinfo'] = fetch_safe_assoc($sigR);

ajaxSuccess($response);
?>
