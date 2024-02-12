<?php
// Vendor - index.php - Main page for vendor registration
require_once("lib/base.php");
require_once("lib/vendorRequest.php");
require_once("lib/vendorInvoice.php");
require_once("lib/vendorYears.php");
require_once("lib/changePassword.php");
require_once("../lib/exhibitorRegForms.php");
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
} else if (str_starts_with('exhibit', $_SERVER['HTTP_HOST'])){
    $portalName = 'Exhibitor';
    $portalType = 'exhibitor';
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
    $loginQ = <<<EOS
SELECT e.id, e.exhibitorEmail as eEmail, e.password AS ePassword, e.need_new as eNeedNew, ey.id AS cID, 
       ey.contactEmail AS cEmail, ey.contactPassword AS cPassword, ey.need_new AS cNeedNew, archived, ey.needReview
FROM exhibitors e
LEFT OUTER JOIN exhibitorYears ey ON e.id = ey.exhibitorId
WHERE e.exhibitorEmail=? OR ey.contactEmail = ?;
EOS;
    $loginR = dbSafeQuery($loginQ, 'ss', array($login, $login));
    while ($result = $loginR->fetch_assoc()) {
        // check exhibitor email/password first
        if ($login == $result['eEmail']) {
            if (password_verify($_POST['si_password'], $result['ePassword'])) {
                $_SESSION['id'] = $result['id'];
                $vendor = $_SESSION['id'];
                $in_session = true;
                $_SESSION['login_type'] = 'e';
                if ($result['eNeedNew']) {
                    $forcePassword = true;
                }
            }
        }
        // try contact login second
        if ((!$in_session) && $login == $result['cEmail']) {
            if (password_verify($_POST['si_password'], $result['cPassword'])) {
                $_SESSION['id'] = $result['id'];
                $vendor = $_SESSION['id'];
                $in_session = true;
                $_SESSION['login_type'] = 'c';
                if ($result['cNeedNew']) {
                    $forcePassword = true;
                }
            }
        }

        if ($in_session) {
            // if archived, unarchive them, they just logged in again
            if ($result['archived'] == 'Y') {
                // they were marked archived, and they logged in again, unarchive them.
                $numupd = dbSafeCmd("UPDATE exhibitors SET archived = 'N' WHERE id = ?", 'i', array($vendor));
                if ($numupd != 1)
                    error_log("Unable to unarchive vendor $vendor");
            }

            // Build exhbititorYear on first login if it doesn't exist at the time of this login
            if ($result['cID'] == NULL) {
                // create the year related functions
                vendorBuildYears($vendor);
            } else {
                $_SESSION['cID'] = $result['cID'];
            }
            break;
        }
    }
    $loginR->free();
    if (!$in_session) {
        ?>
        <h2 class='warn'>Unable to Verify Password</h2>
        <?php
    }
}
if (!$in_session) {
// not logged in, draw signup stuff
    draw_registrationModal($portalType, $portalName, $con, $countryOptions);
    draw_login($config_vars);
    exit();
}

// this section is for 'in-session' management
// build region array
$regionQ = <<<EOS
SELECT ert.portalType, ert.requestApprovalRequired, ert.purchaseApprovalRequired,ert.purchaseAreaTotals,ert.mailInAllowed, ert.mailinMaxUnits, ert.inPersonMaxUnits,
           er.name, er.shortname, er.description, er.sortorder,
           ery.ownerName, ery.ownerEmail, ery.id, ery.includedMemId, ery.additionalMemId, ery.totalUnitsAvailable, ery.conid,
           mi.price AS includedMemPrice, ma.price AS additionalMemPrice
FROM exhibitsRegionTypes ert
JOIN exhibitsRegions er ON er.regionType = ert.regionType
JOIN exhibitsRegionYears ery ON er.id = ery.exhibitsRegion
JOIN memList mi ON (ery.includedMemId = mi.id)
JOIN memList ma ON (ery.additionalMemId = ma.id)
WHERE ery.conid = ? AND ert.portalType = ? AND ert.active = 'Y'
ORDER BY er.sortorder;
EOS;

$regionR = dbSafeQuery($regionQ,'is',array($conid, $portalType));
$region_list = array(); // forward array, id -> data
$regions = array(); // reverse array, shortname -> id

while ($region = $regionR->fetch_assoc()) {
    $region_list[$region['id']] = $region;
    $regions[$region['shortname']] = $region['id'];
}
$regionR->free();

// build spaces array
$spaceQ = <<<EOS
SELECT es.id, er.shortname as regionShortname, er.name as regionName, es.shortname as spaceShortname, es.name AS spaceName, es.description, es.unitsAvailable, es.unitsAvailableMailin, es.exhibitsRegionYear
FROM exhibitsSpaces es
JOIN exhibitsRegionYears ery ON (es.exhibitsRegionYear = ery.id)
JOIN exhibitsRegions er ON (ery.exhibitsRegion = er.id)
JOIN exhibitsRegionTypes ert ON (er.regionType = ert.regionType)
WHERE ery.conid=? AND ert.portalType = ? AND ert.active = 'Y'
ORDER BY es.exhibitsRegionYear, es.sortorder;
EOS;

