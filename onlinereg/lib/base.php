<?php
require_once(__DIR__ . '/../../lib/global.php');
// portal - base.php - base functions for membership portal

if (loadConfFile())
    $include_path_additions = PATH_SEPARATOR . getConfValue('client', 'path', '.') . '/../Composer';

if (getConfValue('reg', 'https') <> 0) {
    if (!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// onlinereg - base.php - base functions for online reg
require_once(__DIR__ . "/../../lib/db_functions.php");
require_once(__DIR__ . '/../../lib/ajax_functions.php');
require_once(__DIR__ . '/../../lib/global.php');
require_once(__DIR__ . '/../../lib/jsVersions.php');
db_connect();

function ol_page_init($title, $js = '') {
    global $libJSversion, $globalJSversion, $onlineregJSversion;

    $cdn = getTabulatorIncludes();
    $tabbs5=$cdn['tabbs5'];
    $tabcss=$cdn['tabcss'];
    $tabjs=$cdn['tabjs'];
    $bs5js=$cdn['bs5js'];
    $bs5css=$cdn['bs5css'];
    $jqjs=$cdn['jqjs'];
    $jquijs=$cdn['jquijs'];
    $jquicss=$cdn['jquicss'];
    echo <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>$title</title>
    <link rel='icon' type='image/x-icon' href='/images/favicon.ico'>
    <link href='css/style.css' rel='stylesheet' type='text/css' />
    <link href="$jquicss" rel='stylesheet' type='text/css' />
    <link href='$bs5css' rel='stylesheet'/>
    <script src='$bs5js'></script>
    <script type='text/javascript' src="$jqjs"></script>
    <script type='text/javascript' src="$jquijs"></script>
    <script type='text/javascript' src="jslib/global.js?v=$globalJSversion"></script>
    <script type='text/javascript' src="jslib/coupon.js?v=$libJSversion"></script>
    <script type='text/javascript' src="javascript/store.js?v=$onlineregJSversion"></script>
EOF;
    if ($js != '') {
        echo <<<EOF
<script type='text/javascript'>
$js;
</script>
EOF;
    }
    echo "\n</head>\n";
}
