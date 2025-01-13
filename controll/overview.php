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
    /*js*/  array('js/d3.js',
                'https://cdn.plot.ly/plotly-2.35.2.min.js',
                'js/overview.js'
            ),
            $need_login);

$con = get_conf("con");
$conid=$con['id'];
$debug = get_conf('debug');
$config_vars['debug'] = $debug['controll_stats'];
$config_vars['conid'] = $conid;
$config_vars['minComp'] = $con['minComp'];
$config_vars['compLen'] = $con['compLen'];
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
</script>
<div id='main'>
    <div class='container-fluid'>
        <div class='row'>
            <div class="col-sm-8 p-0">
                <div class='container-fluid'>
                    <div class="row">
                        <div class="col-sm-12 p-0" id="AnnualMemberships"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 p-0" id="DailyTrend"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 p-0" id="membershipBreakdown"></div>
        </div>
        <div id='result_message' class='mt-4 p-2'></div>
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
