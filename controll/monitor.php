<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "monitor";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('css/d3.css',
                    'css/base.css'
                   ),
    /* js  */ array('js/d3.js',
                    'js/lodash.min.js',
                    'js/monitor.js'
                   ),
              $need_login);
$con = get_conf("con");
$conid=$con['id'];
$minComp = $con['minComp'];
?>
<div id='main'>
    <select id='conid' name='conid'>
      <?php 
        $conList = dbQuery("SELECT id FROM conlist WHERE"
            . " id >= $minComp and id <= $conid"
            . " ORDER BY id DESC");
        while($conid = fetch_safe_array($conList)) {
            echo "<option>" . $conid[0] . "</option>";
        }
      ?>
    </select>
    <button onClick='getBreakdown()'>Get Con</button>
    <div id='membershipBreakdown' class='half right' style='min-width:10%'>
    </div>
    <div class='half' id='graphs'>
    </div>
</div>
