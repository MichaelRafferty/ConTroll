<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "vendor";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css',
                    'css/base.css'
                   ),
    /* js  */ array(
                    //'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js',
                    //'/javascript/d3.js',
                    'js/base.js',
                    'js/vendor.js'
                   ),
              $need_login);

$con = get_con();
$conid = $con['id'];

$conf = get_conf('con');
// first the modals for use by the script
?>
<div id='update_profile' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Update Vendor Profile' aria-hidden='true' style="--bs-modal-width: 80%;">
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong>Update Vendor Profile</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <form id='vendor_update' action='javascript:void(0)'>
                        <input type="hidden" name="vendorId" id="ev_vendorId" value="">
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='ev_name'>Name:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='name' id='ev_name' size='64' required/>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='ev_email'>Email:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='email' id='ev_email' size='64' required/>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='ev_website'>Website:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='website' id='ev_website' required/>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='ev_description'>Description:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <textarea class='form-control-sm' name='description' id='ev_description' rows=5 cols=60></textarea>
                            </div>
                        </div>
                        <div class='row mt-1'>
                            <div class='col-sm-2 p-0 ms-0 me-0 pe-2 text-end'>
                                <input class='form-control-sm' type='checkbox' name='publicity' id="ev_publicity"/>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0'>
                                <label for="ev_publicity">Check if we may use your information to publicize your attendence at <?php echo $conf['conname']; ?></label>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="ev_addr" title='Street Address'>Address </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='ev_addr' type='text' size="64" name='addr' required/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="ev_addr2" title='Company Name'>Company/ Address line 2:</label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='ev_addr2' type='text' size="64" name='addr2'/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="ev_city">City: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='ev_city' type='text' size="32" name=' city' required/>
                            </div>
                            <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                <label for="ev_state"> State: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                <input class="form-control-sm" id='ev_state' type='text' size="2" maxlength="2" name='state' required/>
                            </div>
                            <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                <label for="ev_zip"> Zip: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                <input class="form-control-sm" id='ev_zip' type='text' size="11" maxlength="11" name='zip' required/>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' onClick='updateProfile()'>Update</button>
            </div>
        </div>
    </div>
</div>
    <div class="row">
        <div class="col-sm-12">
            <div id='summary-div'></div>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-12'>
            <div id="VendorList">Vendor List Placeholder</div>
        </div>
    </div>
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <div id="SpaceDetail">Space Detail Placeholder</div>
        </div>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
    <pre id='test'></pre>
<?php

page_foot($page);
?>
