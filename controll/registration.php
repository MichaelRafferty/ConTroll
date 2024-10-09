<?php
require_once 'lib/base.php';
require_once '../lib/profile.php';
require_once '../lib/policies.php';
require_once('../lib/cc__load_methods.php');
require_once('../lib/profile.php');
require_once('../lib/policies.php');
//initialize google session
$need_login = google_init('page');

$page = 'registration';
if (!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page('index.php');
}
load_cc_procs();

$con_conf = get_conf('con');
$conid = $con_conf['id'];

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array('css/base.css', $cdn['tabcss'], $cdn['tabbs5'], 'css/registration.css'
    ),
    /* js  */ array(//$cdn['luxon'],
        $cdn['tabjs'],
        'js/registration.js',
        'jslib/posCart.js',
        'jslib/posCoupon.js',
        'jslib/pos.js',
        'jslib/membershipRules.js',
    ),
    $need_login);

$con = get_conf('con');
$debug = get_conf('debug');
$usps = get_conf('usps');
$conid = $con['id'];
$conname = $con['conname'];
$policies = getPolicies();
$useUSPS = false;
// form as laid out has no room for usps block, if we want it we need to reconsider how to do it here.
//if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
//    $useUSPS = true;

?>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-7">
            <div id="pos-tabs">
                 <ul class="nav nav-pills mb-2" id="tab-ul" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="find-tab" data-bs-toggle="pill" data-bs-target="#find-pane" type="button"
                                role="tab" aria-controls="nav-find" aria-selected="true">
                            Find
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-tab" data-bs-toggle="pill" data-bs-target="#add-pane" type="button"
                                role="tab" aria-controls="nav-add" aria-selected="false">
                            Add/Edit
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="review-tab" data-bs-toggle="pill" data-bs-target="#review-pane" type="button"
                                role="tab" aria-controls="nav-review" aria-selected="false" disabled>
                            Review Data
                        </button>
                    </li>
                     <li class="nav-item" role="presentation"\>
                        <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button"
                                role="tab" aria-controls="nav-pay" aria-selected="false" disabled>
                            Payment
                        </button>
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
                                    <button type="button" class="btn btn-sm btn-primary" id="find_unpaid_btn" name="find_btn"
                                            onclick="pos.find_record('unpaid') ;">
                                        Find Unpaid Transactions
                                    </button>
                                </div>
                                <div class="col-sm-8">
                                    <button type="button" class="btn btn-sm btn-primary" id="find_search_btn" name="find_btn"
                                            onclick="pos.ind_record('search');">
                                        Find Record
                                    </button>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-12 text-bg-secondary">
                                    Search Results
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 ms-0 me-0" id="find_results">
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
<?php
drawEditPersonBlock($conid, $useUSPS, $policies, 'registration', false, true, '', array(), 200, true, '');
?>
                            <div class="row">
                                <div class="col-sm-12 ms-0 me-0" id="add_results">
                            </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 mt-3">
                                    <button type="button" class="btn btn-primary btn-sm" id="addnew-btn" name="find_btn" onclick="pos.add_new();">Add to
                                        Cart
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="clearadd-btn" onclick="pos.clear_add();">
                                        Clear Add Person Form
                                    </button>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="review-pane" role="tabpanel" aria-labelledby="review-tab" tabindex="2">
                        <div id="review-div">Review Data</div>
                    </div>
                    <div class="tab-pane fade" id="pay-pane" role="tabpanel" aria-labelledby="pay-tab" tabindex="3">
                        <div id="pay-div">No Payment Required, Proceed to Next Customer</div>
                    </div>
                 </div>
            </div>
        </div>
        <div class="col-sm-5">
            <div id="cart"></div>
            <div class="row">
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-primary btn-sm" id="cart_no_changes_btn"
                            onclick="pos.review_nochanges();" hidden>No Changes
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="review_btn"
                            onclick="pos.start_review();" hidden>
                        Review Data
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="startover_btn"
                            onclick="pos.start_over(1);" hidden>Start Over
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="void_btn"
                            onclick="pos.void_trans();" hidden>Void
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="next_btn"
                            onclick="pos.start_over(1);" hidden>Next Customer
                    </button>
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
                    <button type='button' id="close_note_button" class='btn btn-primary'
                            onclick="pos.save_note();">
                        Close
                    </button>
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
                    <button type='button' id='discard_change_button' class='btn btn-secondary'
                            onclick='pos.changeModal.hide();'>
                        Keep Current Membership
                    </button>
                    <button type='button' id='close_change_button' class='btn btn-primary'
                            onclick='save_membership_change();'>
                        Change Membership
                    </button>
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
                    <button type='button' id='discard_cash_button' class='btn btn-secondary' onclick='pos.cashChangeModal.hide();'>
                        Cancel Cash Payment
                    </button>
                    <button type='button' id='close_cash_button' class='btn btn-primary' onclick='pos.pay("nomodal");'>
                        Change given to Customer
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'></pre><?php
page_foot($page);
