<?php

require_once "lib/base.php";

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}

$con = get_conf('con');
$conid = $con['id'];
$conname = $con['conname'];
$tab = 'checkin';
$mode = 'checkin';
$method='data_entry';
if (isset($_GET['mode'])) {
    if ($_GET['mode'] == 'cashier') {
        $mode = 'cashier';
        $method='cashier';
    }
    if ($mode == 'cashier') {
        $tab = 'cashier';
    }
}
$page = "Atcon POS ($tab)";

if (!check_atcon($method, $conid)) {
    header('Location: /index.php');
    exit(0);
}

page_init($page, $tab,
    /* css */ array('https://unpkg.com/tabulator-tables@5.5.1/dist/css/tabulator.min.css',
                    //'https://unpkg.com/tabulator-tables@5.5.1/dist/css/tabulator_bootstrap5.min.css',
                    'css/atcon.css',
                    'css/registration.css'),
    /* js  */ array( //'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.5.1/dist/js/tabulator.min.js','js/regpos_cart.js', 'js/regpos_coupon.js', 'js/regpos.js')
    );
?>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-7">
            <div id="pos-tabs">
                 <ul class="nav nav-pills mb-2" id="tab-ul" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="find-tab" data-bs-toggle="pill" data-bs-target="#find-pane" type="button" role="tab" aria-controls="nav-find" aria-selected="true">Find</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-tab" data-bs-toggle="pill" data-bs-target="#add-pane" type="button" role="tab" aria-controls="nav-add" aria-selected="false">Add/Edit</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="review-tab" data-bs-toggle="pill" data-bs-target="#review-pane" type="button" role="tab" aria-controls="nav-review" aria-selected="false" disabled>Review Data</button>
                    </li>
                     <li class="nav-item" role="presentation"<?php if ($mode != 'cashier') { echo ' style="display:none;"'; } ?>>
                        <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button" role="tab" aria-controls="nav-pay" aria-selected="false" disabled>Payment</button>
                    </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link" id="print-tab" data-bs-toggle="pill" data-bs-target="#print-pane" type="button" role="tab" aria-controls="nav-print" aria-selected="false" disabled>Print Badges</button>
                    </li>
                </ul>
                <div class="tab-content" id="find-content">          
                    <div class="tab-pane fade show active" id="find-pane" role="tabpanel" aria-labelledby="reg-tab" tabindex="0">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-sm-12 text-bg-primary mb-2">
                                    <div class="text-bg-primary m-2">
                                        Find record for person
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-4">
                                    <label for="find_pattern" >Search for:</label>
                                </div>
                                <div class="col-sm-8">
                                    <input type="text" id="find_pattern" name="find_name" maxlength="50" size="50" placeholder="Name/Portion of Name, Person (Badge) ID or TransID"/>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-4">
                                      <?php if ($mode == 'cashier') { ?>
                                    <button type="button" class="btn btn-small btn-primary" id="find_unpaid_btn" name="find_btn" onclick="find_record('unpaid');" hidden>Find Unpaid Transactions</button>
                                    <?php } ?>
                                </div>
                                <div class="col-sm-8">
                                    <button type="button" class="btn btn-small btn-primary" id="find_search_btn" name="find_btn" onclick="find_record('search');">Find Record</button>
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
                    <div class="tab-pane fade" id="add-pane" role="tabpanel" aria-labelledby="add-tab" tabindex="1">
                        <form id="add-edit-form" name="add-edit-form" onsubmit="return false;">
                         <div class="container-fluid">
                            <div class="row" id="add_header">
                                <div class="col-sm-12 text-bg-primary mb-2">
                                    <div class="text-bg-primary m-2">
                                        Add New Person and Membership
                                    </div>
                                </div>
                            </div>
                             <input type="hidden" name="perinfo-index" id="perinfo-index" />
                             <input type="hidden" name="perinfo-perid" id="perinfo-perid" />
                             <input type="hidden" name="membership-index" id="membership-index" />
                             <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="fname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>First Name</span></label><br/>
                                    <input type="text" name="fname" id='fname' size="22" maxlength="32" tabindex="1"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="mname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label><br/>
                                    <input type="text" name="mname" id='mname' size="6" maxlength="32" tabindex="2"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="lname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Last Name</span></label><br/>
                                    <input type="text" name="lname" id='lname' size="22" maxlength="32" tabindex="3"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="suffix" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Suffix</span></label><br/>
                                    <input type="text" name="suffix" id='suffix' size="4" maxlength="4" tabindex="4"/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 ms-0 me-0 p-0">
                                    <label for="addr" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Address</span></label><br/>
                                    <input type="text" name='addr' id='addr' size=64 maxlength="64" tabindex='5'/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 ms-0 me-0 p-0">
                                    <label for="addr2" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span></label><br/>
                                    <input type="text" name='addr2' id='addr2' size=64 maxlength="64" tabindex='6'/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="city" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>City</span></label><br/>
                                    <input type="text" name="city" id='city' size="22" maxlength="32" tabindex="7"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="state" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>State</span></label><br/>
                                    <input type="text" name="state" id='state' size="2" maxlength="2" tabindex="8"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="zip" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Zip</span></label><br/>
                                    <input type="text" name="zip" id='zip' size="10" maxlength="10" tabindex="9"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="country" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label><br/>
                                    <select name='country' id="country" tabindex='10'>
                                    <?php
                                    $fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
                                    while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
                                        echo "<option value='".$data[1]."'>".$data[0]."</option>";
                                    }
                                    fclose($fh);
                                    ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="email" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Email</span></label><br/>
                                    <input type="email" name="email" id='email' size="50" maxlength="64" tabindex="11"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="phone" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label><br/>
                                    <input type="text" name="phone" id='phone' size="15" maxlength="15" tabindex="13"/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="badgename" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Badge Name (optional)</span></label><br/>
                                    <input type="text" name="badgename" id='badgename' size="35" maxlength="32"  placeholder='Badgename: defaults to first and last name' tabindex="14"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="memType" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Membership Type</span></label><br/>
                                    <div id="ae_mem_select"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-auto mt-2 ms-0 me-0 p-0">
                                    <label for="contact_ok">Include in annual reminder postcards, future <?php echo $conname; ?> emails and surveys?</label>
                                    <select id="contact_ok" name="contact_ok" tabindex='16'>
                                        <option value="Y" selected>Yes</option>
                                        <option value="N">No</option>
                                    </select>
                                </div>
                            </div>
                              <div class="row">
                                <div class="col-sm-auto mt-2 ms-0 me-0 p-0">
                                    <label for="share_reg_ok">Allow search by member to find you on website?</label>
                                    <select id="share_reg_ok" name="share_reg_ok" tabindex='16'>
                                        <option value="Y" selected>Yes</option>
                                        <option value="N">No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12" id="add_results">
                            </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 mt-3">
                                    <button type="button" class="btn btn-primary btn-small" id="addnew-btn" name="find_btn" onclick="add_new();">Add to Cart</button>
                                    <button type="button" class="btn btn-secondary btn-small" id="clearadd-btn" onclick="clear_add();">Clear Add Person Form</button>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="review-pane" role="tabpanel" aria-labelledby="review-tab" tabindex="2">
                        <div id="review-div">Review Data</div>
                    </div>
                    <div class="tab-pane fade" id="pay-pane" role="tabpanel" aria-labelledby="pay-tab" tabindex="3">
                        <div id="pay-div">Process Payment</div>
                    </div>
                    <div class="tab-pane fade" id="print-pane" role="tabpanel" aria-labelledby="print-tab" tabindex="4">
                        <div id="print-div">Print Badges</div>
                    </div>
                 </div>
            </div>
        </div>
        <div class="col-sm-5">
            <div id="cart"></div>
            <div class="row">
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-primary btn-small" id="cart_no_changes_btn" onclick="review_nochanges();" hidden>No Changes</button>
                    <button type="button" class="btn btn-primary btn-small" id="review_btn" onclick="start_review();" hidden>Review Data</button>
                    <button type="button" class="btn btn-warning btn-small" id="startover_btn" onclick="start_over(1);" hidden>Start Over</button>
                    <button type="button" class="btn btn-warning btn-small" id="void_btn" onclick="void_trans();" hidden>Void</button>
                    <button type="button" class="btn btn-primary btn-small" id="next_btn" onclick="start_over(1);" hidden>Next Customer</button>
                </div>
            </div>
        </div>       
    </div>
    <!--- notes modal popup -->
    <div class='modal modal-lg' id='Notes' tabindex='-2' aria-labelledby='Notes' data-bs-backdrop='static' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <div class='modal-title' id="NotesTitle">
                        Member Notes
                    </div>
                </div>
                <div class='modal-body' id="NotesBody">
                </div>
                <div class='modal-footer'>
                    <button type='button' id="close_note_button" class='btn btn-primary' onclick="save_note();">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!--- change membership modal popup -->
    <div class='modal modal-lg' id='Change' tabindex='-3' aria-labelledby='Change' data-bs-backdrop='static' data-bs-keyboard='false' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <div class='modal-title' id='ChangeTitle'>
                        Change Membership Type
                    </div>
                </div>
                <div class='modal-body' id='ChangeBody'>
                </div>
                <div class='modal-footer'>
                    <button type='button' id='discard_change_button' class='btn btn-secondary' onclick='changeModal.hide();'>Keep Current Membership</button>
                    <button type='button' id='close_change_button' class='btn btn-primary' onclick='save_membership_change();'>Change Membership</button>
                </div>
            </div>
        </div>
    </div>
    <!--- pay cash change modal popup -->
    <div class='modal modal-lg' id='CashChange' tabindex='-4' aria-labelledby='CashChange' data-bs-backdrop='static' data-bs-keyboard='false' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <div class='modal-title' id='CashChangeTitle'>
                        Change due to Customer
                    </div>
                </div>
                <div class='modal-body' id='CashChangeBody'>
                </div>
                <div class='modal-footer'>
                    <button type='button' id='discard_cash_button' class='btn btn-secondary' onclick='cashChangeModal.hide();'>Cancel Cash Payment</button>
                    <button type='button' id='close_cash_button' class='btn btn-primary' onclick='pay("nomodal");'>Change given to Customer</button>
                </div>
            </div>
        </div>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'></pre><?php
page_foot();
