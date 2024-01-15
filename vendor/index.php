<?php
// Vendor - index.php - Main page for vendor registration
require_once("lib/base.php");
require_once("../lib/cc__load_methods.php");
global $config_vars;

$cc = get_conf('cc');
$con = get_conf('con');
$conid = $con['id'];
$vendor_conf = get_conf('vendor');
$debug = get_conf('debug');
$ini = get_conf('reg');
load_cc_procs();

$condata = get_con();

$in_session = false;
$forcePassword = false;
$regserver = $ini['server'];
$vendor = '';

$reg_link = "<a href='$regserver'>Convention Registration</a>";

if (str_starts_with('artist', $_SERVER['HTTP_HOST'])){
    $portalName = 'Artist';
    $portalType = 'artist';
} else {
    $portalName = 'Vendor';
    $portalType = 'vendor';
}

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['vemail'] = $vendor_conf[$portalType];
$config_vars['portalType'] = $portalType;
$config_vars['portalName'] = $portalName;
$config_vars['artistsite'] = $vendor_conf['artistsite'];
$config_vars['vendorsite'] = $vendor_conf['vendorsite'];
$config_vars['debug'] = $debug['vendors'];

vendor_page_init($condata['label'] . " $portalName Registration");

// load country select
$countryOptions = '';
$fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
  $countryOptions .= '<option value="' . escape_quotes($data[1]) . '">' .$data[0] . '</option>' . PHP_EOL;
}
fclose($fh);
?>

<body id="vendorPortalBody">
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 p-0">
            <?php
if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') {
    if (array_key_exists('logoalt', $ini)) {
        $altstring = $ini['logoalt'];
    } else {
        $altstring = 'Logo';
    } ?>
                <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo $altstring; ?>"/>
<?php
}
if (array_key_exists('logotext', $ini) && $ini['logotext'] != '') {
    echo $ini['logotext'];
}
?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 mt-2">
            <h1><?php echo $portalName;?> Portal</h1>
        </div>
    </div>
    <?php
if ($vendor_conf['open'] == 0) { ?>
    <p class='text-primary'>The <?php echo $portalName;?> portal is currently closed. Please check the website to determine when it will open or try again tomorrow.</p>
<?php
    exit;
}
?>
    <div class="row p-1">
        <div class="col-sm-auto">
            Welcome to the <?php echo $con['label'] . ' ' . $portalName; ?>  Portal.
        </div>
    </div>
    <div class="row p-1">
        <div class="col-sm-12">
            From here you can create and manage your account for <?php echo $portalType; ?>s.
        </div>
    </div>
<?php
if ($vendor_conf['test'] == 1) {
?>
    <div class="row">
        <div class="col-sm-12">
            <h2 class='warn'>This Page is for test purposes only</h2>
        </div>
    </div>
<?php
}

if (isset($_SESSION['id'])) {
// in session, is it a logout?
    if (isset($_REQUEST['logout'])) {
        session_destroy();
        unset($_SESSION['id']);
        if ($portalType == 'vendor') {
            header('location:' . $vendor_conf['vendorsite']);
        } else {
            header('location:' . $vendor_conf['artistsite']);
        }
    } else {
        // nope, just set the vendor id
        $vendor = $_SESSION['id'];
        $in_session = true;
    }
} else if (isset($_POST['si_email']) and isset($_POST['si_password'])) {
    // handle login submit
    $login = strtolower(sql_safe($_POST['si_email']));
    $loginQ = "SELECT id, password, need_new, archived FROM vendors WHERE contactEmail=?;";
    $loginR = dbSafeQuery($loginQ, 's', array($login));
    while ($result = $loginR->fetch_assoc()) {
        if (password_verify($_POST['si_password'], $result['password'])) {
            $_SESSION['id'] = $result['id'];
            $vendor = $_SESSION['id'];
            $in_session = true;

            if ($result['archived'] == 'Y') {
                // they were marked archived and they logged in again, unarchive them.
                $numupd = dbSafeCmd("UPDATE vendors SET archived = 'N' WHERE id = ?", 'i', array($vendor));
                if ($numupd != 1)
                    error_log("Unable to unarchive vendor $vendor");
            }

            if ($result['need_new']) {
                $forcePassword = true;
            }
        } else {
            ?>
            <h2 class='warn'>Unable to Verify Password</h2>
            <?php
        }
    }
} else {
// not logged in, draw signup stuff
    draw_registrationModal($portalType, $portalName, $con, $countryOptions);
?>
    <!-- signin form (at body level) -->
    <div id='signin'>
        <div class="container-fluid form-floating">
            <div class="row mb-2">
                <div class="col-sm-auto">
                    <h4>Please log in to continue to the Portal.</h4>
                </div>
            </div>
            <form id='vendor-signin' method='POST'>
                <div class="row mt-1">
                    <div class="col-sm-1">
                        <label for="si_email">*Email/Login: </label>
                    </div>
                    <div class="col-sm-auto">
                        <input class="form-control-sm" type='email' name='si_email' id='si_email' size='40' required/>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-sm-1">
                        <label for="si_password">*Password: </label>
                    </div>
                    <div class="col-sm-auto">
                        <input class="form-control-sm" type='password' id='si_password' name='si_password' size="40" autocomplete="off" required/>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-1"></div>
                    <div class="col-sm-auto">
                        <input type='submit' class="btn btn-primary" value='signin'/> or
                            <a href='javascript:void(0)' onclick="profileModalOpen('register');">Sign Up</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div id='resetpw'>
        <div class="container-fluid">
            <div class="row mt-4">
                <div class="col-sm-auto">
                    <button class="btn btn-primary" onclick='resetPassword()'>Reset Password</button>
                </div>
            </div>
        </div>
    </div>
    <div class='container-fluid'>
        <div class="row">
            <div class="col-sm-12 m-0 p-0">
                <div id='result_message' class='mt-4 p-2'></div>
            </div>
        </div>
    </div>
    </body>
    <script type='text/javascript'>
        var config = <?php echo json_encode($config_vars); ?>;
    </script>
</html>
<?php
    exit();
}

