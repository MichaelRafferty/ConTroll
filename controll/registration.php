<?php
require_once 'lib/base.php';
require_once '../lib/profile.php';
require_once '../lib/portalForms.php';
require_once '../lib/policies.php';
require_once('../lib/cc__load_methods.php');
//initialize google session
$need_login = google_init('page');

$page = 'registration';
if (!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page('index.php');
}
load_cc_procs();

$con_conf = get_conf('con');
$conid = $con_conf['id'];
$condata = get_con();

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array('css/base.css', $cdn['tabcss'], $cdn['tabbs5']
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
$controll = get_conf('controll');
$conid = $con['id'];
$conname = $con['conname'];
$useUSPS = false;

if (array_key_exists('multioneday', $con))
    $multiOneDay =$con['multioneday'];
else
    $multiOneDay = 0;

if (array_key_exists('onedaycoupons', $con)) {
    $onedaycoupons = $con['onedaycoupons'];
} else {
    $onedaycoupons = 0;
}

$policies = getPolicies();
$policyIndex = array();
if ($policies != null) {
    for ($index = 0; $index < count($policies); $index++) {
        $policyIndex[$policies[$index]['policy']] = $index;
    }
}

if (array_key_exists('controll_registration', $debug)) {
    $debug_registration = $debug['controll_registration'];
} else
    $debug_registration = 0;

if (array_key_exists('useportal', $controll)) {
    $usePortal = $controll['useportal'];
} else {
    $usePortal = 0;
}

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = $debug_registration;
$config_vars['conid'] = $conid;
$config_vars['mode'] = 'admin';
$config_vars['regadminemail'] = $con['regadminemail'];
$config_vars['required'] = getConfValue('reg','required', 'addr');
$config_vars['useportal'] = $usePortal;
$config_vars['cashier'] = 1;
$config_vars['multiOneDay'] = $multiOneDay;
$config_vars['onedaycoupons'] = $onedaycoupons;
$config_vars['source'] = 'registration';

// form as laid out has no room for usps block, if we want it we need to reconsider how to do it here.
//if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
//    $useUSPS = true;

?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var allPolicies = <?php echo json_encode($policies); ?>;
    var policyIndex = <?php echo json_encode($policyIndex); ?>;
</script>
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
                     <li class="nav-item" role="presentation">
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
<?php
if (!$controll['useportal']) {
?>
                                    <button type="button" class="btn btn-sm btn-primary" id="find_unpaid_btn" name="find_btn"
                                            onclick="pos.findRecord('unpaid') ;">
                                        Find Unpaid Transactions
                                    </button>
<?php
}
?>
                                </div>
                                <div class="col-sm-8">
                                    <button type="button" class="btn btn-sm btn-primary" id="find_search_btn" name="find_btn"
                                            onclick="pos.findRecord('search');">
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
                                    <button type="button" class="btn btn-primary btn-sm" id="addnew-btn" name="add_btn"
                                            onclick="pos.add_new();">Add to Cart</button>
                                    <button type='button' class='btn btn-primary btn-sm' id='addoverride-btn' name='override_btn' hidden
                                            onclick='pos.addNewToCart(1);'>Add to Cart Overriding Missing Fields</button>
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
                            onclick="pos.reviewNoChanges();" hidden>No Changes
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="review_btn"
                            onclick="pos.startReview();" hidden>
                        Review Data
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="startover_btn"
                            onclick="pos.startOver(1);" hidden>Start Over
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="void_btn"
                            onclick="pos.voidTrans();" hidden>Void
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="next_btn"
                            onclick="pos.startOver(1);" hidden>Next Customer
                    </button>
                </div>
            </div>
        </div>       
    </div>
    <!--- notes modal popup -->
    <div class='modal modal-lg' id='Notes' tabindex='-2' aria-labelledby='Notes' data-bs-backdrop='static' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="NotesTitle">
                        Member Notes
                    </div>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class="container-fluid" id="NotesBody">
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' id="close_note_button" class='btn btn-primary'
                            onclick="pos.saveNote();">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--- add/Edit membership modal popup -->
    <div class='modal modal-xl fade' id='addEdit' tabindex='-3' aria-labelledby='addEdit' data-bs-backdrop='static'
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
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='CashChangeTitle'>
                        Change due to Customer
                    </div>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class="container-fluid" id='CashChangeBody'>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' id='discard_cash_button' class='btn btn-secondary' onclick='pos.hideCashChangeModal();'>
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
