<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "vendor";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

page_init($page,
    /* css */ array('https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css',
                    'https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator_bootstrap5.min.css',
                    'css/base.css'
                   ),
    /* js  */ array(
                    //'https://cdn.jsdelivr.net/npm/luxon@3.1.0/build/global/luxon.min.js',
                    'https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js',
                    //'js/d3.js',
                    'js/base.js',
                    'js/vendor.js'
                   ),
              $need_login);

$con = get_con();
$conid = $con['id'];
$debug = get_conf('debug');
if (array_key_exists('reg_control_exhibitors', $debug))
    $debug_exhibitors = $debug['reg_control_exhibitors'];
else
    $debug_exhibitors = 0;

$conf = get_conf('con');

// to build tabs get the list of vendor types
$regionOwnerQ = <<<EOS
SELECT eR.id, eR.name, eRY.ownerName
FROM exhibitsRegionYears eRY
JOIN exhibitsRegions eR ON eRY.exhibitsRegion = eR.id
WHERE conid = ?
ORDER BY ownerName;
EOS;
$regionOwnerR = dbSafeQuery($regionOwnerQ, 'i',array($conid));
if ($regionOwnerR == false || $regionOwnerR->num_rows == 0) {
    echo "No exhibits are configured.";
    page_foot($page);
    return;
}
?>
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
    $regions[$regionL['name']] = [ 'regionOwner' => $regionOwner, 'id' => $regionL['id'] ];
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
$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['conid'] = $conid;
$config_vars['debug'] = $debug_exhibitors;
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
                        <li>Exhibotor Management</li>
                        <li>Permission to request space in this Exhibitor Space (if required)</li>
                        <li>Status of all Space Requests</li>
                    </ol>
                </div>
            </div>
            <div class='row'>
                <div class='col-sm-12'>
                    <p>
                        <strong>NOTE:</strong> When you approve requests for a space, any request from the same exhibior that is not approved will be cancelled when you press the save button.
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
// get the list of vendors and spaces for the add vendor space modal
$vendorListQ = "SELECT id, name, website, city, state FROM vendors ORDER BY name, city, state;";
$vendorListR = dbQuery($vendorListQ);
$vendorList = array();
while ($row = $vendorListR->fetch_assoc()) {
        $vendorList[] = $row;

}

$spaceListQ = "SELECT id, shortname, name FROM vendorSpaces ORDER BY name;";
$spaceListR = dbQuery($spaceListQ);
$spaceList = array();
while ($row = $spaceListR->fetch_assoc()) {
    $spaceList[] = $row;
}

$spacePriceListQ = <<<EOS
SELECT v.id, v.spaceId, v.description, v.price, v.includedMemberships, v.additionalMemberships, m.price AS additionalPrice
FROM vendorSpacePrices v
JOIN vendorSpaces vs ON (v.spaceId = vs.id)
JOIN memList m ON (vs.additionalMemId = m.id)
ORDER BY v.spaceId, v.sortOrder;
EOS;
$spacePriceListR = dbQuery($spacePriceListQ);
$spacePriceList = array();
while ($row = $spacePriceListR->fetch_assoc()) {
    $spacePriceList[] = $row;
}
// first the modals for use by the script
?>
<script type="text/javascript">
    <?php echo "var spacePriceList = " . json_encode($spacePriceList) . ";\n"; ?>
</script>
<div id='update_profile' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Update Vendor Profile' aria-hidden='true' style="--bs-modal-width: 80%;">
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='vendorAddEditTitle'>Update Vendor Profile</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div class='container-fluid'>
                    <form id='vendor_update' action='javascript:void(0)'>
                        <input type="hidden" name="vendorId" id="ev_vendorId" value="">
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='ev_name'>Name:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='name' id='ev_name' size='64' required/>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='ev_email'>Email:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='email' id='ev_email' size='64' required/>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='ev_website'>Website:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='website' id='ev_website' required/>
                            </div>
                        </div>
                        <div class='row p-1'>
                            <div class='col-sm-2 p-0'>
                                <label for='ev_description'>Description:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <textarea class='form-control-sm' name='description' id='ev_description' rows=5 cols=60></textarea>
                            </div>
                        </div>
                        <div class='row mt-1'>
                            <div class='col-sm-2 p-0 ms-0 me-0 pe-2 text-end'>
                                <input class='form-control-sm' type='checkbox' name='publicity' id="ev_publicity"/>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0'>
                                <label for="ev_publicity">Check if we may use your information to publicize your attendence at <?php echo $conf['conname']; ?></label>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="ev_addr" title='Street Address'>Address </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='ev_addr' type='text' size="64" name='addr' required/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="ev_addr2" title='Company Name'>Company/ Address line 2:</label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='ev_addr2' type='text' size="64" name='addr2'/>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-sm-2">
                                <label for="ev_city">City: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0">
                                <input class="form-control-sm" id='ev_city' type='text' size="32" name=' city' required/>
                            </div>
                            <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                <label for="ev_state"> State: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                <input class="form-control-sm" id='ev_state' type='text' size="2" maxlength="2" name='state' required/>
                            </div>
                            <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                <label for="ev_zip"> Zip: </label>
                            </div>
                            <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                <input class="form-control-sm" id='ev_zip' type='text' size="11" maxlength="11" name='zip' required/>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='vendorAddUpdatebtn' onClick='updateProfile()'>Update</button>
            </div>
        </div>
    </div>
</div>
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