// this section is for 'in-session' management
// build region arry
$regionQ = <<<EOS
SELECT vrt.portalType, vrt.requestApprovalRequired, vrt.purchaseApprovalRequired,vrt.purchaseAreaTotals,vrt.mailInAllowed,
           vr.name, vr.shortname, vr.description, vr.sortorder,
           vry.ownerName, vry.ownerEmail, vry.id, vry.includedMemId, vry.additionalMemId, vry.totalUnitsAvailable, vry.conid,
           mi.price AS includedMemPrice, ma.price AS additionalMemPrice
FROM vendorRegionTypes vrt
JOIN vendorRegions vr ON vr.regionType = vrt.regionType
JOIN vendorRegionYears vry ON vr.id = vry.vendorRegion
JOIN memList mi ON (vry.includedMemId = mi.id)
JOIN memList ma ON (vry.additionalMemId = ma.id)
WHERE vry.conid = ? AND vrt.portalType = ?
ORDER BY vr.sortorder;
EOS;

$regionR = dbSafeQuery($regionQ,'is',array($conid, $portalType));
$region_list = array(); // forward array, id -> data
$regions = array(); // reverse array, shortname -> id

while ($region = $regionR->fetch_assoc()) {
    $region_list[$region['id']] = $region;
    $regions[$region['shortname']] = $region['id'];
}

// build spaces array
$spaceQ = <<<EOS
SELECT v.id, v.shortname, v.name, v.description, v.unitsAvailable, v.unitsAvailableMailin, v.vendorRegionYear
FROM vendorSpaces v
JOIN vendorRegionYears vRY ON (v.vendorRegionYear = vRY.id)
JOIN vendorRegions vR ON (vRY.vendorRegion = vR.id)
JOIN vendorRegionTypes vRT ON (vR.regionType = vRT.regionType)
WHERE vRY.conid=? AND vRT.portalType = ?
ORDER BY v.vendorRegionYear, v.sortorder;
EOS;

$spaceR =  dbSafeQuery($spaceQ, 'is', array($condata['id'], $portalType));
$space_list = array();
$spaces = array();
// output the data for the scripts to use

while ($space = $spaceR->fetch_assoc()) {
    $space_list[$space['id']] = $space;
    $spaces[$space['shortname']] = $space['id'];
}

// built price lists
foreach ($space_list AS $id => $space) {
    $priceQ = <<<EOS
SELECT id, spaceId, code, description, units, price, includedMemberships, additionalMemberships, requestable, sortOrder
FROM vendorSpacePrices
WHERE spaceId=?
ORDER BY spaceId, sortOrder;
EOS;
    $priceR = dbSafeQuery($priceQ, 'i', array($space['id']));
    $price_list = array();
    while ($price = $priceR->fetch_assoc()) {
        $price_list[] = $price;
    }
    $space_list[$id]['prices'] = $price_list;
}

// get this vendor
$vendorQ = <<<EOS
SELECT vendorName, vendorEmail, vendorPhone, website, description, contactName, contactEmail, contactPhone, need_new, confirm, 
       addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, publicity
FROM vendors
WHERE id=?;
EOS;

$info = dbSafeQuery($vendorQ, 'i', array($vendor))->fetch_assoc();
if ($info['need_new']) {
    drawChangePassword('You need to change your password.', 3, true);
    return;
}

