<?php

require_once "lib/base.php";

if (!isSessionVar('user')) {
    header("Location: /index.php");
    exit(0);
}

$con = get_conf('con');
$conid = $con['id'];
$conname = $con['conname'];
$tab = 'artsales';
$mode = 'artsales';
$method='artsales';

$region = '';
if(array_key_exists('region', $_GET)) {
    $region = $_GET['region'];
}

$page = "Point of Sale ($tab)";

if (!check_atcon($method, $conid)) {
    header('Location: /index.php');
    exit(0);
}

$con = get_conf('con');
$usps = get_conf('usps');
$vendor = get_conf('vendor');
$controll = get_conf('controll');
$atcon = get_conf('atcon');
$condata = get_con();
$conid = $con['id'];
$conname = $con['conname'];
if (array_key_exists('inlineinventory', $atcon))
    $inlineInventory = $atcon['inlineinventory'];
else
    $inlineInventory = 1;

if (array_key_exists('taxRate', $con))
    $taxRate = $con['taxRate'];
else
    $taxRate = 0;

if (array_key_exists('taxLabel', $con)) {
    $taxLabel = $con['taxLabel'];
    $taxUid = $con['taxuid'];
} else {
    $taxLabel = '';
    $taxUid = '';
}

$regionQ = <<<EOS
SELECT xR.shortname AS regionName, xRY.roomStatus
FROM exhibitsRegionTypes xRT
    JOIN exhibitsRegions xR ON xR.regionType=xRT.regionType
    JOIN exhibitsRegionYears xRY ON xRY.exhibitsRegion = xR.id
WHERE xRT.active='Y' AND xRT.usesInventory='Y' AND xRY.conid=?;
EOS;
$regionR = dbSafeQuery($regionQ, 'i', array($conid));
$setRegion = false;
if ($regionR->num_rows == 1 && $region == '') {
    $setRegion = true;
}
$roomStatus = 'all';
$regionList = [];
while ($regionInfo = $regionR->fetch_assoc()) {
    $regionList[] = $regionInfo['regionName'];
    if ($setRegion)
        $region = $regionInfo['regionName'];
    if ($region == $regionInfo['regionName'])
        $roomStatus = $regionInfo['roomStatus'];
}
$regionR->free();
setSessionVar('ARTPOSRegion', $region);

$config_vars = array ();
$config_vars['label'] = $con['label'];
$config_vars['region'] = $region;
$config_vars['conid'] = $conid;
$config_vars['regadminemail'] = $con['regadminemail'];
$config_vars['required'] = getConfValue('reg','required', 'addr');
$config_vars['taxRate'] = $taxRate;
$config_vars['taxLabel'] = $taxLabel;
$config_vars['taxUid'] = $taxUid;
$config_vars['source'] = 'artpos';
$config_vars['roomStatus'] = $roomStatus;
$config_vars['inlineInventory'] = $inlineInventory;
if (array_key_exists('creditoffline', $atcon)) {
    $config_vars['creditoffline'] = $atcon['creditoffline'];
} else {
    $config_vars['creditoffline'] = 1;
}
if (array_key_exists('creditonline', $atcon)) {
    $config_vars['creditonline'] = $atcon['creditonline'];
} else {
    $config_vars['creditonline'] = 0;
}
if (isSessionVar('terminal'))
    $config_vars['terminal'] = getSessionVar('terminal')['name'] != 'None' ? 1 : 0;
else
    $config_vars['terminal'] = 0;

$cdn = getTabulatorIncludes();
page_init($page, $tab,
    /* css */ array($cdn['tabcss'], $cdn['tabbs5']),
    /* js  */ array( ///$cdn['luxon'],
                    $cdn['tabjs'], 'js/artpos_cart.js', 'js/artpos.js'),
    $config_vars
    );
if (count($regionList) > 1) {
?>
<div id='tabs'>
    <ul class='nav nav-tabs mb-3' id='region-tabs' role='tablist'>
        <?php
            foreach ($regionList as $regionName) {
                $isRegion = $region == $regionName;
                $actual_link = $_SERVER['PHP_SELF'];
                ?>
                <li class='nav-item' role='presentation'>
                    <button class='nav-link <?php if ($isRegion) {
                        echo 'active';
                    } ?>' id='<?php echo $regionName; ?>-tab' data-bs-toggle='pill' type='button' role='tab' aria-controls='nav-<?php echo $regionName; ?>'
                            aria-selected='<?php echo $isRegion ? 'true' : 'false'; ?>'
                            onclick='window.location = "<?php echo $actual_link . '?region=' . $regionName; ?>"'>
                        <?php echo $regionName; ?>
                    </button>
                </li>
                <?php
            }
        ?>
    </ul>
