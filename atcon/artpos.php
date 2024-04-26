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
                                </div>
                                <div class="col-sm-8">
                                    <input type="number" class='no-spinners' inputmode="numeric" id="find_perid" name="find_perid" size="20" placeholder="Badge ID"/>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-8">
                                    <button type="button" class="btn btn-sm btn-primary" id="find_search_btn" name="find_btn" onclick="findPerson('search');">Find Person</button>
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
                        <div class='container-fluid' id="add-div">
                            <form id='add-form' name='add-form' onsubmit='return false;'>
                                <div class='row' id='add_header'>
                                    <div class='col-sm-12 text-bg-primary mb-2'>
                                        <div class='text-bg-primary m-2'>
                                            Add Additional Art to Cart
                                        </div>
                                    </div>
                                </div>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-2 p-0'>
                                        <label for='artistNumber' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Artist Number</span></label><br/>
                                        <input type='number' name='artistNumber' id='artistNumber' inputmode='numeric' class='no-spinners' style="width: 7em;" tabindex='21'/>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-2 p-0'>
                                        <label for='pieceNumber' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Piece Number</span></label><br/>
                                        <input type='number' name='pieceNumber' id='pieceNumber' inputmode='numeric' class='no-spinners' style='width: 4em;' tabindex='22'/>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-2 p-0'>
                                        <label for='unitNumber' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Unit Number</span></label><br/>
                                        <input type='number' name='unitNumber' id='unitNumber' inputmode='numeric' class='no-spinners' style='width: 4em;' tabindex='23'/>
                                    </div>
                                    <div class='col-sm-auto ms-2 me-2 p-0'>&nbsp;<br/>OR</div>
                                    <div class='col-sm-auto ms-2 me-0 p-0'>
                                        <label for='itemCode' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Item Code Scan</span></label><br/>
                                        <input type='text' name='itemCode' id='itemCode' size="15" maxlength="32" tabindex='24'/>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-sm-auto ms-0 me-0 p-0">
                                        <button class="btn btn-sm btn-primary" type='button' name='findArtBtn' id='findArtBtn' onclick="findArt('button')">Find Art to Add</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class='container-fluid' id='add-found-div'></div>
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
