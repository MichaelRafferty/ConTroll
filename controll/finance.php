<?php
require_once "lib/base.php";
require_once "../lib/paymentPlans.php";

//initialize google session
$need_login = google_init("page");

$page = "finance";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$con = get_con();
$conid = $con['id'];
$debug = get_conf('debug');
$regConf = get_conf('reg');
$conConf = get_conf('con');
$usps = get_conf('usps');
if (array_key_exists('controll_finance', $debug))
    $debug_finance = $debug['controll_finance'];
else
    $debug_finance = 0;


$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5'],'css/base.css'),
    /* js  */ array(
                    //$cdn['luxon'],
                    $cdn['tabjs'],
                    'js/tinymce/tinymce.min.js',
                    'js/finance.js',
                    'js/planSetup.js',
                    'js/payors.js',
                    'js/coupon.js',
                   ),
              $need_login);


$config_vars = array();
$config_vars['pageName'] = 'finance';
$config_vars['label'] = $con['label'];
$config_vars['vemail'] = $conConf['regadminemail'];
$config_vars['debug'] = $debug_finance;
$config_vars['conid'] = $conid;
$paymentPlans = getPlanConfig();
// finance needs membership list (not counting free items) and category list
$memCategories = [];
$memLabels = [];
$cQ = <<<EOS
SELECT memCategory, notes
FROM memCategories
WHERE active='Y'
ORDER BY sortorder;
EOS;
$cR = dbQuery($cQ);
if ($cR !== false) {
    while ($cL = $cR->fetch_assoc())
        $memCategories[] = $cL;
    $cR->free();
}
$mQ = <<<EOS
SELECT id, conid, label, memType, memCategory, memAge
FROM memLabel
WHERE price > 0 AND ((memCategory != 'yearahead' AND conid = ?) OR (memCategory = 'yearahead' AND conid = ?))
ORDER BY conid, sort_order
EOS;
$mR = dbSafeQuery($mQ, 'ii', array($conid, $conid + 1));
if ($mR !== false) {
    while ($mL = $mR->fetch_assoc())
        $memLabels[] = $mL;
    $mR->free();
}

// for coupons
$limitSQL = <<<EOS
SELECT id, label, price, enddate
FROM memLabel
WHERE conid = ? AND price > 0 AND memCategory not in ('freebie', 'rollover', 'cancel', 'addon', 'add-on')
ORDER BY label, enddate, price
EOS;
    $limitR = dbSafeQuery($limitSQL, 'i', array($conid));
    $optionList = "<option value=''>Not Limited</option>\n";
    while ($row = $limitR->fetch_assoc()) {
        $optionList .= '<option value="' . $row['id'] . '">' . $row['label'] . ' (' . $row['price'] . ': ' . $row['enddate'] . ")</option>\n";
    }


// modals
//bs_tinymceModal();

    $tabindex = 100;
    $star = "<span class='text-danger'>&bigstar;</span>";
