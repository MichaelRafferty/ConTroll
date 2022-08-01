<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

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

$source = sql_safe($_POST['src']);
$user = sql_safe($_POST['user']);

$paymentQ = "INSERT INTO payments (cashier, type, category, source, amount)"
    . " VALUES ( $user, 'credit', '$source', 'cashier', '" 
    . sql_safe($_POST['ssl_amount']) . "');";
$payment = dbInsert($paymentQ);


$paymentQ = "UPDATE payments SET txn_time='". sql_safe($_POST['ssl_txn_time']) 
    . "'" . ", cc='" . sql_safe($_POST['ssl_card_number']) . "'"
    . ", cc_txn_id='" . sql_safe($_POST['ssl_txn_id']) . "'"
    . ", cc_approval_code='" . sql_safe($_POST['ssl_approval_code']) . "'"
    . " WHERE id=$payment";
dbQuery($paymentQ);

$response['payment'] = $payment;

ajaxSuccess($response);
?>
