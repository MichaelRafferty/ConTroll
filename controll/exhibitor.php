<?php
require_once "lib/base.php";
require_once "../lib/exhibitorRegistrationForms.php";
require_once "../lib/exhibitorRequestForms.php";
require_once "../lib/exhibitorReceiptForms.php";
require_once "../lib/exhibitorInvoice.php";
require_once "lib/exhibitsConfiguration.php";
require_once "lib/exhibitorChooseExhibitor.php";

//initialize google session
$need_login = google_init("page");

$page = "exhibitor";
if(!$need_login or !checkAuth($need_login['sub'], $page)) {
    bounce_page("index.php");
}

$con = get_con();
$conid = $con['id'];
$debug = get_conf('debug');
$vendor_conf = get_conf('vendor');

$usps = get_conf('usps');
$conf = get_conf('con');
if (array_key_exists('controll_exhibitors', $debug))
    $debug_exhibitors = $debug['controll_exhibitors'];
else
    $debug_exhibitors = 0;

$required = getConfValue('reg', 'required', 'addr');
$testsite = getConfValue('vendor', 'test') == 1;

$scriptName = $_SERVER['SCRIPT_NAME'];
if (array_key_exists('tab', $_REQUEST)) {
    $initialTab = $_REQUEST['tab'];
} else {
    $initialTab = 'overview';
}

$conf = get_conf('con');

$cdn = getTabulatorIncludes();
page_init($page,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5'],'css/base.css'),
    /* js  */ array(
                    //$cdn['luxon'],
                    $cdn['tabjs'],
                    'jslib/exhibitorProfile.js',
                    'js/exhibitor.js',
                    'js/exhibitsConfiguration.js',
                    'js/exhibitorInvoice.js',
                    'js/adminCustomText.js',
                    'jslib/exhibitorRequest.js',
                    'jslib/exhibitorReceipt.js',
                    'js/tinymce/tinymce.min.js'
                   ),
              $need_login);

// to build tabs get the list of vendor types
$regionOwnerQ = <<<EOS
SELECT eR.id, eR.name, eRY.ownerName, eRT.requestApprovalRequired, eRT.purchaseApprovalRequired
FROM exhibitsRegionYears eRY
JOIN exhibitsRegions eR ON eRY.exhibitsRegion = eR.id
JOIN exhibitsRegionTypes eRT ON eR.regionType = eRT.regionType
WHERE conid = ?
ORDER BY eRY.ownerName, eR.sortorder;
EOS;
$regionOwnerR = dbSafeQuery($regionOwnerQ, 'i',array($conid));

// load country select
$countryOptions = '';
$fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
    $countryOptions .= '<option value="' . escape_quotes($data[1]) . '">' .$data[0] . '</option>' . PHP_EOL;
}
fclose($fh);

$useUSPS = false;
if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
    $useUSPS = true;

$config_vars = array();
$portalType = 'admin';
$portalName = 'Exhibitor';
$config_vars['pageName'] = 'exhibitor';
$config_vars['label'] = $con['label'];
$config_vars['vemail'] = $conf['regadminemail'];
$config_vars['portalType'] = $portalType;
$config_vars['portalName'] = $portalName;
$config_vars['artistsite'] = $vendor_conf['artistsite'];
$config_vars['vendorsite'] = $vendor_conf['vendorsite'];
$config_vars['debug'] = $debug['controll_exhibitors'];
$config_vars['conid'] = $conid;
$config_vars['required'] = $required;
$config_vars['useUSPS'] = $useUSPS;
$config_vars['initialTab'] = $initialTab;
$config_vars['scriptName'] = $scriptName;
$config_vars['regserver'] = getConfValue('reg', 'server');

