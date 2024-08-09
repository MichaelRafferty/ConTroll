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
page_init("Badge List",
    /* css */ array($cdn['tabcss'],
                    // $cdn['tabbs5'],
                    'css/base.css',
                    ),
    /* js  */ array(//$cdn['luxon'],
                    $cdn['tabjs'],
                    'js/reg_admin.js',
                    'jslib/emailBulkSend.js'),
                    $need_login);

// first the modal for transfer to
$con_conf = get_conf('con');
$controll = get_conf('controll');
if ($controll != null && array_key_exists('badgelistfilter', $controll)) {
    $badgeListFilter = $controll['badgelistfilter'];
    if ($badgeListFilter != "top" && $badgeListFilter != "bottom")
        $badgeListFilter = "top";
} else
    $badgeListFilter = "top";

?>
<div id='transfer_to' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Transfer Registration' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='transferTitle'>Transfer Registration</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <form id="transfer-search" action='javascript:void(0)'>
                        <input type="hidden" name="from_badgeid" id="from_badgeid" value="">
                        <input type="hidden" name="from_perid" id="from_perid" value="">
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='name'>Transfer Badge::</label>
                            </div>
                            <div class='col-sm-10 p-0' id="transfer_badge"></div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='name'>Transfer From::</label>
                            </div>
                            <div class='col-sm-10 p-0' id='transfer_from'></div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='search_name'>Transfer To:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='namesearch' id='transfer_name_search' size='64' placeholder="Name/Portion of Name, Person (Badge) ID"/>
                            </div>
                            <div class='row mt-3'>
                                <div class='col-sm-12 text-bg-secondary'>
                                    Search Results
                                </div>
                            </div>
                            <div class='row'>
                                <div class='col-sm-12' id='transfer_search_results'>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='transferSearch' onClick='transfer_find()'>Find Person</button>
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
<?php
    if ($badgeListFilter == "top")
        drawFilters();
?>
    <div class="row">
        <div class="col-sm-auto p-0">
            <div id="badge-table"></div>
        </div>
    </div>
<?php
    if ($badgeListFilter == 'bottom')
        drawFilters();
?>
    <div class="row">
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/allEmails.php';">Download Email List</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/regReport.php';">Download Reg Report</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="sendEmail('marketing')">Send Marketing Email</button>
        </div>
        <div class='col-sm-auto p-2'>
            <button class='btn btn-primary btn-sm' onclick="sendEmail('comeback')">Send Come Back Email</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="sendEmail('reminder')">Send Attendance Reminder Email</button>
        </div>       
        <?php if ($db_ini['con']['survey_url']) { ?>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="sendEmail('survey')">Send Survey Email</button>
        </div>
        <?php } ?>
        <?php if ($db_ini['reg']['cancelled']) { ?>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="sendCancel()">Send Cancelation Instructions</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/cancel.php';">Download Cancellation Report</button>
        </div>
        <div class="col-sm-auto p-2">
            <button class="btn btn-primary btn-sm" onclick="window.location.href = 'reports/processRefunds.php';">Download Process Refunds Report</button>
        </div>
        <?php } ?>
    </div>
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
            <div id="paid-table"></div>
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
