<?php

require_once "lib/base.php";

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}

$con = get_conf('con');
$conid = $con['id'];
$tab = 'vol_roll';
$method='vol_roll';
$page = "Volunteer Rollover";

if (!check_atcon($method, $conid)) {
    header('Location: /index.php');
    exit(0);
}

page_init($page, $tab,
    /* css */ array('https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css','css/registration.css',
                    'https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator_bootstrap5.min.css'),
    /* js  */ array( //'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js','js/volRollover.js')
    );
?>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-7">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12 text-bg-primary mb-2">
                        <div class="text-bg-primary m-2">
                            Find member to rollover to <?PHP echo $conid + 1; ?>
                        </div>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-sm-4">
                        <label for="find_pattern" >Search for:</label>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" id="find_pattern" name="find_name" maxlength="50" size="50" placeholder="Perid, or Name or Portion of Name"/>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-sm-4">
                    </div>
                    <div class="col-sm-8">
                        <button type="button" class="btn btn-small btn-primary" id="find_search_btn" onclick="find_record();">Find Record</button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-sm-12 text-bg-secondary">
                        Search Results
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12" id="find_results">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-5">
            <div class='row'>
                <div class='col-sm-12 text-bg-primary mb-2'>
                    <div class='text-bg-primary m-2'>
                        Members Rolled Over (this session)
                    </div>
                </div>
            </div>
            <div id='list'></div>
            <div class="row">
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-warning btn-small" id="startover_btn" onclick="start_over(1);">Start Over</button>
                </div>
            </div>
        </div>       
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
<pre id='test'></pre><?php
page_foot();
