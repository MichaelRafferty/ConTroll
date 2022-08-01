<?php
require("../lib/base.php");
require("../lib/ajax_functions.php");

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

$response = array('post'=>$_POST, 'get'=>$_GET);
$transid=$_POST['transid'];
$response['transid']=$transid;

$details = callHome('getArtDetails.php', 'POST', $data);
$response['response']=$details;
$details = json_decode($details, true);

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

$artlist = array();
$artstr = "";

$width = 30;

foreach($details['artlist'] as $art) {
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

fwrite($temp, closeReceipt($details['transinfo']));
$response['transinfo'] = $details['transinfo'];

$response['artItems'] = $artlist;
fclose($temp);
shell_exec("lp -d $receipt $tempfile");
unlink($tempfile);

ajaxSuccess($response);

?>