bs_tinymceModal();
draw_registrationModal('admin', 'Admin', $conf, $countryOptions);
draw_exhibitorRequestModal('admin');
draw_exhibitorReceiptModal('admin');
draw_exhibitorInvoiceModal('', null, $countryOptions, $testsite, null, 'Exhibitors', 'admin');
draw_exhibitorChooseModal();
draw_exhibitsConfigurationModals();
?>
<!-- space detail modal -->
<div id='space_detail' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Space Detail' aria-hidden='true' style='--bs-modal-width: 90%;'>
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
                            <input type="text" name="locations", id="locationsVal", placeholder="Enter locations separated by commas", maxlength="512"
                                   size="90"/>
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
<div class="container-fluid" id='main'>
    <ul class='nav nav-tabs mb-3' id='exhibitor-tab' role='tablist'>
        <li class='nav-item' role='presentation'>
            <button class='nav-link active' id='overview-tab' data-bs-toggle='pill' data-bs-target='#overview-pane' type='button' role='tab'
                    aria-controls='nav-overview' aria-selected="true" onclick="exhibitors.settabOwner('overview-pane');">Overview
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='configuration-tab' data-bs-toggle='pill' data-bs-target='#configuration-pane' type='button' role='tab'
                    aria-controls='nav-configuration' aria-selected='false' onclick="exhibitors.settabOwner('configuration-pane');">Exhibits Configuration
            </button>
        </li>
        <li class='nav-item' role='presentation'>
            <button class='nav-link' id='customtext-tab' data-bs-toggle='pill' data-bs-target='#customtext-pane' type='button' role='tab'
                    aria-controls='nav-customtext' aria-selected='false' onclick="exhibitors.settabOwner('customtext-pane');">Custom Text
            </button>
        </li>
<?php
// build tab structure
$regionOwners = [];
$regionOwnersOrder = [];
$regionOwnersTabNames = [];
$regions = [];
$regionTabNames = [];
while ($regionL = $regionOwnerR->fetch_assoc()) {
    $regionOwner = $regionL['ownerName'];
    $regionOwnerId = str_replace(' ', '-', $regionOwner);
    $regionOwnersTabNames[$regionOwnerId . '-pane'] = $regionOwner;
    $regionOwners[$regionOwner][$regionL['id']] = $regionL;
    $regionOwnersOrder[$regionOwner][] = $regionL['id'];
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
                    <p>There is a separate tab within the Exhibitors tab for configuration of Exhibits Spaces, configuration of Custom Text
                        for the Exhibitor Portals, and for each Exhibitor Space within the convention.</p>
                    <p>The Exhibits configuration tab handles:</p>
                    <ol>
                        <li>Region Types - Rules configuration such as: portal type, approval requirements, mail-in among others.</li>
                        <li>Regions - Room(s) that follow the rules of a Region Type</li>
                        <li>Regions for this Year - which Room(s) are active in this years convention.  This includes the owners name and email.  It
                            also specifies the type of memberships that are available to the exhibitors in this thse rooms.
                        </li>
                        <li>Spaces within the Region - this is the types of space an exhibitor can request within these rooms.  Examples are tables, panels,
                            demo space, and anything else you can come up with.
                        </li>
                        <li>Space Pricing Options - What space within the region a exhibitor can request (1 table, 2 panels, etc.) and the price and
                            membership options for same.</li>
                    </ol>
                    <p>The Exhibitor space tabs handle:</p>
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
                        <strong>NOTE:</strong> When you approve requests for a Space in a Region, any request in that Region,
                        from the same exhibitor ,that is not approved will be cancelled when you press the save button.
                        It is necessary to approve all the Spaces in a Region for an exhibitor in the same save transaction.
                        (All of the Spaces, in that Region, for that Exhibitor will appear on the same approve request popup.)
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class='tab-pane fade' id='configuration-pane' role='tabpanel' aria-labelledby='configuration-tab' tabindex='0'></div>
    <div class='tab-pane fade' id='customtext-pane' role='tabpanel' aria-labelledby='customtext-tab' tabindex='0'></div>

    <?php
foreach ($regionOwners AS $regionOwner => $regionList) {
    $regionOwnerId = str_replace(' ', '-', $regionOwner);
    $regionOrder = $regionOwnersOrder[$regionOwner];
    ?>
    <div class='tab-content ms-2' id='<?php echo $regionOwnerId; ?>-content' hidden>
        <ul class='nav nav-pills nav-fill  mb-3' id='<?php echo $regionOwnerId; ?>-content-tab' role='tablist'>
<?php
    $first = true;
    foreach ($regionOrder AS $regionId) {
        $region = $regionList[$regionId];
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

<?php
page_foot($page);
