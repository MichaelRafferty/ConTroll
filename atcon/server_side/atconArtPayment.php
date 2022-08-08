<?php
require_once "lib/base.php";

$perm = "artshow";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

$con = get_conf("con");
$conid=$con['id'];
$check_auth=false;
if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
    $check_auth = check_atcon($_POST['user'], $_POST['passwd'], $perm, $conid);
}

$method='artshow';
$taxRate = $con['taxRate'];
$response['taxRate'] = $taxRate;

if(!isset($_SESSION['user']) ||
  !check_atcon($_SESSION['user'], $_SESSION['passwd'], $method, $conid)) {
    if(isset($_POST['user']) && isset($_POST['passwd']) &&
      check_atcon($_POST['user'], $_POST['passwd'], $method, $conid)) {
        $_SESSION['user']=$_POST['user'];
        $_SESSION['passwd']=$_POST['passwd'];
    } else {
        unset($_SESSION['user']);
        unset($_SESSION['passwd']);
    }
}

$purchaseUser = $_SESSION['user'];
$transUser = $_POST['perid'];
$paid = $_POST['amount'];
$desc = $_POST['description'];
$type = $_POST['type'];
$items = json_decode($_POST['items'], true);

$response['items'] = $items;
$response['type'] = $type;

$transQ = "INSERT INTO transaction (conid, perid, userid, type) VALUES ($conid, $transUser, 2, 'artshow');";

$transI = dbSafeInsert($transQ, 'ii', array($conid, $transUser));
$response['transid']=$transI;

$saleQ = "INSERT INTO artsales (transid, artid, perid, amount, quantity) VALUES";
$sqdatatypes = '';
$sqvalues = array();

$firstItem = true;
$totalPrice = 0;

foreach($items as $item) {
    $itemQ = <<<EOS
SELECT I.id, I.type, I.min_price, I.sale_price, I.final_price, I.quantity
FROM artshow S
JOIN artItems I ON (I.item_key=? AND I.conid=S.conid AND I.artshow=S.id)
WHERE S.art_key=? AND S.conid=?;
EOS;

    $itemR = fetch_safe_assoc(dbSafeQuery($itemQ, 'iii', array($item['item'], $conid, $item['artist'])));
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
            $datatypss .= 'di';
            $values[] = $itemPrice;
            $values[] = $transUser;
        }
    }
    $updateQ .= " WHERE id=?;";
    $datatypes .= 'i';
    $valuesp[] = $itemR['id'];
    dbSafeCmd($updateQ, $datatypes, $values);

    $totalPrice += $itemPrice;

    if (!$firstItem) { $saleQ .= ","; }
    else { $firstItem=false; }
    $saleQ .= " ?,?,?,?,?)";
    $sqdatatypes .= 'iiidi';
    $values[] = $transI;
    $values[] = $itemR['id'];
    $values[] = $transUser;
    $values[] = $itemPrice;
    $values[] = $itemQty;
}
$saleQ .= ";";

dbSafeInsert($saleQ);
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
if($type == 'credit') {
    $paymentQ = "UPDATE payments SET transid = ?, description = ? WHERE id=?;";
    dbSafeCmd($paymentQ, 'isi', array($transI,$desc,  $_POST['payment']));
} else { 
    dbSafeInsert($paymentQ, 'issdi', array($transI, $type, $desc, $amount, $purchaseUser));
}

ajaxSuccess($response);
?>