// load the country codes for the option pulldown
$fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
$countryOptions = '';
while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
    $countryOptions .=  "<option value='".$data[1]."'>".$data[0]."</option>\n";
}
fclose($fh);


?>

<script type='text/javascript'>
var config = <?php echo json_encode($config_vars); ?>;
var vendor_spaces = <?php echo json_encode($space_list); ?>;
var vendor_info = <?php echo json_encode($info); ?>;
var country_options = <?php echo json_encode($countryOptions); ?>;
</script>
<?php

$vendorPQ = <<<EOS
SELECT vrp.*
FROM vendor_requestPermissions vrp
JOIN vendorRegions vr ON vrp.regionId = vr.id
JOIN vendorRegionTypes vrt ON vr.regionType = vrt.regionType
WHERE vendorId = ? and (year = ? or year is NULL) and vrt.portalType = ?;
EOS;

$vendorPR = dbSafeQuery($vendorPQ, 'iis', array($vendor, $condata['id'], $portalType));
$vendor_permlist = array();
while ($perm = $vendorPR->fetch_assoc()) {
    $vendor_permlist[$perm['regionId']] = $perm;
}

$vendorSQ = <<<EOS
SELECT *
FROM vw_VendorSpace
WHERE vendorId = ? and yearId = ? and portalType = ?;
EOS;

$vendorSR = dbSafeQuery($vendorSQ, 'iii', array($vendor, $condata['id'], $vendor));
$vendor_spacelist = array();
while ($space = $vendorSR->fetch_assoc()) {
    $vendor_spacelist[$space['spaceId']] = $space;
}

draw_registrationModal($portalType, $portalName, $con, $countryOptions);
draw_passwordModal();
draw_vendorReqModal();
draw_vendorInvoiceModal($vendor, $info, $countryOptions, $ini, $cc);
?>
    <!-- now for the top of the form -->
     <div class='container-fluid'>
        <div class='row p-1'>
            <div class='col-sm-12 p-0'>
                <h3>Welcome to the <?php echo $portalName; ?> Portal Page for <?php echo $info['vendorName']; ?></h3>
            </div>
        </div>
        <div class="row p-1">
            <div class="col-sm-auto p-0">
                <button class="btn btn-secondary" onclick="profileModalOpen('update');">View/Change your profile</button>
                <button class='btn btn-secondary' onclick='changePasswordOpen();'>Change your password</button>
                <button class='btn btn-secondary' id='switchPortalbtn' onclick='switchPortal();'>Switch to XXX Portal</button>
                <button class="btn btn-secondary" onclick="window.location='?logout';">Logout</button>
            </div>
        </div>
        <div class="row p-1 pt-4">
            <div class="col-sm-12 p-0">
                <h3><?php echo $portalName; ?> Spaces</h3>
            </div>
        </div>
