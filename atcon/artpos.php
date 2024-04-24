<?php

require_once "lib/base.php";

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}

$con = get_conf('con');
$conid = $con['id'];
$conname = $con['conname'];
$tab = 'artsales';
$mode = 'artsales';
$method='cashier';

$page = "Atcon POS ($tab)";

if (!check_atcon($method, $conid)) {
    header('Location: /index.php');
    exit(0);
}

$cdn = getTabulatorIncludes();
page_init($page, $tab,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5']),
    /* js  */ array( ///$cdn['luxon'],
                    $cdn['tabjs'], 'js/artpos_cart.js', 'js/artpos.js')
    );
?>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-6">
            <div id="pos-tabs">
                 <ul class="nav nav-pills mb-2" id="tab-ul" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="find-tab" data-bs-toggle="pill" data-bs-target="#find-pane" type="button" role="tab" aria-controls="nav-find" aria-selected="true">Find Customer</button>
                    </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-tab" data-bs-toggle="pill" data-bs-target="#add-pane" type="button" role="tab" aria-controls="nav-cart" aria-selected="false" disabled>Add Art to Cart</button>
                     </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button" role="tab" aria-controls="nav-pay" aria-selected="false" disabled>Payment</button>
                    </li>
                </ul>
                <div class="tab-content" id="find-content">          
                    <div class="tab-pane fade show active" id="find-pane" role="tabpanel" aria-labelledby="person-tab" tabindex="0">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-sm-12 text-bg-primary mb-2">
                                    <div class="text-bg-primary m-2">
                                        Find person buying the items
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-4">
                                    <label for="find_perid" >Search for:</label>
                                    <label for="find_perid" >Search forchang:</label>
                                </div>
                                <div class="col-sm-8">
                                    <input type="number" inputmode="numeric" id="find_perid" name="find_perid" size="20" placeholder="Badge ID"/>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-8">
                                    <button type="button" class="btn btn-sm btn-primary" id="find_search_btn" name="find_btn" onclick="find_person('search');">Find Person</button>
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
                        <div id="add-div"><h1>Add</h1></div>
                    </div>
                    <div class="tab-pane fade" id="pay-pane" role="tabpanel" aria-labelledby="pay-tab" tabindex="2">
                        <div id="pay-div">Process Payment</div>
                    </div>
                 </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div id="cart"></div>
            <div class="row">
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-primary btn-sm" id="add_btn" onclick="goto_add();" hidden>Add Art to Cart</button>
                    <button type="button" class="btn btn-primary btn-sm" id="pay_btn" onclick="goto_pay();" hidden>Pay Cart</button>
                    <button type="button" class="btn btn-warning btn-sm" id="startover_btn" onclick="start_over(1);" hidden>Start Over</button>
                    <button type="button" class="btn btn-primary btn-sm" id="next_btn" onclick="start_over(1);" hidden>Next Customer</button>
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
