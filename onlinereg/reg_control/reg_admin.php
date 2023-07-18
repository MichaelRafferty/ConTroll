<?php
global $db_ini;

require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "reg_admin";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init("Badge List",
    /* css */ array('https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css',
                    'css/base.css',
                    ),
    /* js  */ array(//'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js',
                    'js/base.js',
                    'js/reg_admin.js'),
                    $need_login);

?>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-auto me-1 p-0">
            <div id="category-table"></div>
        </div>
        <div class="col-sm-auto me-1  p-0">
            <div id="type-table"></div>
        </div>
        <div class="col-sm-auto me-1  p-0">
            <div id="age-table"></div>
        </div>
        <div class="col-sm-auto me-1  p-0">
            <div id="paid-table"></div>
        </div>
        <div class="col-sm-auto me-1 p-0">
            <div id="label-table"></div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto p-0">
            <div id="badge-table"></div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/allEmails.php';">Download Email List</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/regReport.php';">Download Reg Report</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="sendEmail('marketing')">Send Marketing Email</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="sendEmail('reminder')">Send Attendance Reminder Email</button>
        </div>       
        <?php if ($db_ini['con']['survey_url']) { ?>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="sendEmail('survey')">Send Survey Email</button>
        </div>
        <?php } ?>
        <?php if ($db_ini['reg']['cancelled']) { ?>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="sendCancel()">Send Cancelation Instructions</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/cancel.php';">Download Cancellation Report</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/processRefunds.php';">Download Process Refunds Report</button>
        </div>
        <?php } ?>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'>
</pre>
<?php

page_foot($page);
?>