<?php   if (count($spaces) > 1)  { ?>
        <div class="row p-1">
            <div class="col-sm-12 p-0"><?php
                echo $con['label']; ?> has multiple types of spaces for <?php echo $portalName; ?>s. If you select a type for which you aren't qualified we will alert groups
                managing other spaces.
            </div>
        </div>
<?php   }

    foreach ($region_list as $id => $region) {

    /*
    foreach ($spaces AS $spacename => $spaceid) {
        $space = $space_list[$spaceid];
        if (array_key_exists($space['shortname'] . '_details', $vendor_conf)) {
            $description = $vendor_conf[$space['shortname'] . '_details'];
        } else {
            $description = $space['description'];
        }
        if (array_key_exists($spaceid, $vendor_spacelist)) {
            $vendor_space = $vendor_spacelist[$spaceid];
            $item_requested = $vendor_space['item_requested'];
        } else {
            $vendor_space = null;
            $item_requested = null;
        }
    */

        // now the fixed text
        ?>
        <div class="row pt-4 p-1">
            <div class="col-sm-auto p-0">
                <h3><?php echo $region['name'];?></h3>
            </div>
        </div>
        <div class="row p-1">
            <div class="col-sm-12 p-0">
                <?php echo $region['description'];?>
            </div>
        </div>
        <div class="row p-1 mt-2" id="<?php echo $region['shortname']; ?>_div">
            <div class="col-sm-auto p-0"><?php

            // lets see if where are authorized for this space
            if ($region['requestApprovalRequired'] != 'none') {
                if (array_key_exists($region['id'], $vendor_permlist)) {
                    $permission = $vendor_permlist['permission'];
                } else {
                    $permission = 'Y';
                }
            } else {
                $permission = 'Y';
            }

            switch ($permission) {
                case 'R': // they do not have a permission record brcause the have not requested permission yet.
                    echo "<p>Permission of " . $region['ownerName'] . " is required to apply for space in " . $region['name'] . "</p>" . PHP_EOL; ?>
                <button class='btn btn-primary' onclick="requestPermission(<?php echo $region['id']; ?>);">Request Permission to apply for space</button>
                <?php
                    break;

                case 'N': // they were answered no
                    echo "<p>You already requested permission for this space and " . $region['ownerName'] . " has denied that request.</p>" . PHP_EOL .
                        "<p>Please email " . $region['ownerName'] . " at <a href='mailto:" . $region['ownerEmail'] . "'>" . $region['ownerEmail'] . "</a>" . " if you wish to appeal this decision.</p>" . PHP_EOL;
                    break;

                case 'Y': // permission isn't needed or you have been granted permission
                    // check if they already have paid space, if so, offer to show them the receipt
                    foreach ($space_list as $spaceId => $space) {
                        if ($space['vendorRegionYear'] != $region['id'])
                            continue;

                        ob_start();
                        var_dump($space);
                        $str = ob_get_contents();
                        ob_end_clean();
                        echo "<pre>$str</pre>" . PHP_EOL;
                    }
            }
            /*
             * SELECT v.id, v.shortname, v.name, v.description, v.unitsAvailable, v.unitsAvailableMailin, v.vendorRegionYear
FROM vendorSpaces v
JOIN vendorRegionYears vRY ON (v.vendorRegionYear = vRY.id)
JOIN vendorRegions vR ON (vRY.vendorRegion = vR.id)
JOIN vendorRegionTypes vRT ON (vR.regionType = vRT.regionType)
WHERE vRY.conid=? AND vRT.portalType = ?
ORDER BY v.vendorRegionYear, v.sortorder;
EOS;

$spaceR =  dbSafeQuery($spaceQ, 'is', array($condata['id'], $portalType));
$space_list = array();
$spaces = array();
// output the data for the scripts to use

while ($space = $spaceR->fetch_assoc()) {
    $space_list[$space['id']] = $space;
    $spaces[$space['shortname']] = $space['id'];
}

// built price lists
foreach ($space_list AS $shortname => $space) {
    $priceQ = <<<EOS
SELECT id, spaceId, code, description, units, price, includedMemberships, additionalMemberships, requestable, sortOrder
FROM vendorSpacePrices
WHERE spaceId=?
ORDER BY sortOrder;
EOS;
             *
             *
        if ($vendor_space !== null) {
            if ($vendor_space['item_purchased']) {
                echo "You are registered for " . $vendor_space['purchased_description'] . "\n";
            } else if ($vendor_space['item_approved']) {
                ?>
                <button class="btn btn-primary"
                        onclick="openInvoice(<?php echo "'" . $space['id'] . "', '" . substr('0000000000' . $vendor_space['approved_sort'], -6); ?>')">
                    Pay <?php echo $space['name']; ?> Invoice</button> <?php
            } else if ($vendor_space['item_requested']) {
                echo 'Request pending authorization for ' . $vendor_space['requested_description'] . ".\n";?>
            </div>
            <div class="col-sm-auto ms-4 p-0"> <button class='btn btn-primary' onclick='openReq(<?php echo $spaceid . ", " . $vendor_space['item_requested'];?>);'>Change/Cancel  <?php echo $space['name']; ?> Space</button><?php
            } else {
                 ?>
            <button class="btn btn-primary" onclick='openReq(<?php echo $spaceid; ?>, 0);'>Request <?php echo $space['name']; ?> Space</button><?php
            }
        } else {
            ?>
            <button class="btn btn-primary" onclick='openReq(<?php echo $spaceid; ?>, 0);'>Request <?php echo $space['name']; ?> Space</button><?php
        }
            */
        ?>
            </div>
        </div>
        <?php } ?>
    </div>
     <div class='container-fluid'>
        <div class='row'>
            <div class='col-sm-12 m-0 p-0'>
                <div id='result_message' class='mt-4 p-2'></div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// draw_RegistratioModal - the modal for signup and edit profile
