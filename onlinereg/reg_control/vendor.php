<?php
require_once "lib/base.php";
require_once "../../lib/exhibitorRegistrationForms.php";
require_once "../../lib/exhibitorRequestForms.php";
require_once "../../lib/exhibitorReceiptForms.php";

//initialize google session
$need_login = google_init("page");

$page = "vendor";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5'],'css/base.css'),
    /* js  */ array(
                    //$cdn['luxon'],
                    $cdn['tabjs'],
                    //'js/d3.js',
                    'js/base.js',
                    'jslib/exhibitorProfile.js',
                    'js/vendor.js',
                    'jslib/exhibitorRequest.js',
                    'jslib/exhibitorReceipt.js',
                    'js/tinymce/tinymce.min.js'
                   ),
              $need_login);

$con = get_con();
$conid = $con['id'];
$conf = $con['id'];
$debug = get_conf('debug');
$vendor_conf = get_conf('vendor');
if (array_key_exists('reg_control_exhibitors', $debug))
    $debug_exhibitors = $debug['reg_control_exhibitors'];
else
    $debug_exhibitors = 0;

$conf = get_conf('con');

// to build tabs get the list of vendor types
$regionOwnerQ = <<<EOS
SELECT eR.id, eR.name, eRY.ownerName, eRT.requestApprovalRequired, eRT.purchaseApprovalRequired
FROM exhibitsRegionYears eRY
JOIN exhibitsRegions eR ON eRY.exhibitsRegion = eR.id
JOIN exhibitsRegionTypes eRT ON eR.regionType = eRT.regionType
WHERE conid = ?
ORDER BY eRY.ownerName, eR.name;
EOS;
$regionOwnerR = dbSafeQuery($regionOwnerQ, 'i',array($conid));
if ($regionOwnerR == false || $regionOwnerR->num_rows == 0) {
    echo "No exhibits are configured.";
    page_foot($page);
    return;
}

// load country select
$countryOptions = '';
$fh = fopen(__DIR__ . '/../../lib/countryCodes.csv', 'r');
while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
    $countryOptions .= '<option value="' . escape_quotes($data[1]) . '">' .$data[0] . '</option>' . PHP_EOL;
}
fclose($fh);

$config_vars = array();
$portalType = 'admin';
$portalName = 'Exhibitor';
$config_vars['lƒabel'] = $con['label'];
$config_vars['vemail'] = $conf['regadminemail'];
$config_vars['portalType'] = $portalType;
$config_vars['portalName'] = $portalName;
$config_vars['artistsite'] = $vendor_conf['artistsite'];
$config_vars['vendorsite'] = $vendor_conf['vendorsite'];
$config_vars['debug'] = $debug['reg_control_exhibitors'];

draw_registrationModal('admin', 'Admin', $conf, $countryOptions);
draw_exhibitorRequestModal('admin');
draw_exhibitorReceiptModal('admin');
?>
<!-- space detail modal -->
    <div id='space_detail' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Space Detail' aria-hidden='true'
    style='--bs-modal-width: 90%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='space-detail-title'>
                        <strong>Space Detail</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <div class="row">
                            <div class="col-sm-12">
                                <h4>Space Request/Approval/Payment Detail</h4>
                            </div>
                        </div>
                        <div id='spaceDetailHTML'></div>
                        <div class='row mt-3'>
                            <div class='col-sm-12'>
                                <h4>Information about this Exhibitor</h4>
                            </div>
                        </div>
                        <div class="container-fluid" id='exhibitorInfoHTML'></div>
                        <div class='row' id='spacedetail_message_div'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Dismiss</button>
                </div>
            </div>
        </div>
    </div>