</div>
<?php } ?>
<div id="pos" class="container-fluid">
    <div class="row mt-2">
        <div class="col-sm-6">
            <div id="pos-tabs">
                 <ul class="nav nav-pills mb-2" id="tab-ul" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="find-tab" data-bs-toggle="pill" data-bs-target="#find-pane" type="button" role="tab" aria-controls="nav-find" aria-selected="true">Find Customer</button>
                    </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-tab" data-bs-toggle="pill" data-bs-target="#add-pane" type="button" role="tab" aria-controls="nav-cart" aria-selected="false" disabled>Add Art to Cart</button>
                     </li>
                     <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button" role="tab" aria-controls="nav-pay" aria-selected="false" disabled>Payment</button>
                    </li>
                     <li class='nav-item' role='presentation'>
                         <button class='nav-link' id='release-tab' data-bs-toggle='pill' data-bs-target='#release-pane' type='button' role='tab' aria-controls='nav-release'
                                 aria-selected='false' disabled>Release Art
                         </button>
                     </li>
                </ul>
                <div class="tab-content" id="find-content">          
                    <div class="tab-pane fade show active" id="find-pane" role="tabpanel" aria-labelledby="person-tab" tabindex="0">
                        <div class="container-fluid">
                            <div class="row" id="stats-div"></div>
                            <div class="container-fluid" id="showStats-div"></div>
                            <div class="row">
                                <div class="col-sm-12 text-bg-primary mb-2">
                                    <div class="text-bg-primary m-2">
                                        Find person buying the items
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-4">
                                    <label for="find_perid" >Search for:</label>
                                </div>
                                <div class="col-sm-8">
                                    <input type="number" class='no-spinners' inputmode="numeric" id="find_perid" name="find_perid" size="20" placeholder="Badge ID"/>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-sm-8">
                                    <button type="button" class="btn btn-sm btn-primary" id="find_search_btn" name="find_btn" onclick="findPerson('search');">Find Person</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="add-pane" role="tabpanel" aria-labelledby="add-tab" tabindex="1">
                        <div class='container-fluid' id="add-div">
                            <form id='add-form' name='add-form' onsubmit='return false;'>
                                <div class='row' id='add_header'>
                                    <div class='col-sm-12 text-bg-primary mb-2'>
                                        <div class='text-bg-primary m-2'>
                                            Add Additional Art to Cart
                                        </div>
                                    </div>
                                </div>
                                <div class='row'>
                                    <div class='col-sm-auto ms-0 me-2 p-0'>
                                        <label for='artistNumber' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Artist Number</span></label><br/>
                                        <input type='number' name='artistNumber' id='artistNumber' inputmode='numeric' class='no-spinners' style="width: 7em;" tabindex='21'/>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-2 p-0'>
                                        <label for='pieceNumber' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Piece Number</span></label><br/>
                                        <input type='number' name='pieceNumber' id='pieceNumber' inputmode='numeric' class='no-spinners' style='width: 4em;' tabindex='22'/>
                                    </div>
                                    <div class='col-sm-auto ms-0 me-2 p-0'>
                                        <label for='unitNumber' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Unit Number</span></label><br/>
                                        <input type='number' name='unitNumber' id='unitNumber' inputmode='numeric' class='no-spinners' style='width: 4em;' tabindex='23'/>
                                    </div>
                                    <div class='col-sm-auto ms-2 me-2 p-0'>&nbsp;<br/>OR</div>
                                    <div class='col-sm-auto ms-2 me-0 p-0'>
                                        <label for='itemCode' class='form-label-sm'><span class='text-dark' style='font-size: 10pt;'>Item Code Scan</span></label><br/>
                                        <input type='text' name='itemCode' id='itemCode' size="15" maxlength="32" tabindex='24'/>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-sm-auto ms-0 me-0 p-0">
                                        <button class="btn btn-sm btn-primary" type='button' name='findArtBtn' id='findArtBtn' onclick="findArt('button')">Find Art to Add</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class='container-fluid' id='add-found-div'></div>
                    </div>
                    <div class="tab-pane fade" id="pay-pane" role="tabpanel" aria-labelledby="pay-tab" tabindex="2">
                        <div id="pay-div">Process Payment</div>
                    </div>
                    <div class='tab-pane fade' id='release-pane' role='tabpanel' aria-labelledby='release-tab' tabindex='3'>
                        <div id='release-div'></div>
                    </div>
                 </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div id="cart"></div>
            <div class="row">
                <div class="col-sm-12 mt-3">
                    <button type="button" class="btn btn-primary btn-sm" id="pay_btn" onclick="gotoPay();" hidden>Pay Cart</button>
                    <button type='button' class='btn btn-primary btn-sm' id='release_btn' onclick='gotoRelease();' hidden>Release Artwork</button>
                    <button type="button" class="btn btn-warning btn-sm" id="startover_btn" onclick="startOver(1);" hidden>Start Over</button>
                    <button type="button" class="btn btn-primary btn-sm" id="next_btn" onclick="startOver(2);" hidden>Next Customer</button>
                </div>
            </div>
        </div>       
    </div>
    <!--- search results modal -->
    <div class='modal modal-lg' id='SearchResultsModal' tabindex='-5' aria-labelledby='SearchResults' data-bs-backdrop='static'
         data-bs-keyboard='false' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='SearchResultsTitle'>
                        Find Person Search Results
                    </div>
                </div>
                <div class='modal-body' id='SearchResultsBody'>
                    <div class='row mt-3'>
                        <div class='col-sm-12 text-bg-secondary'>
                            Search Results
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-sm-12' id='find_results'>
                        </div>
                    </div>
                    <div id='searchResultMessage' class='mt-4 p-2'></div>
                </div>
                <div class='modal-footer'>
                    <button type='button' id='canceSearchResultsBtn' class='btn btn-secondary' onclick='searchResultsClose();'>
                        Retry or Cancel Search
                    </button>
                    <button type='button' id='startCheckoutBtn' class='btn btn-primary' onclick='startCheckout();'>
                        Start Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--- pay cash change modal popup -->
    <div class='modal modal-lg' id='CashChange' tabindex='-6' aria-labelledby='CashChange' data-bs-backdrop='static' data-bs-keyboard='false'
         aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='CashChangeTitle'>
                        Change due to Customer
                    </div>
                </div>
                <div class='modal-body' id='CashChangeBody'>
                </div>
                <div class='modal-footer'>
                    <button type='button' id='discard_cash_button' class='btn btn-secondary' onclick='cashChangeModal.hide();'>Cancel Cash Payment</button>
                    <button type='button' id='close_cash_button' class='btn btn-primary' onclick='pay("nomodal");'>Change given to Customer</button>
                </div>
            </div>
        </div>
    </div>
    <!--- release art modal popup -->
    <div class='modal modal-xl' id='ReleaseArt' tabindex='-7' aria-labelledby='ReleaseArt' data-bs-backdrop='static' data-bs-keyboard='false'
         aria-hidden='true' style='--bs-modal-width: 98%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='ReleaseArtTitle'></div>
                </div>
                <div class='modal-body' id='ReleaseArtBody'>
                </div>
                <div class='modal-footer'>
                    <button type='button' id='check_all_button' class='btn btn-light' onclick='releaseSetAll(true);'>Mark All Released</button>
                    <button type='button' id='clear_all_button' class='btn btn-light' onclick='releaseSetAll(false);'>Mark All Not Released</button>
                    <button type='button' id='discard_release_button' class='btn btn-secondary' onclick='releaseModal.hide();'>Cancel Release</button>
                    <button type='button' id='submit_release' class='btn btn-primary' onclick='processRelease();'>Process Release of Artwork</button>
                </div>
            </div>
        </div>
    </div>
<?php if ($inlineInventory == 1) { ?>
    <!--- inventory modal popup -->
    <div class='modal modal-xl' id='Inventory' tabindex='-8' aria-labelledby='Inventory' data-bs-backdrop='static' data-bs-keyboard='false' aria-hidden='true'
         style='--bs-modal-width: 98%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id='Update Inventory for Art Item'></div>
                </div>
                <div class='modal-body' id='InventoryBody'>
                </div>
                <div class='modal-footer'>
                    <button type='button' id='invNoChange_button' class='btn btn-primary' onclick='invUpdate(false);'>No Inventory Changes</button>
                    <button type='button' id='invChange_button' class='btn btn-primary' onclick='invUpdate(true);'>Update Inventory Record</button>
                    <button type='button' id='discardInv_button' class='btn btn-secondary' onclick='inventoryModal.hide();'>Cancel Update</button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
    <div id='result_message' class='mt-4 p-2'></div>
</div>
<pre id='test'></pre><?php
page_foot();
