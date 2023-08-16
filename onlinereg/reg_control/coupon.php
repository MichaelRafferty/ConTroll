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
    /* css */ array('https://unpkg.com/tabulator-tables@5.5.1/dist/css/tabulator.min.css',
                    'css/base.css',
                    ),
    /* js  */ array(//'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.5.1/dist/js/tabulator.min.js',
                    'js/base.js',
                    'js/coupon.js'),
                    $need_login);

// first the modal for transfer to
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 p-0 m-0">
            <h4>Coupons:</h4>
        </div>
    </div>
    <div class="col-row" id="couponTable"></div>
    <div class="col-sm-12">
        <button id="coupon-undo" type="button" class="btn btn-secondary btn-sm" onclick="coupon.undo();" disabled>Undo</button>
        <button id="coupon-redo" type="button" class="btn btn-secondary btn-sm" onclick="coupon.redo();" disabled>Redo</button>
        <button id="coupon-addrow" type="button" class="btn btn-secondary btn-sm" onclick="coupon.addrow();">Add New</button>
        <button id="coupon-save" type="button" class="btn btn-primary btn-sm" onclick="coupon.save();" disabled>Save Changes</button>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'>
</pre>
<?php

page_foot($page);
?>
