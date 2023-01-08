<?php

require("lib/base.php");
require_once("lib/mockup_db_functions.php");
require_once(__DIR__ . "/../lib/ajax_functions.php");

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}

$page = "Atcon New Workflow Mockup";

page_init($page, 'mockup',
    /* css */ array('https://unpkg.com/tabulator-tables@5.4.3/dist/css/tabulator.min.css','css/atcon.css','css/registration.css','css/mockup.css'),
    /* js  */ array( //'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.4.3/dist/js/tabulator.min.js','js/atcon.js','js/mockup.js')
    );

$con = get_conf("con");
$conid=$con['id'];
$method='manager';
db_connect();

//var_dump($_SESSION);
//echo $conid;

$membershiptypes = array();
$priceQ = <<<EOS
SELECT id, memGroup, label, shortname, sort_order, price
FROM memLabel
WHERE
    conid=?
    AND atcon = 'Y'
    AND startdate >= '2023-11-10'
    AND enddate > current_timestamp()
ORDER BY sort_order, price DESC
;
EOS;
$priceR = dbSafeQuery($priceQ, "i", array($conid));
while($priceL = fetch_safe_assoc($priceR)) {
    $membershiptypes[] = array('memGroup' => $priceL['memGroup'], 'shortname' => $priceL['shortname'], 'price' => $priceL['price'], 'label' => $priceL['label']);
}


?>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-7">
            <div id="pos-tabs">
                 <ul class="nav nav-pills mb-2" id="tab-ul" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="find-tab" data-bs-toggle="pill" data-bs-target="#find-pane" type="button" role="tab" aria-controls="nav-find" aria-selected="true" onclick-old="settab('find-pane');">Find People</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-tab" data-bs-toggle="pill" data-bs-target="#add-pane" type="button" role="tab" aria-controls="nav-add" aria-selected="false" onclick-old="settab('add-pane');">Add People</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="review-tab" data-bs-toggle="pill" data-bs-target="#review-pane" type="button" role="tab" aria-controls="nav-review" aria-selected="false" onclick-old="settab('review-pane');" disabled>Review Data</button>
                    </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button" role="tab" aria-controls="nav-pay" aria-selected="false" onclick-ols="settab('pay-pane');" disabled>Payment</button>
                    </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link" id="print-tab" data-bs-toggle="pill" data-bs-target="#print-pane" type="button" role="tab" aria-controls="nav-print" aria-selected="false" onclick-old="settab('print-pane');" disabled>Print Badges</button>
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
                                    Search by Name:
                                </div>
                                <div class="col-sm-8">
                                    <input type="text" id="find_name" name="find_name" maxlength="50" size="50" placeholder="Name or Portion of Name"/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-4">
                                    Search by Person id:
                                </div>
                                <div class="col-sm-8">
                                    <input type="number" id="find_perid" name="find_perid" class="no-spinners" size="6" placeholder="Person #" />
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-4">
                                    Search by Transaction id:
                                </div>
                                <div class="col-sm-8">
                                    <input type="number" id="find_transid" name="find_transid" class="no-spinners" size="6" placeholder="Transaction #"/>
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
                    <div class="tab-pane fade" id="add-pane" role="tabpanel" aria-labelledby="add-tab" tabindex="0">
                         <div class="container-fluid">
                            <div class="row" id="add_header">
                                <div class="col-sm-12 text-bg-primary mb-2">
                                    <div class="text-bg-primary m-2">
                                        Add New Person and Membership
                                    </div>
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="fname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>First Name</span></label><br/>
                                    <input class="form-control-sm" type="text" name="fname" id='fname' size="22" maxlength="32" tabindex="1"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="mname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Middle Name</span></label><br/>
                                    <input class="form-control-sm" type="text" name="mname" id='mname' size="8" maxlength="32" tabindex="2"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="lname" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Last Name</span></label><br/>
                                    <input class="form-control-sm" type="text" name="lname" id='lname' size="22" maxlength="32" tabindex="3"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="suffix" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Suffix</span></label><br/>
                                    <input class="form-control-sm" type="text" name="suffix" id='suffix' size="4" maxlength="4" tabindex="4"/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 ms-0 me-0 p-0">
                                    <label for="addr" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Address</span></label><br/>
                                    <input class="form-control-sm" type="text" name='addr' id='addr' size=64 maxlength="64" tabindex='5'/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 ms-0 me-0 p-0">
                                    <label for="addr2" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Company/2nd Address line</span></label><br/>
                                    <input class="form-control-sm" type="text" name='addr2' id='addr2' size=64 maxlength="64" tabindex='6'/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="city" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>City</span></label><br/>
                                    <input class="form-control-sm" type="text" name="city" id='city' size="22" maxlength="32" tabindex="7"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="state" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>State</span></label><br/>
                                    <input class="form-control-sm" type="text" name="state" id='state' size="2" maxlength="2" tabindex="8"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="zip" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Zip</span></label><br/>
                                    <input class="form-control-sm" type="text" name="zip" id='zip' size="5" maxlength="10" tabindex="9"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="country" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Country</span></label><br/>
                                    <select name='country' id="country" tabindex='10'>
                                        <option value="USA" default='true'>United States</option>
                                        <option value="CAN">Canada</option>
                                    <?php
                                    $fh = fopen("lib/countryCodes.csv","r");
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
                                    <label for="email1" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Email</span></label><br/>
                                    <input class="form-control-sm" type="email" name="email" id='email' size="35" maxlength="64" tabindex="11"/>
                                </div>
                                <div class="col-sm-6 ms-0 me-0 p-0">
                                    <label for="phone" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Phone</span></label><br/>
                                    <input class="form-control-sm" type="text" name="phone" id='phone' size="20" maxlength="15" tabindex="13"/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-auto ms-0 me-2 p-0">
                                    <label for="badgename" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;">Badge Name (optional)</span></label><br/>
                                    <input class="form-control-sm" type="text" name="badgename" id='badgename' size="35" maxlength="32"  placeholder='defaults to first and last name' tabindex="14"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0">
                                    <label for="memType" class="form-label-sm"><span class="text-dark" style="font-size: 10pt;"><span class='text-info'>*</span>Membership Type</span></label><br/>
                                    <select id='memType' name='age' style="width:300px;" tabindex='15' title='Age as of <?php echo substr($condata['startdate'], 0, 10); ?> (the first day of the convention)'>
                                        <?php foreach ($membershiptypes as $memType) { ?>
                                            <option value='<?php echo $memType['memGroup'];?>'><?php echo $memType['label']; ?> ($<?php echo $memType['price'];?>)</option>
                                        <?php    } ?>
                                    </select>
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
                                <div class="col-sm-12" id="add_results">
                            </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 mt-3">
                                    <button type="button" class="btn btn-primary btn-small" id="addnew-btn" onclick="add_new();">Add to Cart</button>
                                    <button type="button" class="btn btn-secondary btn-small" id="clearadd-btn" onclick="clear_add();">Clear Add Person Form</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="review-pane" role="tabpanel" aria-labelledby="review-tab" tabindex="0">
                        Review Data
                    </div>
                    <div class="tab-pane fade" id="pay-pane" role="tabpanel" aria-labelledby="pay-tab" tabindex="0">
                        Prrocess Payment
                    </div>
                    <div class="tab-pane fade" id="print-pane" role="tabpanel" aria-labelledby="print-tab" tabindex="0">
                        Print Badges
                    </div>
                 </div>
            </div>
        </div>
        <div class="col-sm-5">
            <div id="cart"></div>
            <div class="row">
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-success btn-small" id="complete_btn" onclick="complete_over();" hidden>Complete Transaction</button>
                    <button type="button" class="btn btn-primary btn-small" id="startover_btn" onclick="start_over();" hidden>Start Over</button>
                    <button type="button" class="btn btn-warning btn-small" id="void_btn" onclick="void ();" hidden>Void</button>
                </div>
            </div>
        </div>       
    </div>
<pre id='test'></pre>
