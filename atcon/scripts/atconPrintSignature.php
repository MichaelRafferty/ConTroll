<?php
require("../lib/base.php");
require("../lib/ajax_functions.php");
require("../lib/cc.php");

$conf = get_conf('printers');
$receipt = $conf['receipt'];

if(isset($_POST) && count($_POST)>0) {
    $method='POST';
    $data = "user=".$_SESSION['user']."&passwd=".$_SESSION['passwd'];
    foreach ($_POST as $key => $value) {
        $data .= "&$key=$value";
    }
} else {
    $data = "user=".$_SESSION['user']."&passwd=".$_SESSION['passwd'];
    foreach ($_GET as $key => $value) {
        $data .= "&$key=$value";
    }
}

    header('content-type: application/json');
    $result = json_decode(callHome("atconPrintSignature.php", "POST", $data), true);

    $ccinfo = array('ssl_card_number' => $result['ccinfo']['cc'],
                    'ssl_txn_id' => $result['ccinfo']['cc_txn_id'],
                    'ssl_approval_code' => $result['ccinfo']['cc_approval_code'],
                    'ssl_txn_time' => $result['ccinfo']['txn_time'],
                    'ssl_amount' => $result['ccinfo']['amount']);

    $tempfile = tempnam(sys_get_temp_dir(), 'prnCtrl');
    if(!$tempfile) {
        $resp['error'] = "Print Failure: tempnam error: " . error_get_last();
        return($resp);
    }

    $temp = fopen($tempfile, "w");
    if(!$temp) {
        $resp['error'] = "Print Failure: Fopen error: " . error_get_last();
        return($resp);
    }

    fwrite($temp, buildSalesDraft($ccinfo));
    fclose($temp);

    shell_exec("lp -d $receipt $tempfile");
    sleep(5);
    unlink($tempfile);
    
    ajaxSuccess($ccinfo);

?>

