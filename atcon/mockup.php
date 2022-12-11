<?php

require("lib/base.php");

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

//var_dump($_SESSION);
//echo $conid;

?>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-7">
            <div id="pos-tabs">
                 <ul class="nav nav-pills mb-2" id="reg-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="reg-tab" data-bs-toggle="pill" data-bs-target="#find-pane" type="button" role="tab" aria-controls="nav-reg" aria-selected="true" onclick-old="settab('reg-pane');">Find Membership</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-tab" data-bs-toggle="pill" data-bs-target="#add-pane" type="button" role="tab" aria-controls="nav-add" aria-selected="false" onclick-old="settab('add-pane');">Add Additional Memberships</button>
                    </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button" role="tab" aria-controls="nav-pay" aria-selected="false" onclick-ols="settab('pay-pane');">Payment</button>
                    </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link" id="print-tab" data-bs-toggle="pill" data-bs-target="#print-pane" type="button" role="tab" aria-controls="nav-print" aria-selected="false" onclick-old="settab('print-pane');">Print Badges</button>
                    </li>
                </ul>
                <div class="tab-content" id="reg-content">          
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
                        Add Additional Memberships
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
            <div id="cart">Cart is Empty</div>
            <div class="row">
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-success btn-small" id="complete_btn" disabled>Complete Transaction</button>
                    <button type="button" class="btn btn-primary btn-small" id="startover_btn" disabled>Start Over</button>
                    <button type="button" class="btn btn-warning btn-small" id="void_btn" disabled>Void</button>
                </div>
            </div>
        </div>       
    </div>
<pre id='test'></pre>
