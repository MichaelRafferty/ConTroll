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
                    'js/planSetup.js'
                   ),
              $need_login);


$config_vars = array();
$config_vars['pageName'] = 'finance';
$config_vars['label'] = $con['label'];
$config_vars['vemail'] = $conConf['regadminemail'];
$config_vars['debug'] = $debug_finance;
$config_vars['conid'] = $conid;
$paymentPlans = getPlanConfig();

// modals
//bs_tinymceModal();

    $tabstart = 100;
    $star = "<span class='text-danger'>&bigstar;</span>";
?>
// add/edit payment plan modal
<!-- space detail modal -->
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
                            <input class="form-control-sm" type="text" name="planName" id="planName>" size="16" maxlength="16"
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
                        <div class="col-sm-2">
                            <button class="btn btn-sm btn-primary" onclick="plans.editCategoryList();">Edit Category List</button>
                        </div>
                        <div class='col-sm-2' id="categoryList"></div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <button class='btn btn-sm btn-primary' onclick='plans.editIncludeList();'>Edit Include List</button>
                        </div>
                        <div class='col-sm-2' id='includeList'></div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <button class='btn btn-sm btn-primary' onclick='plans.editExcludeList();'>Edit Exclude List</button>
                        </div>
                        <div class='col-sm-2' id='excludeList'></div>
                    </div>
                    <div class='row mt-3'>
                        <div class='col-sm-12'>
                            <h2 class='h4'>Payment Items</h2>
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
                            <input type='number' name='downPaymentAmount id='downPaymentAmount' placeholder='n.nn' min='0' max='999999'
                                   class='no-spinners form-control' tabindex=" <?php echo $tabindex; $tabindex += 10; ?>"/>
                        </div>
                    </div>
                    <div class='row mt-1'>
                        <div class='col-sm-2'>
                            <label for='minPayment' class='form-label-sm'>
                                <span class='text-dark'>Minimum Payment</span>
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
                                <span class='text-dark'>Maximum Number of Payments</span>
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
                                <span class='text-dark'>Pay By Date</span>
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
                                <span class='text-dark'>Payment Type</span>
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
                                <span class='text-dark'>Modify Plan</span>
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
                                <span class='text-dark'>Send Reminders</span>
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
                                <span class='text-dark'>Include Non Plan in Down Payment</span>
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
                                <span class='text-dark'>Last Payment can Partial</span>
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
                                <span class='text-dark'>Active</span>
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
<?php
// additional computed tabs go here
?>
    </ul>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var paymentPlans = <?php echo json_encode($paymentPlans); ?>;
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
    <div class='tab-pane fade' id='paymentPlans-pane' role='tabpanel' aria-labelledby='paymentPlans-tab' tabindex='0'>
        <div class='container-fluid'>
            <div class='row mt-2'>
                <div class='col-sm-12' id='paymentPlanDiv'><H1 class='h3'><b>Payment Plans:</b></H1></div>
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
    <div id='result_message' class='mt-4 p-2'></div>
    <pre id='test'></pre>
</div>

<?php
page_foot($page);
?>
