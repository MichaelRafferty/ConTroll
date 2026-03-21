<?php
require_once __DIR__ . "/../lib/base.php";

$response = array("post" => $_POST, "get" => $_GET, "session" => getAllSessionVars());

$con = get_con();
$conid=$con['id'];
$perm='artinventory';
$response = array();
$response['conid'] = $conid;
$response['perm'] = $perm;

$check_auth = check_atcon($perm, $conid);
if($check_auth == false) { 
    ajaxSuccess(array('error' => "Authentication Failure"));
}

// data: { type: type, item: item, quantity: quantity, print: print, },

if (!(array_key_exists('type', $_POST) && array_key_exists('item', $_POST) &&
    array_key_exists('quantity', $_POST) && array_key_exists('print', $_POST))) {
    ajaxSuccess(array ('error' => 'Parameter Error'));
    exit();
}

$type = $_POST['type'];
$item = $_POST['item'];
$quantity = $_POST['quantity'];
$print = $_POST['print'];

if ($type == 'bid' && !array_key_exists('bid', $_POST)) {
    ajaxSuccess(array ('error' => 'Parameter Error'));
    exit();
}
$bid = $_POST['bid'];

// check validity of inputs
if ($type != 'bid') {
    $response['message'] = "fake success for type $type of item $item for quantity $quantity which print check is $print";
} else {
    $response['message'] = "fake success for type $type of item $item at new bid $bid";
}

ajaxSuccess($response);
