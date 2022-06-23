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
    /* js  */ array('/javascript/d3.js',
                    'js/base.js'
                   ),
              $need_login);
if(isset($_GET) && isset($_GET['id'])) {
    $updateQ = "UPDATE reg SET paid = 0 WHERE create_trans=" . sql_safe($_GET['id']). ";";
    dbquery($updateQ);
}
?>
<div id='main'>
<form method='GET'>
<input name='id' type='text'></input>
<input type='submit' value='Set'></input>
</form>
</div>
