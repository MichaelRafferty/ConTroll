<?php
global $db_ini;
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "overview";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /*css*/ array('css/d3.css',
                'css/base.css'
            ),
    /*js*/  array('/javascript/d3.js',
                'js/base.js',
                'js/overview.js'
            ),
            $need_login);

?>
<div id='main'> 
    <div id='membershipBreakdown' class='half right'>
    </div>
    <div class='half'>
        <div id='membershipGrowth'>
            <span class='blocktitle'><?php echo $db_ini['con']['label'] ?> membership growth pre-con</span>
            <div id='membershipGrowthForm'>
            </div>
        </div>
        <div id='OverTime'>
            <span class='blocktitle'><?php echo $db_ini['con']['conname'] ?> membership over the years</span>
            <div id='OverTimeForm'>
            </div>
        </div>
    </div>
</div>
<div id='test'>
</div>
<?php

page_foot($page);

?>
