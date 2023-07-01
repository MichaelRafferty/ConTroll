<?php

require_once "lib/base.php";

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
    /* css */ array('https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css',
                    'https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_bootstrap5.min.css',
                    'css/registration.css'),
    /* js  */ array('https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js',
                    'js/admin.js')
    );

//var_dump($_SESSION);
//echo $conid;

?>
<ul class='nav nav-tabs mb-3' id='admin-tab' role='tablist'>
    <li class='nav-item' role='presentation'>
        <button class='nav-link active' id='users-tab' data-bs-toggle='pill' data-bs-target='#users-pane' type='button'
                role='tab' aria-controls='nav-users' aria-selected='true' onclick="settab('users-pane');">Users
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='printers-tab' data-bs-toggle='pill' data-bs-target='#printers-pane' type='button'
                role='tab' aria-controls='nav-printers' aria-selected='false' onclick="settab('printers-pane');">Printers
        </button>
    </li>
</ul>
<div class='tab-content' id='admin-content'>
    <div class='tab-pane fade show active' id='users-pane' role='tabpanel' aria-labelledby='users-tab' tabindex='0'>
        <div class='container-fluid mt-4'>
            <div class="row">
                <div class="col-sm-auto table-bordered table-sm" id="userTab"></div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4">
                    <button type='button' class='btn btn-secondary btn-sm' id='users_add_user_btn' onclick='users.addUser();'>Add User</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='users_undo_btn' onclick='users.undo();' disabled>Undo</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='users_redo_btn' onclick='users.redo();' disabled>Redo</button>
                    <button type='button' class='btn btn-primary btn-sm' id='users_save_btn' onclick='users.save();' disabled>Save</button>
                </div>
            </div>
        </div>
        <div id='addUser' class='container-fluid mt-4' hidden>
            <div class='row'>
                <div class='col-sm-6'>
                    <div class='form-floating mb-3'>
                        <input type='text' name='name_search' id='name_search' class='form-control' oninput="users.search_name_changed();"
                               placeholder='First and Last Name Fragment' required/>
                        <label for='name_search'>User to Add: (Type parts of first and last name or enter the perid):</label>
                    </div>
                </div>
            </div>
            <div class='row mb-2'>
                <div class='col-sm-4'>
                    <button type='button' class='btn btn-primary btn-sm' id='users_search_btn' onclick='users.search();'>Search Users</button>
                </div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-auto table-bordered table-sm' id='searchTab'></div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='printers-pane' role='tabpanel' aria-labelledby='printers-tab' tabindex='0'>
        <div class='container-fluid'>
            <div class='row'>
                <div class='col-sm-auto'><h2>Print Servers</h2></div>
            </div>
            <div class='row'>
                <div class='col-sm-auto table-bordered table-sm' id='serversTable'></div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-4'>
                    <button type='button' class='btn btn-secondary btn-sm' id='servers_add_btn' onclick='printers.addServer();'>Add Server</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='servers_undo_btn' onclick='printers.undo_server();' disabled>Undo</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='servers_redo_btn' onclick='printers.redo_server();' disabled>Redo</button>
                </div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-auto'><h2>Printers</h2></div>
            </div>
            <div class='row'>
                <div class='col-sm-auto table-bordered table-sm' id='printersTable'></div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-4'>
                    <button type='button' class='btn btn-secondary btn-sm' id='printers_add_btn' onclick='printers.addPrinter();'>Add Printer</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='printers_undo_btn' onclick='printers.undo_printer();' disabled>Undo</button>
                    <button type='button' class='btn btn-secondary btn-sm' id='printers_redo_btn' onclick='printers.redo_printer();' disabled>Redo</button>
                    <button type='button' class='btn btn-primary btn-sm' id='printers_save_btn' onclick='printers.save();' disabled>Save</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="result_message" class="mt-4 p-2"></div>
<pre id='test'></pre>
