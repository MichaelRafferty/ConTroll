<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "reports";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array('js/d3.js',
                    'js/base.js'
                   ),
              $need_login);


$con = get_conf("con");
$control = get_conf("control");
$conid=$con['id'];

?>
<div id='main'>
  <a href='reports/website.php'>List of people for website</a><br/>
  <a href='reports/duplicates.php'>Duplicate Memberships</a><br/>
  <a href='reports/badgeTypes.php'>Badge Types</a><br/>
</div>