$spaceR =  dbSafeQuery($spaceQ, 'is', array($condata['id'], $portalType));
$space_list = array();
$spaces = array();
// output the data for the scripts to use

while ($space = $spaceR->fetch_assoc()) {
    $space_list[$space['exhibitsRegionYear']][$space['id']] = $space;
    $spaces[$space['spaceShortname']] = array( 'region' => $space['exhibitsRegionYear'], 'space' => $space['id'] );
}
$spaceR->free();

// built price lists
foreach ($space_list AS $yearId => $regionYear) {
    foreach ($regionYear as $id => $space) {
        $priceQ = <<<EOS
SELECT p.id, p.spaceId, p.code, p.description, p.units, p.price, p.includedMemberships, p.additionalMemberships, p.requestable, p.sortOrder, es.id AS spaceId, es.exhibitsRegionYear
FROM exhibitsSpacePrices p
JOIN exhibitsSpaces es ON p.spaceId = es.id
WHERE spaceId=?
ORDER BY p.spaceId, p.sortOrder;
EOS;
        $priceR = dbSafeQuery($priceQ, 'i', array($id));
        $price_list = array();
        while ($price = $priceR->fetch_assoc()) {
            $price_list[] = $price;
        }
        $space_list[$yearId][$id]['prices'] = $price_list;
    }
}
$priceR->free();

// get this exhibitor
$vendorQ = <<<EOS
SELECT exhibitorName, exhibitorEmail, exhibitorPhone, website, description, e.need_new AS eNeedNew, e.confirm AS eConfirm, 
       ey.contactName, ey.contactEmail, ey.contactPhone, ey.need_new AS cNeedNew, ey.confirm AS cConfirm, ey.needReview, ey.mailin,
       addr, addr2, city, state, zip, country, shipCompany, shipAddr, shipAddr2, shipCity, shipState, shipZip, shipCountry, publicity
FROM exhibitors e
LEFT OUTER JOIN exhibitorYears ey ON e.id = ey.exhibitorId
WHERE e.id=? AND ey.conid = ?;
EOS;

$infoR = dbSafeQuery($vendorQ, 'ii', array($vendor, $conid));
$info = $infoR->fetch_assoc();
if ($info['eNeedNew'] || $info['cNeedNew']) {
    drawChangePassword('You need to change your password.', 3, true);
    return;
}

$infoR->free();

// load the country codes for the option pulldown
$fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
$countryOptions = '';
while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
    $countryOptions .=  "<option value='".$data[1]."'>".$data[0]."</option>\n";
}
fclose($fh);


$config_vars['loginType'] = $_SESSION['login_type'];

$vendorPQ = <<<EOS
SELECT ea.*
FROM exhibitorApprovals ea
JOIN exhibitsRegionYears ery ON ea.exhibitsRegionYearId = ery.id
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id 
JOIN exhibitsRegionTypes ert ON er.regionType = ert.regionType
WHERE exhibitorId = ? AND ert.portalType = ?;
EOS;

$vendorPR = dbSafeQuery($vendorPQ, 'is', array($vendor, $portalType));
$vendor_permlist = array();
while ($perm = $vendorPR->fetch_assoc()) {
    $vendor_permlist[$perm['exhibitsRegionYearId']] = $perm;
}
$vendorPR->free();

$exhibitorSQ = <<<EOS
SELECT *
FROM vw_ExhibitorSpace
WHERE exhibitorId = ? and conid = ? and portalType = ?;
EOS;

$exhibitorSR = dbSafeQuery($exhibitorSQ, 'iis', array($vendor, $condata['id'], $portalType));
$exhibitorSpaceList = array();
while ($space = $exhibitorSR->fetch_assoc()) {
    $exhibitorSpaceList[$space['spaceId']] = $space;
}
$exhibitorSR->free();
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var region_list = <?php echo json_encode($region_list); ?>;
    var exhibits_spaces = <?php echo json_encode($space_list); ?>;
    var vendor_info = <?php echo json_encode($info); ?>;
    var exhibitor_spacelist = <?php echo json_encode($exhibitorSpaceList); ?>;
    var regions = <?php echo json_encode($regions); ?>;
    var spaces = <?php echo json_encode($spaces); ?>;
    var country_options = <?php echo json_encode($countryOptions); ?>;
    </script>
