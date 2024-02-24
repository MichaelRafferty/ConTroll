<?php
// Vendor - switch Profile.php - switch to the alternate profile
require_once("lib/base.php");
global $config_vars;

$cc = get_conf('cc');
$con = get_conf('con');
$conid = $con['id'];
$vendor_conf = get_conf('vendor');
$debug = get_conf('debug');
$ini = get_conf('reg');

$condata = get_con();

$in_session = false;
$forcePassword = false;
$regserver = $ini['server'];
$vendor = '';

// encrypt/decrypt stuff
$ciphers = openssl_get_cipher_methods();
$cipher = 'aes-128-cbc';
$ivlen = openssl_cipher_iv_length($cipher);
$ivdate = date_create("now");
$iv = substr(date_format($ivdate, 'YmdzwLLwzdmY'), 0, $ivlen);
$key = $conid . $con['label'] . $con['regadminemail'];

if (isset($_SESSION['id']) && isset($_GET['site'])) {
    $match = array();
    $match['id'] = $_SESSION['id'];
    $match['eyID'] = $_SESSION['eyID'];
    $match['loginType'] = $_SESSION['login_type'];
    $match['eNeedNew'] = 0;
    $match['cNeedNew'] = 0;
    $match['archived'] = 'N';
    $match['ts'] = time();
    $string = json_encode($match);
    $string = urlencode(openssl_encrypt($string, $cipher, $key, 0, $iv));
    header("Location: " . $_GET['site'] . "/index.php?vid=$string");
    exit();
    }
echo "window.location='/index.php\n";
exit();