?>
<!-- add/edit payment plan modal -->
<div id='addEditPlan' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Add/Edit Payment Plan' aria-hidden='true' style='--bs-modal-width: 96%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title' id='plan-title'>
                    <strong>Add/Edit Payment Plan</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <div class='row mt-2'>
                        <div class='col-sm-12'>
                            <h1 class="h3" id="plan-heading">Add/Edit Payment Plan</h1>
                        </div>
                    </div>
                    <div class='row mt-2'>
                        <div class='col-sm-12'>
                            <h2 class='h4'>Name/Description</h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class='col-sm-2'>
                            <label for="planName" class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Plan Name</span>
                            </label>
                        </div>
                        <div class="col-sm-2">
                            <input class="form-control-sm" type="text" name="planName" id="planName" size="16" maxlength="16"
                                   tabindex=" <?php echo $tabindex; $tabindex += 10;?>"/>
                        </div>
                        <div class="col-sm-8">Short (up to 16 character) Name that this plan will be referred to in the site and emails.</div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='planDescription' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Plan Description</span>
                            </label>
                        </div>
                        <div class="col-sm-10">
                            <textarea cols="128" rows="8" wrap="soft" maxlength="1020" id="planDescription" name="planDescription"
                                placeholder="Enter a description about this plan that will be shown to the customer to help the choose between plans"
                                      tabindex=" <?php echo $tabindex; $tabindex += 10; ?>">
                            </textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="container-fluid">
                                <div class='row mt-3'>
                                    <div class='col-sm-12'>
                                        <h2 class='h4'>In Plan Membership Criteria</h2>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        For a membership to be "In Plan" it must:
                                        <ul>
                                            <li>Not match any of the ID's in the exclude list</li>
                                            <li>Be in one of the categories in the Category list</li>
                                            <li>or match one of the ID's in the Include List</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class='row'>
                                    <div class="col-sm-6">
                                        <button class="btn btn-sm btn-primary" onclick="plans.editList('category');">Edit Category List</button>
                                    </div>
                                    <div class='col-sm-6' id="categoryListDiv"><i>None</i></div>
                                    <input type="hidden" id="categoryList" name="categoryList">
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-6'>
                                        <button class='btn btn-sm btn-primary' onclick="plans.editList('include');">Edit Include List</button>
                                    </div>
                                    <div class='col-sm-6' id='includeListDiv'><i>None</i></div>
                                    <input type='hidden' id='includeList' name='includeList'>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-6'>
                                        <button class='btn btn-sm btn-primary' onclick="plans.editList('exclude');">Edit Exclude List</button>
                                    </div>
                                    <div class='col-sm-6' id='excludeListDiv'><i>None</i></div>
                                    <input type='hidden' id='excludeList' name='excludeList'>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-sm-6'>
                                        <button class='btn btn-sm btn-primary' onclick="plans.editList('portal');">Edit Portal List</button>
                                    </div>
                                    <div class='col-sm-6' id='portalListDiv'><i>None</i></div>
                                    <input type='hidden' id='portalList' name='portlList'>
                                </div>
                                <div class='row mt-3'>
                                    <div class='col-sm-12'>
                                        <h2 class='h4'>Payment Items</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-8" id="selectDiv">
                            <div class="container-fluid">
                                <div class='row mb-2'>
                                    <div class='col-sm-12' id='editSelLabel'></div>
                                </div>
                                <div class='row'>
                                    <div class='col-sm-12 m-0 p-0' id='editSelTable'></div>
                                </div>
                                <div class='row mt-1' id='editSelButtons' name='editSelButtons'>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-secondary btn-sm' type='button' onclick="plans.closeSelTable();">Cancel Changes</button>
                                    </div>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-secondary btn-sm' type='button' onclick="plans.setEditSel(false);">
                                            Clear All Items
                                        </button>
                                    </div>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-secondary btn-sm' type='button' onclick="plans.setEditSel(true);">
                                            Select All Items
                                        </button>
                                    </div>
                                    <div class='col-sm-auto'>
                                        <button class='btn btn-primary btn-sm' type='button' onclick="plans.applyEditSel();">Apply Selections</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                        <label for='downPaymentPercent' class='form-label-sm'>
                            <span class='text-dark'>Down Payment Percent</span>
                        </label>
                        </div>
                        <div class="col-sm-2">
                        <input type='number' name='downPaymentPercent' id='downPaymentPercent' placeholder='% to 2 places' min='0' max='100'
                               class='no-spinners form-control' tabindex=" <?php echo $tabindex; $tabindex += 10; ?>"/>
                        </div>
                        <div class="col-sm-8">The larger of the down payment in % or the down payment in amount will be the minumum down payment.</div>
                        </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='downPaymentAmount' class='form-label-sm'>
                                <span class='text-dark'>Down Payment Amount</span>
                            </label>
                        </div>
                        <div class='col-sm-2'>
                            <input type='number' name='downPaymentAmount' id='downPaymentAmount' placeholder='n.nn' min='0' max='999999'
                                   class='no-spinners form-control' tabindex=" <?php echo $tabindex; $tabindex += 10; ?>"/>
                        </div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='minPayment' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Minimum Payment</span>
                            </label>
                        </div>
                        <div class='col-sm-2'>
                            <input type='number' name='minPayment' id='minPayment' placeholder='n.nn' min='0' max='99999'
                                   class='no-spinners form-control' tabindex=" <?php echo $tabindex; $tabindex += 10; ?>"/>
                        </div>
                        <div class='col-sm-8'>Each payment after the down payment, but not necessarily the final payment must be at least this amount.</div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='maxNumPayments' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Maximum Number of Payments</span>
                            </label>
                        </div>
                        <div class='col-sm-2'>
                            <input type='number' name='maxNumPayments' id='maxNumPayments' placeholder='N' min='1' max='32'
                                   class='no-spinners form-control' tabindex=" <?php echo $tabindex; $tabindex += 10; ?>"/>
                        </div>
                        <div class='col-sm-8'>
                            The number of payments on this plan cannot exceed this number. The system will take into the amount of time
                            between the start of the plan and the "Pay By Date" as well as the minimum (7 days) and maximum (30 day) between payments.
                        </div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='payByDate' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Pay By Date</span>
                            </label>
                        </div>
                        <div class='col-sm-2'>
                            <input type='date' name='payByDate' id='payByDate' tabindex=" <?php echo $tabindex; $tabindex += 10; ?>"/>
                        </div>
                        <div class='col-sm-8'>
                            This cannot be later than 2 weeks before the convention start date.
                        </div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='paymentType' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Payment Type</span>
                            </label>
                        </div>
                        <div class='col-sm-2'>
                            <select id="paymentType" name="paymentType" tabindex=" <?php echo $tabindex; $tabindex += 10; ?>">
                                <option value="manual">Manual Payments</option>
                                <option value="auto">Automatic Payments</option>
                            </select>
                        </div>
                        <div class='col-sm-8'>
                            Manual payments are started by the member.  Automatic payments are charged on the schedule automatically.<br/>
                            NOTE: Automatic payments are not yet supported.
                        </div>
                    </div>
                    <div class='row mt-3'>
                        <div class='col-sm-12'>
                            <h2 class='h4'>Plan Options</h2>
                        </div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='modifyPlan' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Modify Plan</span>
                            </label>
                        </div>
                        <div class='col-sm-5'>
                            <select id='modifyPlan' name='modifyPlan' style="width: 600px;"
                                    tabindex=" <?php echo $tabindex; $tabindex += 10; ?>">
                                <option value='Y'>Can Modify Calculated Settings</option>
                                <option value='N'>Must Accept Plan as Calculated</option>
                            </select>
                        </div>
                        <div class='col-sm-5'>If Modify is Y, the member can set the down payment, minimum payment, number of payments,
                            and frequency of payments within the limits of the plan as configured above.</div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='reminders' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Send Reminders</span>
                            </label>
                        </div>
                        <div class='col-sm-5'>
                            <select id='reminders' name='reminders' style='width: 600px;'
                                    tabindex=" <?php echo $tabindex; $tabindex += 10; ?>">
                                <option value='Y'>Send Payment Due Reminders</option>
                                <option value='N'>No Reminders will be sent</option>
                            </select>
                        </div>
                        <div class='col-sm-5'>Past Due reminders are always sent, but N will suppress upcoming payment due reminders.</div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='downPaymentIncludes' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Include Non Plan in Down Payment</span>
                            </label>
                        </div>
                        <div class='col-sm-5'>
                            <select id='downPaymentIncludes' name='downPaymentIncludes' style='width: 600px;'
                                    tabindex=" <?php echo $tabindex; $tabindex += 10; ?>">
                                <option value='Y'>The non plan amounts count towards the down payment</option>
                                <option value='N'>The down payment is computed from the in plan amounts only</option>
                            </select>
                        </div>
                        <div class='col-sm-5'>If included, the non plan amounts will meet the requirements for the minimum down payment.  If not, the non
                            plan amounts plus the down payment on the plan amounts will be due at plan setup time.</div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='lastPartial' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Last Payment can Partial</span>
                            </label>
                        </div>
                        <div class='col-sm-5'>
                            <select id='lastPartial' name='lastPartial' style='width: 600px;'
                                    tabindex=" <?php echo $tabindex; $tabindex += 10; ?>">
                                <option value='Y'>The last payment can be less than the minimum payment</option>
                                <option value='N'>All payments will be an equal amount</option>
                            </select>
                        </div>
                        <div class='col-sm-5'>If Y, the last payment is the remainder after all minimum payments are paid.  If N, the minimum payment will be
                            increased to make all payments equal.
                        </div>
                    </div>
                    <div class='row mt-1 mb-3'>
                        <div class='col-sm-2'>
                            <label for='active' class='form-label-sm'>
                                <span class='text-dark'><?php echo $star; ?>Active</span>
                            </label>
                        </div>
                        <div class='col-sm-5'>
                            <select id='active' name='active' style='width: 600px;'
                                    tabindex=" <?php echo $tabindex; $tabindex += 10; ?>">
                                <option value='Y'>Plan is available this year</option>
                                <option value='N'>Plan is configured but not offered this year</option>
                            </select>
                        </div>
                        <div class='col-sm-5'>You can disable a plan without deleting it.  Set Active=N to disable offering this plan.</div>
                    </div>
                    <div class='row' id='plan_message_div'></div>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='plan-saveRow-btn' onclick='plans.saveAddEdit()'>Save Changes</button>
            </div>
        </div>
    </div>
