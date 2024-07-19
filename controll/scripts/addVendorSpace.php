<?php
global $db_ini;

require_once '../lib/base.php';
$check_auth = google_init('ajax');
$perm = 'vendor';
$user = get_user($check_auth['sub']);

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = 'Authentication Failed';
    ajaxSuccess($response);
    exit();
}

$con = get_con();
$conf = get_conf('con');
$vendor_conf = get_conf('vendor');
$conid = $con['id'];

var_error_log($_POST);
if (!(array_key_exists('vendor', $_POST) && array_key_exists('space',$_POST) && array_key_exists('type',$_POST) && array_key_exists('state',$_POST))) {
      ajaxError('No Data');
      return;
}

$vendorId = $_POST['vendor'];
$spaceId = $_POST['space'];
$spacePriceId = $_POST['type'];
$operation = $_POST['state'];
$user_perid = $_SESSION['user_perid'];

// first the vendor space
$insertVS = <<<EOS
INSERT INTO vendor_space(conid, vendorId, spaceId, item_requested, time_requested, item_approved, time_approved, item_purchased, time_purchased, price, paid, transid)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?);
EOS;

$dataTypes = 'iiiisisisddi';
$values = array($conid, $vendorId, $spaceId, $spacePriceId, date('y-m-d h:i:s', time()), null, null, null, null, null, null, null);

$transid = null;
$payid = null;
if ($operation == 'A' || $operation == 'P') {
    $values[5] = $spacePriceId;
    $values[6] = date('y-m-d h:i:s', time());
}

if ($operation == 'P') {
    $values[7] = $spacePriceId;
    $values[8] = date('y-m-d h:i:s', time());
    $values[9] = $_POST['price'];
    $values[10] = $_POST['payment'];

    $desc = "Check No: " . $_POST['checkno'] . " for " . $_POST['included'] . 'I/' . $_POST['additional']  . 'A// ' . $_POST['description'];;

    // build transaction
    $insertT = <<<EOS
INSERT INTO transaction(conid, userid, complete_date, price, paid, type, notes) 
VALUES (?,?, current_timestamp(), ?, ?, 'vendor', ?);
EOS;
    $transid = dbSafeInsert($insertT, 'iidds', array($conid, $user_perid, $_POST['price'], $_POST['payment'], $desc));
    // build payment

    $insertP = <<<EOS
INSERT INTO payments(transid, type, category, description, source, pretax, tax, amount, time, cashier) 
VALUES (?, 'check', 'vendor', ?, 'controll', ?, ?, ?, now(), ?);
EOS;
    $payid = dbSafeInsert($insertP, 'isdddi', array($transid, $desc, $_POST['payment'], 0, $_POST['payment'], $user_perid));
    $values[11] = $transid;
}

$vsid = dbSafeInsert($insertVS, $dataTypes, $values);

$response['success'] = "Space added ($vsid)" . ($transid ? ", transaction id($transid)" : '') . ($payid ? ", payment($payid)" : '');

ajaxSuccess($response);
return;
