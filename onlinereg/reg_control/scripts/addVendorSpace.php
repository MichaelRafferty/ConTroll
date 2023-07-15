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

// first the vendor space
$insertVS = <<<EOS
INSERT INTO vendor_space(conid, vendorId, spaceId, item_requested, item_approved, item_purchased, price, paid, transid)
VALUES (?,?,?,?,?,?,?,?,?);
EOS;

$dataTypes = 'iiiiiiddi';
$values = array($conid, $vendorId, $spaceId, $spacePriceId, null, null, null, null, null);

if ($operation == 'A' || $operation == 'P')
    $values[4] = $spacePriceId;

if ($operation == 'P') {
    $values[5] = $spacePriceId;
    $values[6] = $_POST['price'];
    $values[7] = $_POST['payment'];

    $desc = "Check No: " . $_POST['checkno'] . " for " . $_POST['included'] . 'I/' . $_POST['additional']  . 'A// ' . $_POST['description'];;

    // build transaction
    $insertT = <<<EOS
INSERT INTO transaction(conid, userid, complete_date, price, paid, type, notes) 
VALUES (?,?, current_timestamp(), ?, ?, 'vendor', ?);
EOS;
    $transid = dbSafeInsert($insertT, 'iidds', array($conid, $user, $_POST['price'], $_POST['payment'], $desc));
    // build payment

    $insertP = <<<EOS
INSERT INTO payments(transid, type, category, description, source, amount, time, userid) 
VALUES (?, 'check', 'vendor', ?, 'reg_control', ?, now(), ?);
EOS;
    $payid = dbSafeInsert($insertP, 'isdi', array($transid, $desc, $_POST['payment'], $user));
    $values[8] = $transid;
}

$vsid = dbSafeInsert($insertVS, $dataTypes, $values);

$response['success'] = "Space added ($vsid)" . ($transid ? ", transaction id($transid)" : '') . ($payid ? ", payment($payid)" : '');

ajaxSuccess($response);
return;
