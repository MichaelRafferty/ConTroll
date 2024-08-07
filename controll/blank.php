<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array('js/d3.js',
                   ),
              $need_login);
?>
<div id='main'>
</div>
