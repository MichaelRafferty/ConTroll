<?php
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
// onlinereg - base.php - base functions for online reg
require_once(__DIR__ . "/../../lib/db_functions.php");
require_once(__DIR__ . '/../../lib/ajax_functions.php');
require_once(__DIR__ . '/../../lib/global.php');
db_connect();

function ol_page_init($title, $js = '') {
    $includes = getTabulatorIncludes();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo $title; ?></title>
    <link rel='icon' type='image/x-icon' href='/lib/favicon.ico'>
    <link href='css/style.css' rel='stylesheet' type='text/css' />
    <link href='<?php echo $includes['jquicss'];?>' rel='stylesheet' type='text/css' />
    <link href='<?php echo $includes['bs5css'];?>' rel='stylesheet'/>
    <script src='<?php echo $includes['bs5js'];?>'></script>
    <script type='text/javascript' src='<?php echo $includes['jqjs']; ?>'></script>
    <script type='text/javascript' src='<?php echo $includes['jquijs']; ?>'></script>
    <script type='text/javascript' src='javascript/coupon.js'></script>
    <script type='text/javascript' src='javascript/store.js?v=1.1'></script>
<?php
    if ($js != '') {
        echo <<<EOF
<script type='text/javascript'>
$js;
</script>
EOF;
    }
    echo "\n</head>\n";
}