function draw_registrationModal($portalType, $portalName, $con, $countryOptions) {
    $vendor_conf = get_conf('vendor');
    ?>
    <!-- Registgration/Edit Registration Modal Popup -->
    <div id='profile' class="modal modal-xl fade" tabindex="-1" aria-labelledby="New Vendor" aria-hidden="true" style='--bs-modal-width: 80%;'>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class="modal-title">
                        <strong id="modalTitle">Unset Title for Profile Editing</strong>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 4px; background-color: lightcyan;">
                    <div class="container-fluid form-floating" style="background-color: lightcyan;">
                        <form id="vendorProfileForm" name="vendorProfileForm" action="javascript:void(0);" class="form-floating">
                            <input type="hidden" id='profileMode' name='profileMode' value="unknown"/>
                            <input type="hidden" id='profileType' name='profileType' value="<?php echo $portalType; ?>"/>
                            <div class="row">
                                <div class="col-sm-12" id="profileIntro">
                                    <p>This form creates an account on the <?php echo $con['conname'] . " $portalName" ?>
                                        Portal.</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <p> Please provide us with information we can use to evaluate if you qualify and how you would fit in the selection of <?php
                                        echo $portalType; ?>s at <?php echo $con['conname'];
                                        $addlkey = $portalType == 'artist' ? 'artistSignupAddltext' : 'vendorSignupAddltext';
                                        if (array_key_exists($addlkey, $vendor_conf)) {
                                            echo '<br/>' . file_get_contents('../config/'. $vendor_conf[$addlkey]);
                                        } ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row" id="creatingAccountMsg">
                                <div class="col-sm-12">Creating an account does not guarantee space.</div>
                            </div>
                            <!-- Business Info -->
                            <div class='row mt-2'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h4>Business Information</h4></div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="vendorName"> *Name: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" type='text' name='vendorName' id="vendorName" maxlength="64" size="50" tabindex="1" required
                                        placeholder="<?php echo $portalType == 'artist' ? 'Company or Artist Name' : 'Vendor, Dealer or Store name';?>"/><br/>
                                        <span class="small">This is the name that we will register your space under.</span>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='vendorEmail'> *Business Email: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='email' name='vendorEmail' id='vendorEmail' maxlength='64' size='50' required
                                        placeholder='email address for the business' tabindex="2"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='vendorPhone'> *Business Phone: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='vendorPhone' id='vendorPhone' maxlength='32' size='24' required
                                        placeholder='phone number for the business' tabindex="3"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='website'>Website: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='website' type='text' size='64' name='website'
                                        placeholder='Please enter your web, Etsy or social media site, or other appropriate URL.' tabindex="4"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='description'>*Description: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <textarea class='form-control-sm' id='description' name='description' rows=5 cols=64 required tabindex="5"></textarea>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2 p-0 ms-0 me-0 pe-2 text-end'>
                                    <input class='form-control-sm' type='checkbox' id='publicity' name='publicity' tabindex="6"/>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <label for='publicity'>Check if we may use your information to publicize your attendence at <?php echo $con['conname']; ?>, if you're
                                        coming?</label>
                                </div>
                            </div>
                            <!-- Contact Info -->
                            <div class='row mt-2'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h4>Primary Contact Information</h4></div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactName'> *Name: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='contactName' id='contactName' maxlength='64' size='50' tabindex='7' required
                                        placeholder="primary contact name"/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="contactEmail"> *Email/Login: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class='form-control-sm' type='email' name='contactEmail' id='contactEmail' maxlength='64' size='50' required
                                        placeholder="email address for Contact and Login to the portal" tabindex="8"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='contactPhone'> *Contact Phone: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' type='text' name='contactPhone' id='contactPhone' maxlength='32' size='24' required
                                        placeholder="contact's phone number" tabindex="9"/>
                                </div>
                            </div>
                            <div class="row mt-1" id="passwordLine1">
                                <div class="col-sm-2">
                                    <label for="pw1"> *Password: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='pw1' type='password' name='password' autocomplete="off" required tabindex="10"
                                    size="24" placeholder='minimum of 8 characters' />
                                </div>
                            </div>
                            <div class="row mt-1" id="passwordLine2">
                                <div class="col-sm-2">
                                    <label for="pw2"> *Confirm Password: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='pw2' type='password' name='password2' autocomplete="off" required tabindex="11"
                                    size="24" placeholder='minimum of 8 characters'/>
                                </div>
                            </div>
                            <!-- Vendor/Artist Address -->
                            <div class='row mt-2'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h4><?php echo $portalName; ?> Address</h4></div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="addr"> *Address </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='addr' type='text' size="64" name='addr' required placeholder="Street Address" tabindex="12"/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='addr2' type='text' size="64" name='addr2'
                                           placeholder="second line of address if neededsecond line of address if needed" tabindex="13"/>
                                </div>
                            </div>
                            <div class="row mt-1">
                                <div class="col-sm-2">
                                    <label for="city"> *City: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0">
                                    <input class="form-control-sm" id='city' type='text' size="32" maxlength="32" name='city' required tabindex="14"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="state"> *State: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1">
                                    <input class="form-control-sm" id='state' type='text' size="10" maxlength="16" name='state' required tabindex="15"/>
                                </div>
                                <div class="col-sm-auto ms-0 me-0 p-0 ps-2">
                                    <label for="zip"> *Zip: </label>
                                </div>
                                <div class="col-sm-auto p-0 ms-0 me-0 ps-1 pb-2">
                                    <input class="form-control-sm" id='zip' type='text' size="11" maxlength="11" name='zip' required
                                           placeholder="Postal Code" tabindex="16"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='country'> Country </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <select id='country' name='country' tabindex='17'>
                                        <?php echo $countryOptions; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Shipping Address (artist only) -->
                            <?php if ($portalType == 'artist') { ?>
                            <div class='row mt-4'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'><h4>Shipping Address</h4></div>
                                <div class='col-sm-auto p-0 ms-4 me-0'>
                                    <button class='btn btn-sm btn-primary' type="button" onclick='copyAddressToShipTo()'>Copy <?php echo $portalName; ?> Address to Shipping Address</button>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCompany'> *Company </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipCompany' type='text' size='64' name='shipCompany' required
                                           placeholder='Company Name' tabindex='17'/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipAddr'> *Address </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipAddr' type='text' size='64' name='shipAddr' required
                                           placeholder='Street Address' tabindex="17"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'></div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipAddr2' type='text' size='64' name='shipAddr2'
                                           placeholder='2nd line of address if needed' tabindex="18"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCity'> *City: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0'>
                                    <input class='form-control-sm' id='shipCity' type='text' size='32' maxlength='32' name='shipCity' required tabindex="19"/>
                                </div>
                                <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                    <label for='shipState'> *State: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                    <input class='form-control-sm' id='shipState' type='text' size='10' maxlength='16' name='shipState' required tabindex="20"/>
                                </div>
                                <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                    <label for='shipZip'> *Zip: </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <input class='form-control-sm' id='shipZip' type='text' size='11' maxlength='11' name='shipZip' required
                                           placeholder='Postal Code' tabindex="21"/>
                                </div>
                            </div>
                            <div class='row mt-1'>
                                <div class='col-sm-2'>
                                    <label for='shipCountry'> Country </label>
                                </div>
                                <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                    <select id='shipCountry' name='shipCountry' tabindex='22'>
                                        <?php echo $countryOptions; ?>
                                    </select>
                                </div>
                            </div>
                            <?php } ?>
                        </form>
                    </div>
                    <div id='au_result_message' class='mt-4 p-2'></div>
                </div>
                <div class="modal-footer">
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal' tabindex="23">Cancel</button>
                    <button class='btn btn-sm btn-primary' id='profileSubmitBtn' onClick="submitProfile('<?php echo $portalType; ?>')" tabindex="24">Unknown</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    }

