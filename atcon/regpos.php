<?php

require_once "lib/base.php";
require_once '../lib/profile.php';
require_once '../lib/portalForms.php';
require_once '../lib/policies.php';

// if not logged in, send back to the index page to log in
if (!isSessionVar('user')) {
    header("Location: /index.php");
    exit(0);
}

$con = get_conf('con');
$debug = get_conf('debug');
$usps = get_conf('usps');
$vendor = get_conf('vendor');
$ini = get_conf('reg');
$controll = get_conf('controll');
$atcon = get_conf('atcon');
$condata = get_con();
$conid = $con['id'];
$conname = $con['conname'];
$tab = 'checkin';
$mode = 'checkin';
$method='data_entry';
if (array_key_exists('allage', $atcon)) {
    $allAgeFirst = $atcon['allage'];
} else {
    $allAgeFirst = 0;
}

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

if (array_key_exists('taxRate', $con))
    $taxRate = $con['taxRate'];
else
    $taxRate = 0;

setSessionVar('POSMode', $mode);

if (array_key_exists('taxLabel', $con))
    $taxLabel = $con['taxLabel'];
else
    $taxLabel = '';

if (array_key_exists('multioneday', $con))
    $multiOneDay =$con['multioneday'];
else
    $multiOneDay = 0;

$policies = getPolicies();
$policyIndex = array();
if ($policies != null) {
    for ($index = 0; $index < count($policies); $index++) {
        $policyIndex[$policies[$index]['policy']] = $index;
    }
}
$useUSPS = false;
$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['mode'] = $mode;
$config_vars['tab'] = $tab;
$config_vars['conid'] = $conid;
$config_vars['regadminemail'] = $con['regadminemail'];
$config_vars['required'] = $ini['required'];
$config_vars['useportal'] = $controll['useportal'];
$config_vars['cashier'] = $method == 'cashier' ? 1 : 0;
$config_vars['cashierAllowed'] = check_atcon('cashier', $conid) ? 1 : 0;
$config_vars['multiOneDay'] = $multiOneDay;
$config_vars['allAgeFirst'] = $allAgeFirst;
if (array_key_exists('creditoffline', $atcon)) {
    $config_vars['creditoffline'] = $atcon['creditoffline'];
}
if (array_key_exists('creditonline', $atcon)) {
    $config_vars['creditonline'] = $atcon['creditonline'];
}
if (isset($_GET['tid'])) {
    $config_vars['autoloadTID'] = $_GET['tid'];
}

$useUSPS = false;

// form as laid out has no room for usps block, if we want it we need to reconsider how to do it here.
//if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
//    $useUSPS = true;

$cdn = getTabulatorIncludes();
page_init($page, $tab,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5']),
    /* js  */ array( ///$cdn['luxon'],
                    $cdn['tabjs'],
                    'jslib/posCart.js',
                    'jslib/posCoupon.js',
                    'jslib/pos.js',
                    'jslib/membershipRules.js', 'js/regpos.js'),
            $config_vars
    );
?>
<script type='text/javascript'>
    var allPolicies = <?php echo json_encode($policies); ?>;
    var policyIndex = <?php echo json_encode($policyIndex); ?>;
</script>
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
                                    <button type="button" class="btn btn-sm btn-primary" id="find_unpaid_btn" name="find_btn" onclick="pos.findRecord('unpaid')
                                    ;" hidden>Find Unpaid Transactions</button>
                                    <?php } ?>
                                </div>
                                <div class="col-sm-8">
                                    <button type="button" class="btn btn-sm btn-primary" id="find_search_btn" name="find_btn" onclick="pos.findRecord('search');
