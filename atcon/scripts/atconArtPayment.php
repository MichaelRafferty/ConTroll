<?php
require("../lib/base.php");
require("../lib/ajax_functions.php");
require("../lib/cc.php");

header('content-type: application/json');

$paymentResponse = array();
$con = get_conf('con');
if(isset($_POST) && isset($_POST['type']) && $_POST['type'] == 'credit') {
    $amount = $_POST['amount'];

    $paymentResponse = sendCC($amount, $_POST['track'], 'artshow');

    unset($_POST['track']);

    if(!$paymentResponse['success']) {
        ajaxSuccess($paymentResponse);
        exit();
    }
} else {
    unset($paymentResponse);
}

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

    if(isset($paymentResponse)) {
        $data .= "&payment=" . $paymentResponse['payment'];
    }

    $response = json_decode(callHome("atconArtPayment.php", "POST", $data), true);
    if(isset($paymentResponse)) {
        $response['payment'] = $paymentResponse['payment'];
        if(isset($paymentResponse['error'])) {
            $response['payError'] = $paymentResponse['error']; 
        }
    }

    ajaxSuccess($response);
?>

