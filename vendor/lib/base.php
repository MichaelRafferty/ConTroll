<?php
// exhibitor - base.php - base functions for exhibitor reg
global $db_ini;
if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../../config/reg_conf.ini', true);
}

if ($db_ini['reg']['https'] <> 0) {
    if (!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

require_once(__DIR__ . "/../../lib/db_functions.php");
require_once(__DIR__ . '/../../lib/ajax_functions.php');
require_once(__DIR__ . '/../../lib/global.php');
require_once(__DIR__ . '/../../lib/cipher.php');
require_once(__DIR__ . '/../../lib/jsVersions.php');

db_connect();
session_start();

function exhibitor_page_init($title) {
$cdn = getTabulatorIncludes();
$tabbs5=$cdn['tabbs5'];
$tabcss=$cdn['tabcss'];
$tabjs=$cdn['tabjs'];
$bs5js=$cdn['bs5js'];
$bs5css=$cdn['bs5css'];
$jqjs=$cdn['jqjs'];
$jquijs=$cdn['jquijs'];
$jquicss=$cdn['jquicss'];

$vendor_conf = get_conf('vendor');
if (array_key_exists('customtext', $vendor_conf)) {
    $filter = $vendor_conf['customtext'];
} else {
    $filter = 'production';
}
loadCustomText('exhibitor', basename($_SERVER['PHP_SELF'], '.php'), $filter);

echo <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>$title</title>
    <link rel='icon' type='image/x-icon' href='/images/favicon.ico'>
    <link href='css/style.css' rel='stylesheet' type='text/css' />
    <link href='$jquicss' rel='stylesheet' type='text/css' /> 
    <link href='$tabcss' rel='stylesheet'>
    <link href='$bs5css' rel='stylesheet'>
    
    <script src='$bs5js'></script>
    <script type='text/javascript' src='$jqjs''></script>
    <script type='text/javascript' src='$jquijs'></script>
    <script type="text/javascript" src="$tabjs"></script>
    <script type='text/javascript' src='js/base.js'></script>
    <script type='text/javascript' src='js/vendor.js'></script>
    <script type='text/javascript' src='jslib/exhibitorProfile.js'></script>
    <script type='text/javascript' src='jslib/exhibitorRequest.js'></script>
    <script type='text/javascript' src='jslib/exhibitorReceipt.js'></script>
    <script type='text/javascript' src='js/vendor_invoice.js'></script>
    <script type='text/javascript' src='js/tinymce/tinymce.min.js'></script>
    <script type='text/javascript' src='js/auctionItemRegistration.js'></script>
</head>
EOF;
}

function vendor_page_foot() {
    ?>
    <div class="container-fluid">
        <div class='row mt-2'>
            <?php drawBug(12); ?>
        </div>
    </div>
    </body>
    </html>
    <?php
}

?>
