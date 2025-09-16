<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "monitor";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /*css*/ array(
                'css/d3.css',
                'css/base.css'
            ),
    /*js*/  array(
                'js/d3.js','js/lodash.min.js',

                'https://cdn.plot.ly/plotly-2.35.2.min.js',
                'js/monitor.js'
            ),
            $need_login);

$con = get_conf("con");
$conid=$con['id'];
$minComp = $con['minComp'];
$debug = get_conf('debug');
if(!array_key_exists('controll_stats', $debug)) { $debug['controll_stats']=0;}

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
	        <div class='col-sm-auto p-0'>
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
		    </div>
	    </div>
    </div>
    <div class='container-fluid'>
        <div class='row'>
            <div class="col-sm-8 p-0">
                <div class='container-fluid'>
                    <div class="row">
                        <div class="col-sm-12 p-0" id="tracker"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 p-0" id="throughput"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 p-0" id="staffing"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 p-0" id="membershipBreakdown"></div>
        </div>
        <div id='result_message' class='mt-4 p-2'></div>
    </div>
 </div>
<div id='test'>
</div>
<?php

page_foot($page);