</div>
<!--- coupon add-edit modal -->
<div id='edit_coupon' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Add/Edit Coupon' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title' id='edit-coupon-title'>
                    <strong>Edit Coupon</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid form-floating'>
                    <div class='row mb-1'>
                        <div class='col-sm-12' id='edit_coupon_preform'></div>
                    </div>
                    <form id='coupon_form' class='form-floating' action='javascript:void(0);'>
                        <input type='hidden' name='couponId' id='form_couponId'/>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_code'>Code*:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='text' name='code' id='form_code' size='16' maxlength="16" required/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_name'>Descriptive Name*:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='text' name='name' id='form_name' size='32' maxlength="32" required/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_couponType'>One Use Coupon:*</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <select name='oneUse' id='form_oneUse'>
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_startDate'>Optional Starting Date:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='datetime-local' name='startDate' id='form_startDate'/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_endDate'>Optional Ending Date:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='datetime-local' name='endDate' id='form_endDate'/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_couponType'>Coupon Type:*</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <select name="couponType" id="form_couponType">
                                    <option value="$off">Fixed dollars off Cart</option>
                                    <option value="%off">Fixed percentage off Primary Memberships in Cart</option>
                                    <option value="$mem">Fixed dollars off Primary Memberships</option>
                                    <option value="%mem">Fixed percentage off Primary Memberships</option>
                                    <option value="price">Set specific membership type to a fixed price</option>
                                </select>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_discount'>Discount ($ or %)*:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' name='discount' id='form_discount' type='number' required/>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_memId'>Limit to membership type: </label>
                            </div>
                            <div class='col-sm-8 p-0'>
                                <select class='form-control-sm' id='form_memId' name='memId'>
                                    <?php echo $optionList; ?>
                                </select>
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_minMemberships'>Minimum Primary Memberships:</label>
                            </div>
                            <div class="col-sm-auto p-0">
                                <input class='form-control-sm' type='number' name='minMemberships' id='form_minMemberships' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_maxMemberships'>Apply to at most (Max) Memberships:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='maxMemberships' id='form_maxMemberships' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_limitMemberships'>Limit number of this Primary Membership:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='limitMemberships' id='form_limitMemberships' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_minTransaction'>Require minimum cart of $:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='minTransaction' id='form_minTransaction' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_maxTransaction'>Discount maximum cart of $:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='maxTransaction' id='form_maxTransaction' />
                            </div>
                        </div>
                        <div class='row mb-1'>
                            <div class='col-sm-4'>
                                <label for='form_maxRedemption'>Maximum number of redemptions:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input class='form-control-sm' type='number' name='maxRedemption' id='form_maxRedemption' />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4"></div>
                            <div class="col-sm-auto p-0">
                                <button id="form_submit" type="button" class="btn btn-primary btn-sm" onclick="coupons.UpdateCoupon();">Update Coupon</button>
                                <button id="form_delete" type="button" class="btn btn-warning btn-sm" onclick="coupons.DeleteCoupon();">Delete Unused Coupon</button>
                                <button id="form_cancel" type="button" class="btn btn-secondary btn-sm" onClick="coupons.HideEditModal();">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid" id='main'>
    <ul class='nav nav-tabs mb-3' id='finance-tab' role='tablist'>
        <li class='nav-item' role='presentation'>
            <button class='nav-link active' id='overview-tab' data-bs-toggle='pill' data-bs-target='#overview-pane' type='button' role='tab'
                    aria-controls='nav-overview' aria-selected="true" onclick="finance.setFinanceTab('overview-pane');">Overview
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='paymentPlans-tab' data-bs-toggle='pill' data-bs-target='#paymentPlans-pane' type='button' role='tab'
                    aria-controls='nav-configuration' aria-selected='false' onclick="finance.setFinanceTab('paymentPlans-pane');">
                Payment Plan Configuration
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='payorPlans-tab' data-bs-toggle='pill' data-bs-target='#payorPlans-pane' type='button' role='tab'
                    aria-controls='nav-configuration' aria-selected='false' onclick="finance.setFinanceTab('payorPlans-pane');">
                Payor Plans
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='coupon-tab' data-bs-toggle='pill' data-bs-target='#coupon-pane' type='button' role='tab'
                    aria-controls='nav-configuration' aria-selected='false' onclick="finance.setFinanceTab('coupon-pane');">
                Coupons
            </button>
        </li>
