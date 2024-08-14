<?php
global $db_ini;

require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "reg_admin";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'],
                    // $cdn['tabbs5'],
                    'css/base.css',
                    ),
    /* js  */ array(//$cdn['luxon'],
                    $cdn['tabjs'],
                    'js/tinymce/tinymce.min.js',
                    'js/reg_admin.js',
                    'js/regadmin_consetup.js',
                    /*'js/regadmin_memconfig.js',*/
                    'js/regadmin_merge.js',
                    'js/regadmin_customText.js',
                    'js/regadmin_policy.js',
                    'js/regadmin_interests.js',
                    'js/regadmin_rules.js',
                    'jslib/emailBulkSend.js'),
                    $need_login);

$con_conf = get_conf('con');
$controll = get_conf('controll');
if ($controll != null && array_key_exists('badgelistfilter', $controll)) {
    $badgeListFilter = $controll['badgelistfilter'];
    if ($badgeListFilter != "top" && $badgeListFilter != "bottom")
        $badgeListFilter = "top";
} else
    $badgeListFilter = "top";

$conid = $con_conf['id'];
$debug = get_conf('debug');

if (array_key_exists('controll_regadmin', $debug))
    $debug_admin=$debug['controll_regadmin'];
else
    $debug_regadmin = 0;

?>
<div id='parameters' <?php if (!($debug_regadmin & 4)) echo 'hidden'; ?>>
    <div id="debug"><?php echo $debug_regadmin; ?></div>
    <div id="conid"><?php echo $conid; ?></div>
</div>
<?php bs_tinymceModal(); ?>
<div id='merge-lookup' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Look up Merge Person' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='mergeTitle'>Lookup Person for Merge</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <form id='merge-search' action='javascript:void(0)'>
                        <div class='row p-1'>
                            <div class='col-sm-3 p-0'>
                                <label for='merge_name_search' id='mergeName'>Merge Name:</label>
                            </div>
                            <div class='col-sm-9 p-0'>
                                <input class='form-control-sm' type='text' name='namesearch' id='merge_name_search' size='64'
                                       placeholder='Name/Portion of Name, Person (Registration) ID'/>
                            </div>
                            <div class='row mt-3'>
                                <div class='col-sm-12 text-bg-secondary'>
                                    Search Results
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-12' id='merge_search_results'>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='mergeSearch' onClick='merge_find()'>Find Person</button>
            </div>
            <div id='result_message_merge' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<div id='editPreviewModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit and Preview Configuration' aria-hidden='true'
     style='--bs-modal-width: 96%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='editPreviewTitle'>Edit Preview Title</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid' id='editBlockDiv'></div>
                <div class='container-fluid' id='previewBlockDiv'></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='editPreviewSaveBtn' onClick='editPreviewSave()'>Save Changes</button>
            </div>
            <div id='result_message_editPreview' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