<!-- locations modal -->
<div id='locations_edit' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Locations Edit' aria-hidden='true'
     style='--bs-modal-width: 96%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title' id='locations-edit-title'>
                    <strong>Locations Edit</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <input type = 'hidden' id = 'spaceRowId' name = 'spaceRowId'/>
                <div class='container-fluid'>
                    <div class='row'>
                        <div class='col-sm-12'>
                            <h4>Locations</h4>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-1'>Space:</div>
                        <div class='col-sm-2' id='spaceHTML'></div>
                        <div class='col-sm-9'>
                            <input type="text" name="locations", id="locationsVal", placeholder="Enter locations separated by commas", maxlength="256" size="90"/>
                        </div>
                    </div>
                    <div class='row mt-3'>
                        <div class="col-sm-6 ms-0 me-0">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class='col-sm-12 ms-0 me-0'>
                                        <h4>Information about this Exhibitor</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="container-fluid" id='locationsExhibitorInfoHTML'></div>
                        </div>
                        <div class='col-sm-6'>
                            <div class='container-fluid'>
                                <div class='row'>
                                    <div class='col-sm-12'>
                                        <h4>Locations Used by all Artists</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="container-fluid" id='locationsUsedHTML'></div>
                        </div>
                    </div>
                    <div id='locationsExhibitorInfoHTML'></div>
                    <div class='row' id='locations_message_div'></div>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id="locationsSubmitBtn" onclick="exhibitors.submitLocations()">Update Locations</button>
            </div>
        </div>
    </div>
</div>
<!-- import modal -->
    <div id='import_exhibitor' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Import Past Vendors' aria-hidden='true'
         style='--bs-modal-width: 96%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='exhibitor_receipt_title'>
                        <strong>Import Past Exhibitors</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <div id='importHTML'></div>
                        <div class='row' id='import_message_div'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' onclick='exhibitors.importPastExhibitors()'>Import Selected Past Exhibitors</button>
                </div>
            </div>
        </div>
    </div>
<div id='main'>
    <ul class='nav nav-tabs mb-3' id='exhibitor-tab' role='tablist'>
        <li class='nav-item' role='presentation'>
            <button class='nav-link active' id='overview-tab' data-bs-toggle='pill' data-bs-target='#overview-pane' type='button' role='tab' aria-controls='nav-overview'
            aria-selected="true" onclick="exhibitors.settabOwner('overview-pane');">Overview
            </button>
        </li>
<?php
// build tab structure
$regionOwners = [];
$regionOwnersTabNames = [];
$regions = [];
$regionTabNames = [];
while ($regionL = $regionOwnerR->fetch_assoc()) {
    $regionOwner = $regionL['ownerName'];
    $regionOwnerId = str_replace(' ', '-', $regionOwner);
    $regionOwnersTabNames[$regionOwnerId . '-pane'] = $regionOwner;
    $regionOwners[$regionOwner][$regionL['id']] = $regionL;
    $regionOwners[$regionOwner][$regionL['id']] = $regionL;
    $regions[$regionL['name']] = [ 'regionOwner' => $regionOwner, 'id' => $regionL['id'],
        'requestApprovalRequired' => $regionL['requestApprovalRequired'], 'purchaseApprovalRequired' => $regionL['purchaseApprovalRequired'] ];
    $regionTabName = str_replace(' ', '-', $regionL['name']) . '-pane';
    $regionTabNames[$regionTabName] = [ 'name' => $regionL['name'], 'id' => $regionL['id'] ];
    if (count($regionOwners[$regionOwner]) == 1) {
    ?>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='<?php echo $regionOwnerId; ?>-tab' data-bs-toggle='pill' data-bs-target='#<?php echo $regionOwnerId; ?>-pane' type='button' role='tab' aria-controls='nav-<?php echo $regionOwnerId; ?>'
                    aria-selected="false" onclick="exhibitors.settabOwner('<?php echo $regionOwnerId; ?>-pane');"><?php echo $regionOwner; ?>
            </button>
        </li>
    <?php
    }
}
?>
    </ul>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var regionOwners = <?php echo json_encode($regionOwners); ?>;
    var regions = <?php echo json_encode($regions); ?>;
    var regionTabNames = <?php echo json_encode($regionTabNames); ?>;
    var regionOwnersTabNames = <?php echo json_encode($regionOwnersTabNames); ?>;
