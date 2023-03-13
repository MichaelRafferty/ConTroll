<?php

require("lib/base.php");

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}

$con = get_conf('con');
$conid = $con['id'];
$method = 'manager';
$page = "Atcon Administration";

if (!check_atcon($method, $conid)) {
    header('Location: /index.php');
    exit(0);
}

page_init($page, 'admin',
    /* css */ array('https://unpkg.com/tabulator-tables@5.4.4/dist/css/tabulator.min.css',
                    'https://unpkg.com/tabulator-tables@5.4.4/dist/css/tabulator_bootstrap5.min.css',
                    'css/registration.css'),
    /* js  */ array('https://unpkg.com/tabulator-tables@5.4.4/dist/js/tabulator.min.js',
                    'js/admin.js')
    );

//var_dump($_SESSION);
//echo $conid;

?>
<div class='container-fluid mt-4'>
    <div class="row">
        <div class="col-sm-auto table-bordered table-sm" id="userTab"></div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-4">
            <button type='button' class='btn btn-secondary btn-sm' id='add_user_btn' onclick='addUser();'>Add User</button>
            <button type='button' class='btn btn-secondary btn-sm' id='undo_btn' onclick='undo();' disabled>Undo</button>
            <button type='button' class='btn btn-secondary btn-sm' id='redo_btn' onclick='redo();' disabled>Redo</button>
            <button type='button' class='btn btn-primary btn-sm' id='save_btn' onclick='save();' disabled>Save</button>
        </div>
    </div>
</div>
<div id='addUser' class='container-fluid mt-4' hidden>
    <div class='row'>
        <div class='col-sm-6'>
            <div class='form-floating mb-3'>
                <input type='text' name='name_search' id='name_search' class='form-control' oninput="search_name_changed();"
                       placeholder='First and Last Name Fragment' required/>
                <label for='name_search'>User to Add: (Type parts of first and last name or enter the perid):</label>
            </div>
        </div>
    </div>
    <div class='row mb-2'>
        <div class='col-sm-4'>
            <button type='button' class='btn btn-primary btn-sm' id='search_btn' onclick='search();'>Search Users</button>
        </div>
    </div>
    <div class='row mt-2'>
        <div class='col-sm-auto table-bordered table-sm' id='searchTab'></div>
    </div>
</div>
<div id="result_message" class="mt-4 p-2"></div>
<pre id='test'></pre>
