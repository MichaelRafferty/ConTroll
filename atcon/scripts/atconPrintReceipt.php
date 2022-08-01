<?php
require("../lib/base.php");
require("../lib/ajax_functions.php");

$conf = get_conf('printers');
if(is_null($conf)) { 
	$response['error'] = "No Receipt Printer";
	ajaxSuccess($response);
	exit();
}
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

$details = callHome('getRegDetails.php', 'POST', $data);
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
$badgelist = array();
$badgestr = "";

$width = 30;

foreach($details['badgelist'] as $badge) {
    $p = '$' . ($badge['amount'] * $badge['count']);
    $l = $badge['count'] . 'x ' . '(' . $badge['action'] . ') ' . $badge['label'];
    $len = strlen($l);
    if($len > $width-strlen($p)-1) {
        $len = $width-strlen($p)-1;
        $l = substr($l, 0, $len);
    }
    $space = str_repeat(' ', $width - $len - strlen($p));
    $f = sprintf("%s%s%s\n", $l, $space, $p);
    array_push($badgelist, $f);
    fwrite($temp, $f);
}

fwrite($temp, closeReceipt($details['transinfo']));
fwrite($temp, "\n\n");
$response['transinfo'] = $details['transinfo'];

$response['badgeItems'] = $badgelist;
fclose($temp);
shell_exec("lpr -P$receipt $tempfile");
unlink($tempfile);

ajaxSuccess($response);

?>