</script>
    <div class='tab-content ms-2' id='overview-content'>
        <div class='container-fluid'>
            <div class='row'>
                <div class='col-sm-12'>
                    <h3 style='text-align: center;'><strong>Exhibitors Overview</strong></h3>
                </div>
            </div>
            <div class='row'>
                <div class="col-sm-12">
                    <p>The Exhibitors tab handles all types of exhibitors:</p>
                    <ol>
                        <li>Artists</li>
                        <li>Dealers</li>
                        <li>Exhibits</li>
                        <li>Fan Tables</li>
                    </ol>
                    <p>There is a separate tab within the Exhibitors tab for each Exhibitor Space within the convention.</p>
                    <p>These space tabs handle:</p>
                    <ol>
                        <li>Exhibitor Management</li>
                        <li>Permission to request space in this Exhibitor Space (if required)</li>
                        <li>Status of all Space Requests</li>
                    </ol>
                </div>
            </div>
            <div class='row'>
                <div class='col-sm-12'>
                    <p>
                        <strong>NOTE:</strong> When you approve requests for a space, any request from the same exhibitor that is not approved will be cancelled when you press the save button.
                        It is necessary to approve all the spaces for an exhibitor in the same save transaction.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php
foreach ($regionOwners AS $regionOwner => $regionList) {
    $regionOwnerId = str_replace(' ', '-', $regionOwner);
    ?>
    <div class='tab-content ms-2' id='<?php echo $regionOwnerId; ?>-content' hidden>
        <ul class='nav nav-pills nav-fill  mb-3' id='<?php echo $regionOwnerId; ?>-content-tab' role='tablist'>
<?php
    $first = true;
    foreach ($regionList AS $regionId => $region) {
        $regionName = $region['name'];
        $regionNameId = str_replace(' ', '-', $regionName);
?>
            <li class='nav-item' role='presentation'>
                <button class='nav-link <?php echo $first ? 'active' : ''; ?>' id='<?php echo $regionNameId; ?>-tab' data-bs-toggle='pill' data-bs-target='#regionTypes-pane' type='button' role='tab'
                        aria-controls='<?php echo $regionOwnerId; ?>-content-tab' aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
                        onclick="exhibitors.settabRegion('<?php echo $regionNameId; ?>-pane');"><?php echo $regionName; ?>
                </button>
            </li>
<?php
        $first = false;
    }
    ?>
        </ul>
<?php
        foreach ($regionList AS $regionId => $region) {
            $regionName = $region['name'];
            $regionNameId = str_replace(' ', '-', $regionName);
?>
        <div class='tab-content ms-2' id='<?php echo $regionNameId; ?>-content'>
            <div class='container-fluid' id="<?php echo $regionNameId; ?>-div"></div>
        </div>
<?php
        }
?>
    </div>
<?php
}
?>
    <div id='result_message' class='mt-4 p-2'></div>
    <pre id='test'></pre>
</div>