">Find Record</button>
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
                        <form id='add-edit-form' name='add-edit-form' onsubmit='return false;'>
                            <div class='container-fluid'>
                                <div class='row' id='add_header'>
                                    <div class='col-sm-12 text-bg-primary mb-2'>
                                        <div class='text-bg-primary m-2'>
                                            Add New Person and Membership
                                        </div>
                                    </div>
                                </div>
                                <input type='hidden' name='perinfo-index' id='perinfo-index'/>
                                <input type='hidden' name='perinfo-perid' id='perinfo-perid'/>
                                <input type='hidden' name='membership-index' id='membership-index'/>
                                <?php
                                    drawEditPersonBlock($conid, $useUSPS, $policies, 'registration', false, true, '', array (), 200, true, '');
                                ?>
                                <div class="row">
                                    <div class="col-sm-12 ms-0 me-0" id="add_results">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12 mt-3">
                                        <button type="button" class="btn btn-primary btn-sm" id="addnew-btn" name="add_btn"
                                                onclick="pos.add_new();">Add to Cart
                                        </button>
                                        <button type='button' class='btn btn-primary btn-sm' id='addoverride-btn' name='override_btn' hidden
                                                onclick='pos.addNewToCart(1);'>Add to Cart Overriding Missing Fields
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" id="clearadd-btn" onclick="pos.clearAdd();">
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
                    <button type="button" class="btn btn-primary btn-sm" id="cart_no_changes_btn" onclick="pos.reviewNoChanges();" hidden>No Changes</button>
                    <button type="button" class="btn btn-primary btn-sm" id="review_btn" onclick="pos.startReview();" hidden>Review Data</button>
                    <button type="button" class="btn btn-warning btn-sm" id="startover_btn" onclick="pos.startOver(1);" hidden>Start Over</button>
                    <button type="button" class="btn btn-warning btn-sm" id="void_btn" onclick="pos.voidTrans();" hidden>Void</button>
                    <button type="button" class="btn btn-primary btn-sm" id="next_btn" onclick="pos.startOver(1);" hidden>Next Customer</button>
                </div>
            </div>
        </div>       
    </div>
    <!--- notes modal popup -->
    <div class='modal modal-lg' id='Notes' tabindex='-2' aria-labelledby='Notes' data-bs-backdrop='static' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='NotesTitle'>
                        Member Notes
                    </div>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid' id='NotesBody'>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' id='close_note_button' class='btn btn-primary'
                            onclick='pos.saveNote();'>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--- add/Edit membership modal popup -->
    <div class='modal modal-x1 fade' id='addEdit' tabindex='-3' aria-labelledby='addEdit' data-bs-backdrop='static'
         data-bs-keyboard='false' aria-hidden='true' style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='AddEditTitle'>
                        Add/Edit Memberships
                    </div>
                    <button type='button' class='btn-close' onclick='cart.checkAddEditClose();' aria-label='Close'></button>
                </div>
                <div class='modal-body' id='AddEditBody' style='padding: 4px; background-color: lightcyan;'>
                    <?php
                        drawGetAgeBracket('<span id="addEditFullName">Fullname</span>', $condata);
                        drawGetNewMemberships()
                    ?>
                    <div class='row'>
                        <div class='col-sm-12'>
                            <h2 class='size-h3'>Registration Items:</h2>
                        </div>
                    </div>
                    <div id='cartContentsDiv'>Cart Placeholder</div>
                    <div class='row'>
                        <div class='col-sm-12' id='aeMessageDiv'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' id='discard_change_button' class='btn btn-secondary'
                            onclick='cart.checkAddEditClose();'>
                        Keep Current Memberships
                    </button>
                    <button type='button' id='close_change_button' class='btn btn-primary'
                            onclick='cart.saveMembershipChange();'>
                        Save Changes to Memberships
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
        drawVariablePriceModal('cart');
    ?>
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
                    <button type='button' id='discard_cash_button' class='btn btn-secondary' onclick='pos.hideCashChangeModal();'>Cancel Cash Payment</button>
                    <button type='button' id='close_cash_button' class='btn btn-primary' onclick='pos.pay("nomodal");'>Change given to Customer</button>
                </div>
            </div>
        </div>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'></pre><?php
page_foot();
