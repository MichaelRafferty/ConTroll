<?php
require_once "../lib/base.php";

$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "session"=>$_SESSION);

$con = get_conf("con");
$conid=$con['id'];
$check_auth=false;
if (!check_atcon('artsales', $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}


$method='artshow';
$taxRate = $con['taxRate'];
$response['taxRate'] = $taxRate;

$purchaseUser = $_SESSION['user'];
$transUser = $_POST['perid'];
$paid = $_POST['amount'];
$desc = $_POST['description'];
$cc_approval_code = '';
if(isset($_POST['cc_approval_code'])) {
    $cc_approval_code = $_POST['cc_approval_code'];
}
$type = $_POST['type'];
$items = json_decode($_POST['items'], true);

$response['items'] = $items;
$response['type'] = $type;

$transQ = "INSERT INTO transaction (conid, perid, userid, type) VALUES ($conid, $transUser, 2, 'artshow');";

$transI = dbSafeInsert($transQ, 'ii', array($conid, $transUser));
$response['transid']=$transI;

$saleQ = "INSERT INTO artsales (transid, artid, perid, amount, quantity) VALUES (";
$sqdatatypes = '';
$sqvalues = array();

$firstItem = true;
$totalPrice = 0;

foreach($items as $item) {
    $itemQ = <<<EOS
SELECT I.id, I.type, I.min_price, I.sale_price, I.final_price, I.quantity
FROM artshow S
JOIN artItems I ON (I.conid=S.conid AND I.artshow=S.id)
WHERE I.item_key=? AND S.art_key=? AND S.conid=?;
EOS;

    $itemR = fetch_safe_assoc(dbSafeQuery($itemQ, 'iii', array($item['item'], $item['artist'], $conid)));
    $updateQ = "UPDATE artItems SET";
    $datatypes = '';
    $values = array();

    $itemPrice = $itemR['sale_price'];
    if($item['type'] == 'print') {
        $itemPrice = $itemPrice * $item['qty'];
        $itemQty = $item['qty'];
        $updateQ .= " quantity=" . ($itemR['quantity'] - $itemQty);
    } else {
        $itemPrice = $item['bid'];
        $itemQty = 1;
        if($item['depart'] == "staying") {
            $updateQ .= " final_price=?, bidder=?, status='Quicksale/Sold'";
            $datatypes .= 'di';
            $values[] = $itemPrice;
            $values[] = $transUser;
        } else if($item['depart'] == 'departing') {
            $updateQ .= " final_price=?, bidder=?, status='purchased/released'";
            $datatypes .= 'di';
            $values[] = $itemPrice;
            $values[] = $transUser;
        }
    }
    $updateQ .= " WHERE id=?;";
    $datatypes .= 'i';
    $values[] = $itemR['id'];
    dbSafeCmd($updateQ, $datatypes, $values);

    $totalPrice += $itemPrice;

    if (!$firstItem) { $saleQ .= ",("; }
    else { $firstItem=false; }
    $saleQ .= " ?,?,?,?,?)";
    $sqdatatypes .= 'iiidi';
    $sqvalues[] = $transI;
    $sqvalues[] = $itemR['id'];
    $sqvalues[] = $transUser;
    $sqvalues[] = $itemPrice;
    $sqvalues[] = $itemQty;
}
$saleQ .= ";";

dbSafeInsert($saleQ, $sqdatatypes, $sqvalues);
//var_dump($sqvalues);

$totalTax = $totalPrice * $taxRate/100;
$totalWT = $totalPrice + $totalTax;

$amount = $paid;
$change = 0;
if($amount > $totalWT) {
    $amount = $totalWT;
    $change = $paid - $totalWT;
}

$response['paid']=$amount;
$response['change']=round($change,2);
$response['totalP'] = $totalPrice;
$response['taxRate'] = $taxRate;
$response['totalT'] = $totalTax;
$response['WT'] = $totalWT;

if ($totalWT <= $paid) { $response['complete']='true'; }
else { $response['complete']='false'; }

$transUQ = "UPDATE transaction SET price=?, tax=?, withtax=?, paid=?, change_due=? WHERE id=?";
$paymentQ = "INSERT INTO payments(transid, type, category, description, source, amount, cashier) VALUES(?, ?, 'artshow', ?, 'Atcon Artshow', ?, ?);";

dbSafeCmd($transUQ, 'dddddi', array($totalPrice,$totalTax,$totalWT,$amount,$change,$transI));
if(($type == 'credit') || ($type == 'offline')){
    $type='credit';
    $paymentQ = "INSERT INTO payments(transid, type, category, description, cc_approval_code, source, amount, cashier) VALUES(?, ?, 'artshow', ?, ?, 'Atcon Artshow', ?, ?);";
    dbSafeInsert($paymentQ, 'isssdi', array($transI, $type, $desc, $cc_approval_code, $amount, $purchaseUser));
} else {
    dbSafeInsert($paymentQ, 'issdi', array($transI, $type, $desc, $amount, $purchaseUser));
}

ajaxSuccess($response);
?>