<?php /*

<div id='approve_space' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Approve Vendor Space Request' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div id="approve_header" class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id="approve_title">Approve Vendor Space Request</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <form id='space_request' action='javascript:void(0)'>
                        <input type='hidden' name='vendorId' id='sr_vendorId' value=''>
                        <input type='hidden' name='spaceId' id='sr_spaceId' value=''>
                        <input type='hidden' name='id' id='sr_id' value=''>
                        <input type='hidden' name='operation' id='operation' value='approve'>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>Name:</div>
                            <div class='col-sm-10 p-0' id="sr_name"></div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>Email:</div>
                            <div class='col-sm-10 p-0' id="sr_email"></div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>Website:</div>
                            <div class='col-sm-10 p-0' id="sr_website"></div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>Space:</div>
                            <div class='col-sm-10 p-0' id='sr_spaceName'></div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-4 p-0'>Requested:</div>
                            <div class='col-sm-6 p-0'>Approved:</div>
                        </div>
                        <div class="row">
                            <div class='col-sm-1 p-0'>Units</div>
                            <div class='col-sm-3 p-0'>Description</div>
                            <div class='col-sm-6 p-0'>Approved Space</div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-1 p-0' id='sr_reqUnits'></div>
                            <div class='col-sm-3 p-0' id="sr_reqDescription"></div>
                            <div class='col-sm-6 p-0' id="sr_appOption"></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button id="approve_button" class='btn btn-sm btn-primary' onClick='approveSpace(-1)'>Approve</button>
            </div>
        </div>
    </div>
</div>
<div id='add_vendorSpace' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Add Vendor Space' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='vendorAddEditTitle'>Add Vendor Space</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <form id='add_space_form' action='javascript:void(0)'>
                        <div class="row p-1">
                            <div class='col-sm-2 ms-0 me-0 p-0 ps-2'>
                                <label for='as_vendor'>Vendor: </label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <select id="as_vendor" name="vendor">
                                    <option value="0">No Vendor Selected</option>
                                    <?php
                                    foreach ($vendorList AS $row) {
                                        echo "<option value=" . escape_quotes($row['id']) . ">" .
                                            $row['name'] . " (" . $row['website'] . "), " . $row['city'] . ',' . $row['state'] . "</option>\n";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 ms-0 me-0 p-0 ps-2'>
                                <label for='as_space'>Space: </label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <select id='as_space' name="space" onchange="selectSpaceType()">
                                    <option value='0'>No Space Selected</option>
                                    <?php
                                    foreach ($spaceList as $row) {
                                        echo '<option value="' . escape_quotes($row['id']) . '">' . $row['name'] . "</option>\n";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 ms-0 me-0 p-0 ps-2'>
                                <label for='as_spaceType'>Space Type:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <select id='as_spaceType' name="type" onchange="selectSpacePrice()">
                                    <option value='0'>No Type Selected</option>
                                </select>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 ms-0 me-0 p-0 ps-2'>
                                <label for='as_state'>Req/App/Paid:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <select id='as_state' name="state">
                                    <option value='R'>Requested</option>
                                    <option value='A'>Approved-Unpaid</option>
                                    <option value='P'>Approved-Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2 pe-2'>
                                <label for='as_included'>Included Memberships:</label>
                            </div>
                            <div class='col-sm-auto p-0 pe-1'>
                                <select id='as_included', name='included'>
                                    <option value='0'>0</option>
                                </select>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2 pe-2'>
                                <label for='as_additional'>Additional Memberships:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <select id='as_additional', name='additional' onchange="selectSpaceAdditional()">
                                    <option value='0'>0</option>
                                </select>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 ms-0 me-0 p-0 ps-2'>
                                <label for="as_totaldue">Total Amount due:</label>
                            </div>
                            <div class="col-sm-auto ms-0 me-0 p-0 pe-2">
                                <input type="text" id="as_totaldue" name="price" value="0.00" readonly/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2 pe-2'>
                                <label for='as_checkno'>Check Number:</label>
                            </div>
                            <div class='col-sm-auto p-0 pe-1'>
                                <input type="text" id='as_checkno' name='checkno' size="10" maxlength="10"/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2 pe-2'>
                                <label for='as_payment'>Amount Paid:</label>
                            </div>
                            <div class='col-sm-auto p-0'>
                                <input type='text' id='as_payment' name="payment" size='10' maxlength='10'/>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 ms-0 me-0 p-0 ps-2'>
                                <label for='as_desc'>Description:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input type='text' id='as_desc' name='description' size='32' maxlength='32'/>
                            </div>
                        </div>
                    </form>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' onClick='addVendorSpace()'>Add</button>
                </div>
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
                <div id='receipt-div'></div>
                <div id='regadminemail' hidden='true'><?php echo $conf['regadminemail']; ?></div>
                <div id="receipt-text" hidden="true"></div>
                <div id="receipt-tables" hidden="true"></div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Close</button>
                <button class='btn btn-sm btn-primary' id='emailReceipt' onClick='receipt_email("payor")'>Email Receipt</button>
                <button class='btn btn-sm btn-primary' id='emailReceiptReg' onClick='receipt_email("reg")'>Email Receipt to regadmin
                    at <?php echo $conf['regadminemail']; ?></button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div id='summary-div'></div>
    </div>
</div>
<div class='row'>
    <div class='col-sm-12'>
        <div id="VendorList">Vendor List Placeholder</div>
    </div>
</div>
<div class='row'>
    <div class='col-sm-12'>
        <button class="btn btn-secondary" id="addVendorBtn" onclick="addNewVendor();">Add New Vendor</button>
    </div>
</div>
<div class='row mt-4'>
    <div class='col-sm-12'>
        <div id="SpaceDetail">Space Detail Placeholder</div>
    </div>
</div>
<div class='row'>
    <div class='col-sm-12'>
        <button class='btn btn-secondary' id='addVendorSpaceBtn' onclick="addNewSpace();">Add New Vendor Space</button>
    </div>
</div>
<div id='result_message' class='mt-4 p-2'></div>
<pre id='test'></pre>
<?php
*/
page_foot($page);
?>