<?php
// additional computed tabs go here
?>
    </ul>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var paymentPlans = <?php echo json_encode($paymentPlans); ?>;
    var memCategories = <?php echo json_encode($memCategories); ?>;
    var memLabels = <?php echo json_encode($memLabels); ?>;
</script>
    <div class='tab-content ms-2' id='overview-content'>
        <div class='container-fluid'>
            <div class='row'>
                <div class='col-sm-12'>
                    <h3 style='text-align: center;'><strong>Finance Overview</strong></h3>
                </div>
            </div>
            <div class='row'>
                <div class="col-sm-12">
                    <p>The Finance tab handles functions related to money:</p>
                    <ol>
                        <li>Payment Plan Configuration</li>
                        <li>Payor Plans</li>
                        <li>Refunds</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='paymentPlans-pane' role='tabpanel' aria-labelledby='paymentPlans-tab' tabindex='0' hidden>
        <div class='container-fluid'>
            <div class='row mt-2'>
                <div class='col-sm-12' id='paymentPlanDiv'><H1 class='h3'><b>Payment Plan Configuration:</b></H1></div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-12" id="paymentPlanTable"></div>
            </div>
            <div class='row mt-2'>
                 <div class="col-sm-auto">
                     <button class="btn btn-sm btn-secondary" onclick="plans.addNew();">Add New</button>
                 </div>
                <div class='col-sm-auto'>
                    <button class='btn btn-sm btn-primary' id="planSaveBtn" onclick='plans.save();' disabled>Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='payorPlans-pane' role='tabpanel' aria-labelledby='payorPlans-tab' tabindex='0' hidden>
        <div class='container-fluid'>
            <div class='row mt-2'>
                <div class='col-sm-12' id='payorPlanDiv'><H1 class='h3'><b>Payment Plans:</b></H1></div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-12' id='payorPlansTable'></div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='coupon-pane' role='tabpanel' aria-labelledby='coupon-tab' tabindex='0' hidden>
        <div class='container-fluid'>
            <div class='row mt-2'>
                <div class='col-sm-auto p-0 m-0 me-4'>
                    <h4>Coupons:</h4>
                </div>
                <div class='col-sm-auto p-0 m-0 me-4'>
                    <button id='coupon-addrow' type='button' class='btn btn-secondary btn-sm' onclick='coupons.AddNew();'>Add New</button>
                </div>
                <div class='col-sm-auto p-0 m-0 ms-4'>
                    Click on 'ID', '#Used' or '#Keys' cells to display additional details.
                </div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-auto p-0 m-0' id='couponTable'></div>
            </div>
            <div class='row mt-2'>
                <div class='col-sm-auto p-0 m-0' id='detailTable'></div>
            </div>
    </div>
    <div id='result_message' class='mt-4 p-2'></div>
    <pre id='test'></pre>
</div>

<?php
page_foot($page);
?>
