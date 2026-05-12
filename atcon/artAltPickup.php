<?php

require_once "lib/base.php";

if (!isSessionVar('user')) {
    header('Location: /index.php');
    exit(0);
}

$con = get_conf('con');
$conid = $con['id'];
$method = 'artsales';
$page = 'Art Show Alt Pickup Person Auth';

if (!check_atcon($method, $conid)) {
    header('Location: /index.php');
    exit(0);
}

$config_vars = array ();
$currency = getConfValue('con', 'currency', 'USD');
$locale = getLocale();
$config_vars['locale'] = $locale;
$config_vars['currency'] = $currency;
$config_vars['conid'] = $conid;

$cdn = getTabulatorIncludes();
page_init($page, 'artAltPickup',
    /* css */ array($cdn['tabcss'], $cdn['tabbs5']),
    /* js  */ array( ///$cdn['luxon'],
        $cdn['tabjs'],
        'js/artAltPickup.js'),
        $config_vars
);

?>
<!--- add new pickup person modal -->
<div class='modal modal-lg' id='AddNewPickup' tabindex='-5' aria-labelledby='AddNewPickup' data-bs-backdrop='static'
     data-bs-keyboard='false' aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title' id='AddNewPickupTitle'>
                    Add New Art Pickup Person
                </div>
            </div>
            <div class='modal-body' id='AddNewPickupBody'>
                <div class='row mt-3'>
                    <div class='col-sm-5'>
                        <label for="bidderId">Bidders Badge ID:</label>
                    </div>
                    <div class='col-sm-auto'>
                        <input type='number' class='no-spinners' inputmode='numeric' id='bidderPerid' name='bidderPerid' size='20'
                               placeholder='Scan Badge ID' onchange="altPickupAuth.addNewBidderCheck();">
                    </div>
                </div>
                <div class="row>">
                    <div class="col-sm-12" id="addNewBidderName"></div>
                </div>
                <div class='row mt-3'>
                    <div class='col-sm-5'>
                        <label for='bidderId'>Pickup Person Badge ID:</label>
                    </div>
                    <div class='col-sm-auto'>
                        <input type='number' class='no-spinners' inputmode='numeric' id='pickupPerid' name='pickupPerid' size='20'
                               placeholder='Scan Badge ID' onchange="altPickupAuth.addNewPickupCheck();">
                    </div>
                </div>
                <div class='row>'>
                    <div class='col-sm-12' id='addNewPickupName'></div>
                </div>
            </div>
            <div id='addNewMessage' class='mt-4 p-2'></div>
            <div class='modal-footer'>
                <button type='button' id='canceAddNewPickupBtn' class='btn btn-secondary' onclick='altPickupAuth.addNewClose();'>
                    Cancel
                </button>
                <button type='button' id='addNewBtn' class='btn btn-primary' onclick='altPickupAuth.addNewPickup();'>
                    Add New Pickup Person
                </button>
            </div>
        </div>
    </div>
</div>
<div class='container-fluid mt-4'>
    <div class="row mt-2">
        <div class="col-sm-12">
            <h1 class="size-h4">Art Show Alternate Pickup Authorizations</h1>
        </div>
    </div>
    <div class="row mt-1">
        <div class="col-sm-12" id="pickupAuthTable"></div>
    </div>
    <div class='row mt-2 mb-3' id='artalt-csv-div'>
        <div class='col-sm-auto p-1 ps-3 pe-3 tabulator-paginator paginationBGColor' id='tabPaginationDiv'></div>
        <div class='col-sm-auto p-1 ms-4' id='admin-buttons'>
            <button type='button' class='btn btn-secondary btn-sm' id='artalt_add_pickup_btn' onclick='altPickupAuth.addnew();'>Add Pickup Person</button>
            <button type='button' class='btn btn-secondary btn-sm' id='artalt_undo_btn' onclick='altPickupAuth.undo();' disabled>Undo</button>
            <button type='button' class='btn btn-secondary btn-sm' id='artalt_redo_btn' onclick='altPickupAuth.redo();' disabled>Redo</button>
            <button type='button' class='btn btn-primary btn-sm' id='artalt_save_btn' onclick='altPickupAuth.save();' disabled>Save</button>
            <button id='artaltt_csv_btn' type='button' class='btn btn-info btn-sm'
                    onclick='altPickupAuth.download("csv"); return false;'>Download CSV</button>
            <button id='artalt_xlsx_btn' type='button' class='btn btn-info btn-sm'
                    onclick='altPickupAuth.download("xlsx"); return false;'>Download Excel</button>
        </div>
    </div>
</div>
<div id='result_message' class='mt-4 p-2'></div>
<pre id='test'></pre><?php
page_foot();
