<?php

require_once "../lib/base.php";

$check_auth = google_init("ajax");
$perm = "art_control";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);


if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$con = get_conf('con');
$conid= $con['id'];

$region = $_POST['region'];
$tabledata = null;
$response['region'] = $region;

try {
    $tabledata = json_decode($_POST['tabledata'], true, 512, JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['error'] = $msg;
    $error_log($msg);
    ajaxSuccess($response);
    exit();
}

$response['tabledata'] = $tabledata;

$updateSQL = <<<EOS
UPDATE artItems
SET item_key = ?, location = ?, min_price = ?, original_qty = ?, quantity = ?, sale_price = ?, status = ?, title = ?, type = ?
WHERE id = ?
EOS;

$updateTypes = "issiissssi";

$updated = 0;

foreach ($tabledata as $row) {
    $paramarray = array($row['item_key'], $row['location'], $row['min_price'], $row['original_qty'], $row['quantity'], $row['sale_price'], $row['status'] , $row['title'], $row['type'], $row['id']);
    $updated += dbSafeCmd($updateSQL, $updateTypes, $paramarray);
}
    
$response['status'] = "$updated items Updated";

ajaxSuccess($response);
?>