<?php
draw_registrationModal($portalType, $portalName, $con, $countryOptions);
draw_passwordModal();
draw_vendorReqModal();
draw_vendorInvoiceModal($vendor, $info, $countryOptions, $ini, $cc, $portalName);
?>
    <!-- now for the top of the form -->
     <div class='container-fluid'>
        <div class='row p-1'>
            <div class='col-sm-12 p-0'>
                <h3>Welcome to the <?php echo $portalName; ?> Portal Page for <?php echo $info['exhibitorName']; ?></h3>
            </div>
        </div>
        <div class="row p-1">
            <div class="col-sm-auto p-0">
                <button class="btn btn-secondary" onclick="exhibitorProfile.profileModalOpen('update');">View/Change your profile</button>
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
<?php   if (count($regions) > 1)  { ?>
        <div class="row p-1">
            <div class="col-sm-12 p-0"><?php
                echo $con['label']; ?> has multiple types of spaces for <?php echo $portalName; ?>s. If you select a type for which you aren't qualified we will alert groups
                managing other spaces.
            </div>
        </div>
<?php   }

    foreach ($region_list as $id => $region) {
        // let's see if where are authorized for this space
        if ($region['mailInAllowed'] == 'N' && $info['mailin'] == 'Y')
            $permission='noMailIn';
        else if ($region['requestApprovalRequired'] != 'none') {
            if (array_key_exists($region['id'], $vendor_permlist)) {
                $permission = $vendor_permlist[$region['id']]['approval'];
            } else {
                $permission = 'approved';
            }
        } else {
            $permission = 'approved';
        }

        if ($permission != 'hide') {
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

                switch ($permission) {
                    case 'noMailIn':
                        echo "<p>This space does not allow mail-in. Please update your portal and select in person/agent if you wish to request this space</p>\n";
                        break;
                    case 'none': // they do not have a permission record brcause the have not requested permission yet.
                        echo "<p>Permission of " . $region['ownerName'] . " is required to apply for space in " . $region['name'] . "</p>" . PHP_EOL; ?>
                    <button class='btn btn-primary' onclick="requestPermission(<?php echo $region['id'] . ",'" . $region['shortname'] . "_div'"; ?>);">Request Permission to apply for space in the <?php echo $region['name'];?> </button>
                    <?php
                        break;

                    case 'requested':
                        $date = $vendor_permlist[$region['id']]['updateDate'];
                        $date = date_create($date);
                        $date = date_format($date, "F j, Y") . ' at ' . date_format($date, "g:i A");
                        echo "<p>You requested permission for this space on $date and " . $region['ownerName'] . " has not yet processed that request.</p>" . PHP_EOL .
                            '<p>Please email ' . $region['ownerName'] . " at <a href='mailto:" . $region['ownerEmail'] . "'>" . $region['ownerEmail'] . '</a>' . ' if you need to follow-up on this request.</p>' . PHP_EOL;
                        break;

                    case 'denied': // they were answered no
                        echo "<p>You already requested permission for this space and " . $region['ownerName'] . " has denied that request.</p>" . PHP_EOL .
                            "<p>Please email " . $region['ownerName'] . " at <a href='mailto:" . $region['ownerEmail'] . "'>" . $region['ownerEmail'] . "</a>" . " if you wish to appeal this decision.</p>" . PHP_EOL;
                        break;

                    case 'hide': // no longer show this exhibitor this space
                        break;

                    case 'approved': // permission isn't needed or you have been granted permission
                        // check if they already have paid space, if so, offer to show them the receipt
                        $paid = 0;
                        $requested = 0;
                        $approved = 0;
                        $timeRequested = null;
                        $regionSpaces = [];
                        $vendorSpaces = [];
                        $regionYearId = $region['id'];
                        $regionName = $region['name'];
                        foreach ($space_list[$regionYearId] as $spaceId => $space) {
                            if ($space['exhibitsRegionYear'] != $region['id'])
                                continue;

                            $regionSpaces[$space['id']] = $space;
                            if (array_key_exists($space['id'], $exhibitorSpaceList)) {
                                $vendorSpace = $exhibitorSpaceList[$space['id']];


                                if ($vendorSpace !== null) {
                                    $vendorSpaces[$space['id']] = $vendorSpace;
                                    if ($vendorSpace['item_requested'] != null) {
                                        $requested++;
                                        $timeRequested = $vendorSpace['time_requested'];
                                    }
                                    if ($vendorSpace['item_approved'] != null)
                                        $approved++;
                                    if ($vendorSpace['item_purchased'] != null)
                                        $paid++;
                                }

                            }
                        }

                        if ($paid > 0)
                            vendor_receipt($regionYearId, $regionName, $regionSpaces, $exhibitorSpaceList);
                        else if ($approved > 0)
                            vendor_showInvoice($regionYearId, $regionName, $regionSpaces, $exhibitorSpaceList);
                        else if ($requested > 0)
                            vendor_showRequest($regionYearId, $regionName, $regionSpaces, $exhibitorSpaceList);
                        else
                            echo "<button class='btn btn-primary' onclick = 'openReq($regionYearId, 0);' > Request $regionName Space</button>" . PHP_EOL;
                }
        }
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
