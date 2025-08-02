<?php
// Vendor - switch Profile.php - switch to the alternate profile
require_once("lib/base.php");
global $config_vars;

$cc = get_conf('cc');
$con = get_conf('con');
$conid = $con['id'];
$debug = get_conf('debug');

$condata = get_con();

$in_session = false;
$regserver = getConfValue('reg','server');
$vendor = '';

if (isSessionVar('id') && isset($_GET['site'])) {
    $match = array();
    $match['id'] = getSessionVar('id');
    $match['eyID'] = getSessionVar('eyID');
    $match['loginType'] = getSessionVar('login_type');
    $match['eNeedNew'] = 0;
    $match['cNeedNew'] = 0;
    $match['archived'] = 'N';
    $match['ts'] = time();
    $string = json_encode($match);
    $string = encryptCipher($string, true);
    header("Location: " . $_GET['site'] . "/index.php?vid=$string");
    exit();
    }
echo "window.location='/index.php\n";
exit();
