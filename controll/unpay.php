<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "registration";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/base.css'
                   ),
    /* js  */ array('js/d3.js',
                   ),
              $need_login);
if(isset($_GET) && isset($_GET['id'])) {
    $updateQ = "UPDATE reg SET paid = 0, complete_trans = NULL WHERE create_trans=?;";
    dbSafeCmd($updateQ, 'i', array($_GET['id']));
}
?>
<div id='main'>
<form method='GET'>
<input name='id' type='text'/>
<input type='submit' value='Set'/>
</form>
</div>