// drawChangePassword - make it common code to draw change password prompts
function drawChangePassword($title, $width, $drawbutton) {
    global $config_vars;

    $html = '';
    if ($title != '') {
        $html = <<<EOH
    <div class='row'>
        <div class='col-sm-12'>$title</div>
    </div>
EOH;
        }
    $html .= <<<EOH
    <div class='container-fluid'>
        <form id='changepw' action='javascript:void(0)'>
        <div class='row'>
            <div class='col-sm-$width'>
                <label for='oldPw'>Old or Temp Password:</label>
            </div>
            <div class='col-sm-8'>
                <input type='password' id='oldPw' name='oldPassword' size="24" autocomplete="off" required/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-$width'>
                <label for='newPw'>New Password:</label>
            </div>
            <div class='col-sm-8'>
                <input type='password' id='newPw' name='password' size="24" autocomplete="off" required placeholder="minimum of 8 characters"/>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-$width'>
                <label for='newPw2'>Re-enter New Password:</label>
            </div>
            <div class='col-sm-8'>
                <input type='password' id='newPw2' name='password2' size="24" autocomplete="off" required placeholder="re-enter the password"/>
            </div>
        </div>
EOH;
    if ($drawbutton) {
        $cv = json_encode($config_vars);
        $html .= <<<EOH
        <div class='row mt-2'>
            <div class='col-sm-$width'></div>
            <div class='col-sm-8'>
                <button class='btn btn-sm btn-primary' onClick='changePassword()'>Change Password</button>
            </div>
        </div>
        </form>
        <div class="row">
            <div class="col-sm-12 m-0 p-0">
                <div id='result_message' class='mt-4 p-2'></div>
            </div>
        </div>
    </div>
    </body>
    <script type='text/javascript'>
        var config = $cv;
    </script>
</html>
EOH;
    } else {
        $html .= <<<EOH
        </form>
    </div>
EOH;
    }
    echo $html;
    //vendor_page_footer();
}

