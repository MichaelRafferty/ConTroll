<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "lib/base.php";
require_once "lib/ajax_functions.php";

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
$transUser = sql_safe($_POST['perid']);
$paid = $_POST['amount'];
$desc = sql_safe($_POST['description']);
$type = sql_safe($_POST['type']);
$items = json_decode($_POST['items'], true);

$response['items'] = $items;
$response['type'] = $type;

$transQ = "INSERT INTO transaction (conid, perid, userid, type) values"
    . " ($conid, $transUser, 2, 'artshow');";

$transI = dbInsert($transQ);
$response['transid']=$transI;

$saleQ = "INSERT INTO artsales (transid, artid, perid, amount, quantity) values";

$firstItem = true;
$totalPrice = 0;

foreach($items as $item) {
    $itemQ = "SELECT I.id, I.type, I.min_price, I.sale_price"
        . ", I.final_price, I.quantity"
    . " FROM artshow as S"
        . " JOIN artItems as I ON I.item_key=" . $item['item']
            . " AND I.conid=$conid AND I.artshow=S.id"
    . " WHERE S.art_key=" . $item['artist'] . " AND S.conid=$conid;";
    $itemR = fetch_safe_assoc(dbQuery($itemQ));
    $updateQ = "UPDATE artItems SET";

    $itemPrice = $itemR['sale_price'];
    if($item['type'] == 'print') {
        $itemPrice = $itemPrice * $item['qty'];
        $itemQty = $item['qty'];
        $updateQ .= " quantity=" . ($itemR['quantity'] - $itemQty);
    } else {
        $itemPrice = $item['bid'];
        $itemQty = 1;
        if($item['depart'] == "staying") {
            $updateQ .= " final_price=$itemPrice, bidder=$transUser, status='Quicksale/Sold'";
        } else if($item['depart'] == 'departing') {
            $updateQ .= " final_price=$itemPrice, bidder=$transUser, status='purchased/released'";
        }
    }
    $updateQ .= " WHERE id=". $itemR['id'] . ";";
    dbQuery($updateQ);

    $totalPrice += $itemPrice;

    if(!$firstItem) { $saleQ .= ","; }
    else { $firstItem=false; }
    $saleQ .= " ($transI, " . $itemR['id'] . ", $transUser, $itemPrice, $itemQty )";
}
$saleQ .= ";";

dbInsert($saleQ);
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

$transUQ = "UPDATE transaction SET price=$totalPrice, tax=$totalTax, withtax=$totalWT, paid=$amount, change_due=$change WHERE id=$transI";

$paymentQ = "INSERT INTO payments"
    . " (transid, type, category, description, source, amount, cashier)"
    . " VALUES"
    . " ($transI, '$type', 'artshow', '$desc', 'Atcon Artshow', $amount, $purchaseUser);";

dbQuery($transUQ);
if($type == 'credit') {
    $paymentQ = "UPDATE payments SET"
        . " transid = $transI, description = '$desc'"
        . " WHERE id=" . sql_safe($_POST['payment']) . ";";
    dbQuery($paymentQ);
} else { dbInsert($paymentQ); }

ajaxSuccess($response);
?>
