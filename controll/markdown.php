<?php
require_once "lib/base.php";
// show markdown pages
$need_login = google_init("page");

$page = "reports";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array(
                   ),
              $need_login);
// get md text either as string or file path
    if (array_key_exists('mdf', $_REQUEST)) {
        $mdf = $_REQUEST['mdf'];
        $mdt = file_get_contents($mdf, FILE_USE_INCLUDE_PATH);
        if ($mdt === false) {
            $mdt = "# Error: Cannot read markdown file $mdf\n";
        }
    } else if (array_key_exists('mdt', $_REQUEST)) {
        $mdt = $_REQUEST['mdt'];
    } else if (array_key_exists('mda', $_REQUEST)) {
        $mdt = base64_decode_url($_REQUEST['mda']);
    }

// create instance of markdown conversion
$parsedown = new Parsedown();
echo "<div class='container-fluid'>\n";
echo $parsedown->text($mdt) . PHP_EOL;
echo "</div>\n";
page_foot($page);