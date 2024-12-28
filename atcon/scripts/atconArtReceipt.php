<?php
require("../lib/base.php");

function initReceipt() {
    $con = get_conf('con');
    $width = 30;
    $pad = floor($width / 2 + strlen("Receipt") / 2);
    $return = "\n" . sprintf("%${pad}s", "Receipt") . "\n";
    $pad = floor($width / 2 + strlen($con['label']) / 2);
    $return .= "\n" . sprintf("%${pad}s", $con['label']) . "\n";

    $date = date("M j, Y H:m:s");
    $pad = floor($width / 2 + strlen($date) / 2);
    $return .= sprintf("%${pad}s", $date) . "\n";

    $return .= "\n" . str_repeat('-', $width) . "\n";

    return $return;
}

function closeReceipt($info) {
    $atcon_info = get_conf('atcon');

    $width = 30;

    $type = $info['type'];
    $sub = $info['price'];
    $tax = $info['tax'];
    $total = $info['withtax'];
    $amt = $info['amount'];
    $change = $info['change_due'];
    $desc = $info['description'];
    $cc_num = $info['cc'];
    $cc_code = $info['cc_approval_code'];

    $return = "\n";
    if ($sub > 0) {
        $subStr = sprintf("%01.2f", $sub);
        $pad = $width - strlen("Subtotal:");
        $return .= "Subtotal:" . sprintf("%${pad}s", $subStr) . "\n";

        $subTax = sprintf("%01.2f", $tax);
        $pad = $width - strlen("+ Tax:");
        $return .= "+ Tax:" . sprintf("%${pad}s", $subTax) . "\n";
    }

    $subTotal = sprintf("%01.2f", $total);
    $pad = $width - strlen("Total:");
    $return .= "Total:" . sprintf("%${pad}s", $subTotal) . "\n";

    $subAmt = sprintf("%01.2f", $amt);
    $pad = $width - strlen("Payment Received: $type");
    $return .= "Payment Received" . sprintf("%${pad}s", $subAmt) . "\n";
    if ($type == "check") {
        $return .= "  - Check #$desc\n";
    }
    if ($type == "credit") {
        $return .= "  - Card: " . $cc_num . "\n";
        $return .= "  - Auth: " . $cc_code . "\n";
    }
    $return .= "\n";

    $subCng = sprintf("%01.2f", $change);
    $pad = $width - strlen("Change:");
    $return .= "Change:" . sprintf("%${pad}s", $subCng) . "\n";
    $return .= "\n\n" . $atcon_info['endnote'] . "\n\n\n\n";

    return $return;
}


$response = array('post'=>$_POST, 'get'=>$_GET, 'session'=>$_SESSION);
$transid=$_POST['transid'];
$response['transid']=$transid;

$artQ = <<<EOS
SELECT S.amount, S.quantity, I.title, I.item_key, A.art_key
FROM artSales S
JOIN artItems I ON (I.id=S.artid)
JOIN artshow A ON (A.id=I.artshow)
WHERE S.transid=?;
EOS;

$artR = dbSafeQuery($artQ, 'i', array($transid));

$artList = array();
while($art = $artR->fetch_assoc()) {
    array_push($artList, $art);
}

$response['artlist'] = $artList;

$transQ = <<<EOS
SELECT T.price, T.paid, T.withtax, T.tax, T.change_due, ROUND(T.withtax + T.change_due,2) as amount, P.type, P.description, P.cc, P.cc_approval_code
FROM transaction T
JOIN payments P ON (P.transid=T.id)
WHERE T.id=?;
EOS;

$transR = dbSafeQuery($transQ, 'i', array($transid));

$transinfo = $transR->fetch_assoc();
$response['transinfo'] = $transinfo;

$tempfile = tempnam(sys_get_temp_dir(), 'prnCtrl');
if(!$tempfile) {
    print "FATAL: Unable to create unique file name<br/>\n";
    print_r(error_get_last());
    return false;
}

$temp = fopen($tempfile, "w");
if(!$temp) {
    print "FATAL: Unable to create temp file<br/>\n";
    print_r(error_get_last());
    return false;
}

fwrite($temp, initReceipt());

$artstr = "";

$width = 30;

foreach($artList as $art) {
    $p = '$' . ($art['amount']);
    $l = $art['quantity'] . 'x ' . $art['art_key'] . '-' . $art['item_key'] 
        . ' ' . $art['title'];
    $len = strlen($l);
    if($len > $width-strlen($p)-1) {
        $len = $width-strlen($p)-1;
        $l = substr($l, 0, $len);
    }
    $space = str_repeat(' ', $width - $len - strlen($p));
    $f = sprintf("%s%s%s", $l, $space, $p);
    array_push($artlist, $f);
    fwrite($temp, $f);
}

fwrite($temp, closeReceipt($transinfo));

fclose($temp);

$result_code=-5;
$command = '';
if (isset($_SESSION['receiptPrinter'])) {
    $printer = $_SESSION['receiptPrinter'];
    $server = $printer['host'];
    $queue = $printer['queue'];
    $codepage = $printer['code'];
    $serverArg = '';
    if ($server != '')
        $serverArg = "H$server";
    $command = "lpr $serverArg -P$queue < $tempfile";
    $response['command'] = $command;
} else {
    $command = "cat $tempfile";
    $response['command'] = $command;
}

$reesponse['result'] = shell_exec("$command");
unlink($tempfile);

ajaxSuccess($response);

?>