<div id='changeModal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Change Registration' aria-hidden='true' style='--bs-modal-width: 96%;'>
<div class='modal-dialog'>
    <div class='modal-content'>
        <div class='modal-header bg-primary text-bg-primary'>
            <div class='modal-title'>
                <strong id='changeTitle'>Change Registration</strong>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
        </div>
        <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
            <div class='container-fluid' id="change-body-div"></div>
            <div class='container-fluid' id="transfer-search-div" hidden>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='name'>Transfer Registration:</label>
                    </div>
                    <div class='col-sm-10 p-0' id='transfer_registration'></div>
                </div>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='name'>Transfer From:</label>
                    </div>
                    <div class='col-sm-10 p-0' id='transfer_from'></div>
                </div>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='search_name'>Transfer To:</label>
                    </div>
                    <div class='col-sm-10 p-0'>
                        <input class='form-control-sm' type='text' name='namesearch' id='transfer_name_search' size='64'
                               placeholder='Name/Portion of Name, Person (Registration) ID'/>
                    </div>
                </div>
                <div class='row mt-3'>
                    <div class='col-sm-2 p-0'></div>
                    <div class='col-sm-10 p-0'>
                        <button class='btn btn-sm btn-primary' id='transferSearch' onClick='changeTransferFind()'>Find Person</button>
                    </div>
                </div>
            </div>
            <div class='container-fluid' id='rollover-div' hidden>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='name'>Rollover Registration:</label>
                    </div>
                    <div class='col-sm-10 p-0' id='rollover_registration'></div>
                </div>
                <div class='row p-1'>
                    <div class='col-sm-12 p-0'>
                        <label for='name'>Select Registration Type for the rollover</label>
                    </div>
                </div>
                <div class='row p-1'>
                    <div class='col-sm-12 p-0' id="rollover_select"></div>
                </div>
                <div class='row mt-3 mb-2'>
                    <div class='col-sm-1 p-0'></div>
                    <div class='col-sm-10 p-0'>
                        <button class='btn btn-sm btn-primary' id='rollover-execute' onClick='changeRolloverExecute()'>Execute Rollover</button>
                    </div>
                </div>
            </div>
            <div class='container-fluid' id='editReg-div' hidden>
                <div class='row p-1'>
                    <div class='col-sm-2 p-0'>
                        <label for='name'>Edit Membershop:</label>
                    </div>
                    <div class='col-sm-10 p-0' id='edit_registration_label'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Reg Type:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto' id='edit_memSelect'></div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origMemLabel'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Price:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto'>
                        <input type="number" placeholder="New Price" id="edit_newPrice"/>
                    </div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origPrice'></div>
                    <div class='col-sm-auto'>New Reg:</div>
                    <div class='col-sm-auto' id='edit_newRegPrice'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Paid:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto'>
                        <input type='number' placeholder='New Paid' id='edit_newPaid'/>
                    </div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origPaid'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Coupon:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto'>
                        <input type='number' placeholder='New Coupon' id='edit_newCoupon'/>
                    </div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origCoupon'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Cpn Disc:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto'>
                        <input type='number' placeholder='New Coupon' id='edit_newCouponDiscount'/>
                    </div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origCouponDiscount'></div>
                </div>
                <div class='row mt-1'>
                    <div class='col-sm-1'>Status:</div>
                    <div class='col-sm-auto'>New:</div>
                    <div class='col-sm-auto' id='edit_statusSelect'></div>
                    <div class='col-sm-auto'>Original:</div>
                    <div class='col-sm-auto' id='edit_origStatus'></div>
                </div>
                <div class='row mt-3 mb-2'>
                    <div class='col-sm-1 p-0'></div>
                    <div class='col-sm-10 p-0'>
                        <button class='btn btn-sm btn-secondary' id='edit_discard' onClick='changeEditClose()'>Discard Changes</button>
                        <button class='btn btn-sm btn-primary' id='edit_save' onClick='changeEditSave(0)'>Save Changes</button>
                        <button class='btn btn-sm btn-warning' id='edit_saveOverride' onClick='changeEditSave(1)' hidden>
                            Save Changes Overriding Warnings
                        </button>
                    </div>
                </div>
            </div>
            <div class='container-fluid'>
                <div "class=row mt-2">
                    <div class="col-sm-12" id="changeMessageDiv"></div>
                </div>
            </div>
        </div>
        <div class='modal-footer'>
            <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Close</button>
        </div>
    </div>
</div>
</div>
<div id='receipt' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Registration Receipt' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='receiptTitle'>Registration Receipt</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div id="receipt-div"></div>
                <div id="regadminemail" hidden="true"><?php echo $con_conf['regadminemail'];?></div>
                <div id="receipt-text" hidden="true"></div>
                <div id="receipt-tables" hidden="true"></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Close</button>
                <button class='btn btn-sm btn-primary' id='emailReceipt' onClick='receipt_email("payor")'>Email Receipt</button>
                <button class='btn btn-sm btn-primary' id='emailReceiptReg' onClick='receipt_email("reg")'>Email Receipt to regadmin at <?php echo $con_conf['regadminemail'];?></button>
            </div>
        </div>
    </div>
</div>
<div id='notes' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Registration Notes' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='notesTitle'>Registration Notes</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class="container-fluid" id="notesText"></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Close</button>
            </div>
        </div>
    </div>
