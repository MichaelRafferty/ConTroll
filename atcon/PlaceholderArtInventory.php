<?php

require("lib/base.php");

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}
$tab = 'Art Inventory';
$page = "Atcon Art Inventory";
$mode = 'inventory';
/*
if (isset($_GET['mode'])) {
    if ($_GET['mode'] == 'cashier') {
        $mode = 'cashier';
    }
    if ($mode == 'cashier') {
        $tab = 'mockupCashier';
    }
}
*/

$cdn = getTabulatorIncludes();
page_init($page, $tab,
    /* css */ array($cdn['tabcss'],
            // $cdn['tabbs5'],
		    'css/atcon.css','css/registration.css','css/mockup.css'),
    /* js  */ array( //$cdn['luxon'],
            $cdn['tabjs'],'js/atcon.js','js/mockup2.js')
    );

db_connect();

$con = get_conf("con");
$conid=$con['id'];
$label = $con['label'];
$conInfoQ = <<<EOS
SELECT DATE(startdate) as start, DATE(enddate) as end
FROM conlist
WHERE id=?;
EOS;

$conInfoR = dbSafeQuery($conInfoQ, 'i', array($conid));
$conInfo = $conInfoR->fetch_assoc();
$startdate = $conInfo['start'];
$enddate = $conInfo['end'];
$method='manager';

var_dump($_SESSION);
echo $conid;

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
                                    Search for:
                                </div>
                                <div class="col-sm-8">
                                    <input type="text" id="find_pattern" name="find_name" maxlength="50" size="50" placeholder="Name or Portion of Name, Perid or TransID"/>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-4">
                                      <?php if ($mode == 'cashier') { ?>
                                    <button type="button" class="btn btn-sm btn-primary" id="find_unpaid_btn" onclick="find_record('unpaid');" hidden>Find Unpaid Transactions</button>
                                    <?php } ?>
                                </div>
                                <div class="col-sm-8">
                                    <button type="button" class="btn btn-sm btn-primary" id="find_search_btn" onclick="find_record('search');">Find Record</button>
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
                                    <input type="text" name="state" id='state' size="10" maxlength="16" tabindex="8"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="zip" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Zip</span></label><br/>
                                    <input type="text" name="zip" id='zip' size="10" maxlength="10" tabindex="9"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="country" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label><br/>
                                    <select name='country' id="country" tabindex='10'>
                                    <?php
                                    $fh = fopen(__DIR__ . '/../lib/countryCodes.csv',"r");
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
                                    <input type="email" name="email" id='email' size="50" maxlength="254" tabindex="11"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="phone" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label><br/>
                                    <input type="text" name="phone" id='phone' size="15" maxlength="15" tabindex="13"/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="badgename" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Badge Name (optional)</span></label><br/>
                                    <input type="text" name="badgename" id='badgename' size="35" maxlength="32"  placeholder='defaults to first and last name' tabindex="14"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="memType" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Membership Type</span></label><br/>
                                    <div id="ae_mem_select"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-auto mt-2 ms-0 me-0 p-0">
                                    Include in annual reminder postcards, future Philcon emails and surveys? 
                                    <select id="contact_ok" name="contact_ok" tabindex='16'>
                                        <option value="Y" selected>Yes</option>
                                        <option value="N">No</option>
                                    </select>
                                </div>
                            </div>
                              <div class="row">
                                <div class="col-sm-auto mt-2 ms-0 me-0 p-0">
                                    Allow search by member to find you on website? 
                                    <select id="share_reg" name="share_reg" tabindex='16'>
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
                                    <button type="button" class="btn btn-primary btn-sm" id="addnew-btn" onclick="add_new();">Add to Cart</button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="clearadd-btn" onclick="clear_add();">Clear Add Person Form</button>
                                </div>
                            </div>
                        </div>
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
                    <button type="button" class="btn btn-success btn-sm" id="complete_btn" onclick="complete_over();" hidden>Complete Transaction</button>
                    <button type="button" class="btn btn-primary btn-sm" id="review_btn" onclick="start_review();" hidden>Review Data</button>
                    <button type="button" class="btn btn-warning btn-sm" id="startover_btn" onclick="start_over(1);" hidden>Start Over</button>
                    <button type="button" class="btn btn-warning btn-sm" id="void_btn" onclick="void_trans();" hidden>Void</button>
                    <button type="button" class="btn btn-primary btn-sm" id="next_btn" onclick="start_over(0);" hidden>Next Customer</button>
                </div>
            </div>
        </div>       
    </div>
<pre id='test'></pre>