// draw the password modal
function draw_passwordModal() {
    // modals for each section
    ?>
    <!-- Change Password -->
    <div id='changePassword' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Change Vendor Account Password' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title'>
                        <strong id="changePasswordTitle">Change Vendor Account Password</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <?php drawChangePassword('', 4, false);
                    ?>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' onClick='changePassword()'>Change Password</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// draw the vendor request modal
function draw_vendorReqModal() {
    $vendor_conf = get_conf('vendor');
     ?>
    <!-- request -->
    <div id='vendor_req' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Request $spacetitle Space' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="vendor_req_title">
                        <strong>Vendor Space Request</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class='container-fluid'>
                        <form id='vendor_req_form' action='javascript:void(0)'>
                            <div class='row p-0 bg-warning'>
                                <div class='col-sm-12 p-2'>
                                    Please make sure your profile contains a good description of what you will be vending and a link for our staff to see what
                                    you sell if at all possible.
                                </div>
                            </div>
                            <div class='row p-1'>
                                <div class='col-sm-auto p-0 pe-2'>
                                    <label for='vendor_req_price_id'>How many spaces are you requesting?</label>
                                </div>
                                <div class='col-sm-auto p-0'>
                                    <select name='vendor_req_price_id' id='vendor_req_price_id'>
                                        <option value='-1'>No Space Requested</option>
                                    </select>
                                </div>
                            </div>
                            <div class='row p-1 pt-4 pb-3'>
                                <div class='col-sm-12'>
                                    You will be able to identify people for the included memberships (if any) and purchase up to the allowed number of discounted memberships later, if your request is
                                    approved.
                                </div>
                            </div>
<?php
if (array_key_exists('req_disclaimer',$vendor_conf) && $vendor_conf['req_disclaimer'] != '') {
?>                          <div class='row p-1 pt-4 pb-3'>
                                <div class='col-sm-12'>
                                    <?php echo $vendor_conf['req_disclaimer'] . "\n"; ?>
                                </div>
                            </div>
<?php
}
?>
                            <div class='row p-0 bg-warning'>
                                <div class='col-sm-auto p-2'>Completing this application does not guarantee space.</div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button class='btn btn-sm btn-primary' id='vendor_req_btn' onClick="spaceReq(0, 0)">Request Vendor Space</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function draw_vendorInvoiceModal($vendor, $info, $countryOptions, $ini, $cc) {
    $vendor_conf = get_conf('vendor');
    ?>
    <!-- invoice -->
    <div id='vendor_invoice' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Vendor Invoice' aria-hidden='true' style='--bs-modal-width: 80%;'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-bg-primary'>
                    <div class='modal-title' id="vendor_invoice_title">
                        <strong>Vendor Invoice</strong>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                    <div class="container-fluid form-floating">
                    <form id='vendor_invoice_form' class='form-floating' action='javascript:void(0);'>
                        <div class="row mb=2">
                            <div class="col-sm-12" id="vendor_inv_approved_for"></div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-12' id='vendor_inv_included'></div>
                        </div>
                        <hr/>
                        <input type='hidden' name='vendor' id='vendor_inv_id' value='<?php echo $vendor; ?>'/>
                        <input type='hidden' name='item_purchased' id='vendor_inv_item_id'/>
                        <div class="row">
                            <div class="col-sm-12">
                                <strong>Vendor Information</strong>
                                <p>Please fill out this section with information on the vendor or store.  Changes made to the Vendor Information part of this form will update your profile.</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="vendor_inv_name">Name:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                <input class="form-control-sm" type='text' name='name' id='vendor_inv_name' value="<?php echo escape_quotes($info['vendorName']);  ?>" size="64" required/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_email'>Email:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='email' id='vendor_inv_email' value="<?php echo escape_quotes($info['vendorEmail']); ?>" size="64" required/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_addr'>Address:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='addr' id='vendor_inv_addr' value="<?php echo escape_quotes($info['addr']); ?>" size='64' required/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_addr2'>Company/ Addr2:</label>
                            </div>
                            <div class='col-sm-10 p-0'>
                                <input class='form-control-sm' type='text' name='addr2' id='vendor_inv_addr2' value="<?php echo escape_quotes($info['addr2']); ?>" size='64'/>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-2'>
                                <label for='vendor_inv_city'>City: </label>
                            </div>
                            <div class='col-sm-auto p-0 me-0'>
                                <input class='form-control-sm' id='vendor_inv_city' type='text' size='32' value="<?php echo escape_quotes($info['city']); ?>" name=' city' required/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                <label for='vendor_inv_state'> State: </label>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0 ps-1'>
                                <input class='form-control-sm' id='vendor_inv_state' type='text' size='2' maxlength='2' value="<?php echo escape_quotes($info['state']); ?>"
                                       name='state' required/>
                            </div>
                            <div class='col-sm-auto ms-0 me-0 p-0 ps-2'>
                                <label for='vendor_inv_zip'> Zip: </label>
                            </div>
                            <div class='col-sm-auto p-0 ms-0 me-0 ps-1 pb-2'>
                                <input class='form-control-sm' id='vendor_inv_zip' type='text' size='11' maxlength='11' value="<?php echo escape_quotes($info['zip']); ?>" name='zip'
                                       required/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="vendor_inv_taxid"><?php echo $vendor_conf['taxidlabel']; ?>:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                <input class='form-control-sm' type='text' name='taxid'/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12"><?php echo $vendor_conf['taxidextra']; ?></div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-sm-12">
                                Cost for Spaces $<span id='dealer_space_cost'></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-2">
                                <label for="vendor_inv_requests">Special Requests:</label>
                            </div>
                            <div class="col-sm-10 p-0">
                                 <textarea class='form-control-sm' id='vendor_inv_requests' name='requests' cols="64" rows="5"></textarea>
                            </div>
                        </div>
                        <hr/>
                        <div id="vendor_inv_included_mbr"></div>
                        <div id="vendor_inv_additional_mbr"></div>
                        <div class="row">
                        <div class="row">
                            <div class="col-sm-2">
                                Cost for Memberships:
                            </div>
                            <div class="col-sm-10 p-0">
                                $<span id='vendor_inv_mbr_cost'>0</span>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="col-sm-auto">
                                Total: <span id='vendor_inv_cost'></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                Payment Information:
                            </div>
                        </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_fname'>
                                     Name:
                                 </label>
                             </div>
                             <div class='col-sm-auto pe-0'>
                                 <input type='text' name='cc_fname' id='cc_fname' required='required' placeholder='First Name' size="32" maxlength="32" />
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' name='cc_lname' id='cc_lname' required='required'  placeholder='Last Name' size='32' maxlength='32'/>
                             </div>
                         </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_street'>
                                     Street:
                                 </label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_street' required='required' name='cc_addr' size='64' maxlength='64' value="<?php echo escape_quotes($info['addr']); ?>"/>
                             </div>
                         </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_city'>City:</label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_city' required='required' size='35' name='cc_city' maxlength='64' value="<?php echo escape_quotes($info['city']); ?>"/>
                             </div>
                             <div class='col-sm-auto ps-0 pe-0'>
                                 <label for='cc_state'>State:</label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_state' size=2 maxlength="2" required='required' name='cc_state' value="<?php echo escape_quotes($info['state']); ?>"/>
                             </div>
                             <div class='col-sm-auto ps-0 pe-0'>
                                 <label for='cc_zip'>Zip:</label>
                             </div>
                             <div class='col-sm-auto'>
                                 <input type='text' id='cc_zip' required='required' size=10 maxlength="10" name='cc_zip' value="<?php echo escape_quotes($info['zip']); ?>"/>
                             </div>
                         </div>
                         <div class='row'>
                             <div class='col-sm-2'>
                                 <label for='cc_country'>Country:</label>
                             </div>
                             <div class='col-sm-auto'>
                                  <select id='cc_country' required='required' name='cc_country' size=1>
                                      <?php echo $countryOptions; ?>
                                  </select>
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-sm-2">
                                 <label for="cc_email">Email:</label>
                             </div>
                             <div class="col-sm-auto">
                                  <input type='email' id='cc_email' name='cc_email' size="35" maxlength="64" value="<?php echo escape_quotes($info['contactEmail']); ?>"/>
                             </div>
                         </div>
                         <div class='row'>
                            <div class='col-sm-12'>
                                <?php if ($ini['test'] == 1) {
                                    ?>
                                    <h2 class='warn'>This won't charge your credit card, or do anything else.</h2>
                                    <?php
                                }
                                ?>
                                <br/>
                                We Accept<br/>
                                <img src='cards_accepted_64.png' alt="Visa, Mastercard, American Express, and Discover"/>
                            </div>
                        </div>
                        <hr/>
                        <?php
if (array_key_exists('pay_disclaimer',$vendor_conf) && $vendor_conf['pay_disclaimer'] != '') {
?>                          <div class='row p-1 pt-4 pb-3'>
                                <div class='col-sm-12'>
                                    <?php echo $vendor_conf['pay_disclaimer'] . "\n"; ?>
                                </div>
                            </div>
<?php
}
?>
                        <div class="row">
                            <div class="col-sm-auto">
                                Please wait for the email, and don't click the "Purchase" button more than once.
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-sm-12'>
                                <?php echo draw_cc_html($cc, '--', 2); ?>
                                <input type='reset'/>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
<?php
}
