<?php
// vendor - base.php - base functions for vendor reg
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

db_connect();
session_start();

date_default_timezone_set('America/New_York');

function vendor_page_init($title) {
echo <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>$title</title>
    <link href='css/style.css' rel='stylesheet' type='text/css' />
    <link href='css/jquery-ui-1.13.1.css' rel='stylesheet' type='text/css' />
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN' crossorigin='anonymous'>

    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js' integrity='sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL' crossorigin='anonymous'></script>
    <script type='text/javascript' src='js/jquery-min-3.60.js'></script>
    <script type='text/javascript' src='js/jquery-ui.min-1.13.1.js'></script>
    <script type='text/javascript' src='js/base.js'></script>
    <script type='text/javascript' src='js/vendor.js'></script>
    <script type='text/javascript' src='jslib/exhibitorProfile.js'></script>
    <script type='text/javascript' src='js/vendor_req.js'></script>
    <script type='text/javascript' src='js/vendor_invoice.js'></script>
    <script type='text/javascript' src='js/tinymce/tinymce.min.js'></script>
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