</div>
<ul class='nav nav-tabs mb-3' id='regadmin-tab' role='tablist'>
    <li class='nav-item' role='presentation'>
        <button class='nav-link active' id='registrationlist-tab' data-bs-toggle='pill' data-bs-target='#registrationlist-pane' type='button'
                role='tab' aria-controls='nav-registrationlist' aria-selected='true' onclick="settab('registrationlist-pane');">Registration List
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='consetup-tab' data-bs-toggle='pill' data-bs-target='#consetup-pane' type='button' role='tab'
                aria-controls='nav-consetup' aria-selected='false' onclick="settab('consetup-pane');">Current Convention Setup
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='nextconsetup-tab' data-bs-toggle='pill' data-bs-target='#nextconsetup-pane' type='button' role='tab'
                aria-controls='nav-nextconsetup' aria-selected='false' onclick="settab('nextconsetup-pane');">Next Convention Setup
        </button>
    </li>
    <!---
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='memconfig-tab' data-bs-toggle='pill' data-bs-target='#memconfig-pane' type='button' role='tab'
                aria-controls='nav-memconfigsetup' aria-selected='false' onclick="settab('memconfig-pane');">Membership Configuration
        </button>
    </li>
    --->
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='merge-tab' data-bs-toggle='pill' data-bs-target='#merge-pane' type='button' role='tab'
                aria-controls='nav-merge' aria-selected='false' onclick="settab('merge-pane');">Merge People
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='customtext-tab' data-bs-toggle='pill' data-bs-target='#customtext-pane' type='button' role='tab'
                aria-controls='nav-customtext' aria-selected='false' onclick="settab('customtext-pane');">Custom Text
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='policy-tab' data-bs-toggle='pill' data-bs-target='#policy-pane' type='button' role='tab'
                aria-controls='nav-policy' aria-selected='false' onclick="settab('policy-pane');">Policies
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='interests-tab' data-bs-toggle='pill' data-bs-target='#interests-pane' type='button' role='tab'
                aria-controls='nav-interests' aria-selected='false' onclick="settab('interests-pane');">Interests
        </button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='rules-tab' data-bs-toggle='pill' data-bs-target='#rules-pane' type='button' role='tab'
                aria-controls='nav-rules' aria-selected='false' onclick="settab('rules-pane');">Membership Rules
        </button>
    </li>
</ul>
<div class='tab-content ms-2' id='regadmin-content'>
    <div class='tab-pane fade show active' id='registrationlist-pane' role='tabpanel' aria-labelledby='registrationlist-tab' tabindex='0'>
        <div class="container-fluid">
<?php
    if ($badgeListFilter == "top")
        drawFilters();
?>
        <div class="row">
            <div class="col-sm-auto p-0">
                <div id="registration-table"></div>
            </div>
        </div>
<?php
    if ($badgeListFilter == 'bottom')
        drawFilters();
?>
        <div class="row">
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/allEmails.php';" disabled>Download Email List</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/regReport.php';" disabled>Download Reg Report</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="sendEmail('marketing')" disabled>Send Marketing Email</button>
            </div>
            <div class='col-sm-auto p-2'>
                <button class='btn btn-primary btn-sm' onclick="sendEmail('comeback')" disabled>Send Come Back Email</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="sendEmail('reminder')" disabled>Send Attendance Reminder Email</button>
            </div>
            <?php if ($db_ini['con']['survey_url']) { ?>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="sendEmail('survey')" disabled>Send Survey Email</button>
            </div>
            <?php } ?>
            <?php if ($db_ini['reg']['cancelled']) { ?>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="sendCancel()" disabled>Send Cancelation Instructions</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/cancel.php';" disabled>Download Cancellation Report</button>
            </div>
            <div class="col-sm-auto p-2">
                <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/processRefunds.php';">Download Process Refunds Report</button>
            </div>
            <?php } ?>
        </div>
    </div>
    </div></div>
    <div class='tab-pane fade' id='consetup-pane' role='tabpanel' aria-labelledby='consetup-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='nextconsetup-pane' role='tabpanel' aria-labelledby='nextconsetup-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='memconfig-pane' role='tabpanel' aria-labelledby='memconfig-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='merge-pane' role='tabpanel' aria-labelledby='merge-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='customtext-pane' role='tabpanel' aria-labelledby='customtext-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='policy-pane' role='tabpanel' aria-labelledby='policy-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='interests-pane' role='tabpanel' aria-labelledby='interests-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='rules-pane' role='tabpanel' aria-labelledby='rules-tab' tabindex='0'></div>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'>
</pre>
<?php

page_foot($page);

function drawFilters() {
?>
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-auto me-1 p-0">Click on a row to toggle filtering by that value</div>
        <div class="col-sm-auto me-1 p-0">
            <button class="btn btn-primary btn-sm" onclick="clearfilter();">Clear All Filters</button>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-auto me-1 p-0">
            <div id="category-table"></div>
        </div>
        <div class="col-sm-auto me-1  p-0">
            <div id="type-table"></div>
        </div>
        <div class="col-sm-auto me-1  p-0">
            <div id="age-table"></div>
        </div>
        <div class="col-sm-auto me-1  p-0">
            <div id="price-table"></div>
        </div>
        <div class="col-sm-auto me-1 p-0">
            <div id="label-table"></div>
        </div>
        <div class='col-sm-auto me-1 p-0'>
            <div id='coupon-table'></div>
        </div>
        <div class='col-sm-auto me-1 p-0'>
            <div id='status-table'></div>
        </div>
    </div>
    <?php
}
?>
