<?php
// Vendor - index.php - Main page for vendor registration
require_once("lib/base.php");
require_once("lib/exhibitorInvoice.php");
require_once("lib/changePassword.php");
require_once("lib/auctionItemRegistrationForms.php");
require_once('../lib/exhibitorYears.php');
require_once("../lib/exhibitorRegistrationForms.php");
require_once('../lib/exhibitorRequestForms.php');
require_once('../lib/exhibitorReceiptForms.php');
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
$exhibitor = '';

// encrypt/decrypt stuff
$ciphers = openssl_get_cipher_methods();
$cipher = 'aes-128-cbc';
$ivlen = openssl_cipher_iv_length($cipher);
$ivdate = date_create("now");
$iv = substr(date_format($ivdate, 'YmdzwLLwzdmY'), 0, $ivlen);
$key = $conid . $con['label'] . $con['regadminemail'];

$reg_link = "<a href='$regserver'>Convention Registration</a>";

if (str_starts_with($_SERVER['HTTP_HOST'], 'artist')){
    $portalName = 'Artist';
    $portalType = 'artist';
} else if (str_starts_with($_SERVER['HTTP_HOST'], 'exhibit')){
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

if (isset($_SESSION['id']) && !isset($_GET['vid'])) {
// in session, is it a logout?
    if (isset($_REQUEST['logout'])) {
        session_destroy();
        unset($_SESSION['id']);
        unset($_SESSION['eyID']);
        unset($_SESSION['login_type']);
        if ($portalType == 'vendor') {
            header('location:' . $vendor_conf['vendorsite']);
        } else {
            header('location:' . $vendor_conf['artistsite']);
        }
        exit();
    } else {
        // nope, just set the vendor id
        $exhibitor = $_SESSION['id'];
        $in_session = true;
    }
} else if (isset($_GET['vid'])) {
    $match = openssl_decrypt($_GET['vid'], $cipher, $key, 0, $iv);
    $match = json_decode($match, true);
    $timediff = time() - $match['ts'];
    web_error_log("login @ " . time() . " with ts " . $match['ts']);
    if ($timediff > 120) {
        draw_registrationModal($portalType, $portalName, $con, $countryOptions);
        draw_login($config_vars, "<div class='bg-danger text-white'>The link has expired, please log in again</div>");
        exit();
    }
    $exhibitor = $match['id'];
    $_SESSION['id'] = $exhibitor;
    $_SESSION['eyID'] = $match['eyID'];
    $_SESSION['login_type'] = $match['loginType'];
    $in_session = true;
    if ($match['loginType'] == 'e') {
        if ($match['eNeedNew']) {
            $forcePassword = true;
        }
    } else {
        if ($match['cNeedNew']) {
            $forcePassword = true;
        }
    }
    // if archived, unarchive them, they just logged in again
    if ($match['archived'] == 'Y') {
        // they were marked archived, and they logged in again, unarchive them.
        $numupd = dbSafeCmd("UPDATE exhibitors SET archived = 'N' WHERE id = ?", 'i', array($exhibitor));
        if ($numupd != 1)
            error_log("Unable to unarchive vendor $exhibitor");
    }

    // Build exhbititorYear on first login if it doesn't exist at the time of this login
    if ($match['eyID'] == NULL) {
        // create the year related functions
        $newid = exhibitorBuildYears($exhibitor);
        if (is_numeric($newid))
            $_SESSION['eyID'] = $newid;
    } else {
        $_SESSION['eyID'] = $match['eyID'];
    }
    exhibitorCheckMissingSpaces($exhibitor, $_SESSION['eyID']);
} else if (isset($_POST['si_email']) and isset($_POST['si_password'])) {
    // handle login submit
    $login = strtolower(sql_safe($_POST['si_email']));
    $loginQ = <<<EOS
SELECT e.id, e.exhibitorName, LOWER(e.exhibitorEmail) as eEmail, e.password AS ePassword, e.need_new as eNeedNew, ey.id AS eyID, 
       LOWER(ey.contactEmail) AS cEmail, ey.contactPassword AS cPassword, ey.need_new AS cNeedNew, archived, ey.needReview
FROM exhibitors e
LEFT OUTER JOIN exhibitorYears ey ON e.id = ey.exhibitorId
WHERE (e.exhibitorEmail=? OR ey.contactEmail = ?) AND conid = ?;
EOS;
    $loginR = dbSafeQuery($loginQ, 'ssi', array($login, $login, $conid));
    // find out how many match
    $matches = array();
    while ($result = $loginR->fetch_assoc()) { // check exhibitor email/password first
        $found = false;
        if ($login == $result['eEmail']) {
            if (password_verify($_POST['si_password'], $result['ePassword'])) {
                $result['loginType'] = 'e';
                $matches[] = $result;
                $found = true;
            }
        }
        if (!$found && $login == $result['cEmail']) { // try contact login second
            if (password_verify($_POST['si_password'], $result['cPassword'])) {
                $result['loginType'] = 'c';
                $matches[] = $result;
                $found = true;
            }
        }
    }
    $loginR->free();
    if (count($matches) == 0) {
        ?>
    <h2 class='warn'>Unable to Verify Password</h2>
    <?php
// not logged in, draw signup stuff
        draw_registrationModal($portalType, $portalName, $con, $countryOptions);
        draw_login($config_vars);
        exit();
    }

    if (count($matches) > 1) {
        echo "<h4>This email address has access to multiple portal accounts</h4>\n" .
            "Please select one of the accounts below:<br/><ul>\n";

        foreach ($matches as $match) {
            $match['ts'] = time();
            $string = json_encode($match);
            $string = urlencode(openssl_encrypt($string, $cipher, $key, 0, $iv));
            echo "<li><a href='?vid=$string'>" .  $match['exhibitorName'] . "</a></li>\n";
        }
?>
    </ul>
    <button class='btn btn-secondary m-1' onclick="window.location='?logout';">Logout</button>
    <script type='text/javascript'>
        var config = <?php echo json_encode($config_vars); ?>;
    </script>
    <?php
        exit();
    }

    // a single  match....
    $match = $matches[0];
    $_SESSION['id'] = $match['id'];
    $exhibitor = $_SESSION['id'];
    $_SESSION['login_type'] = $match['loginType'];
    $in_session = true;
    if ($match['loginType'] == 'e') {
        if ($match['eNeedNew']) {
            $forcePassword = true;
        }
    } else {
        if ($match['cNeedNew']) {
            $forcePassword = true;
        }
    }

    // if archived, unarchive them, they just logged in again
    if ($match['archived'] == 'Y') {
        // they were marked archived, and they logged in again, unarchive them.
        $numupd = dbSafeCmd("UPDATE exhibitors SET archived = 'N' WHERE id = ?", 'i', array($exhibitor));
        if ($numupd != 1)
            error_log("Unable to unarchive vendor $exhibitor");
    }

    // Build exhbititorYear on first login if it doesn't exist at the time of this login
    if ($match['eyID'] == NULL) {
        // create the year related functions
        $newid = exhibitorBuildYears($exhibitor);
        if (is_numeric($newid))
            $_SESSION['eyID'] = $newid;
    } else {
        $_SESSION['eyID'] = $match['eyID'];
    }
    exhibitorCheckMissingSpaces($exhibitor, $_SESSION['eyID']);
} else {
    draw_registrationModal($portalType, $portalName, $con, $countryOptions);
    draw_login($config_vars);
    exit();
}

// this section is for 'in-session' management
// build region array
$regionQ = <<<EOS
SELECT ert.portalType, ert.requestApprovalRequired, ert.purchaseApprovalRequired,ert.purchaseAreaTotals,ert.mailInAllowed, ert.mailinMaxUnits, ert.inPersonMaxUnits,
           er.name, er.shortname, er.description, er.sortorder,
           ery.ownerName, ery.ownerEmail, ery.id, ery.includedMemId, ery.additionalMemId, ery.totalUnitsAvailable, ery.conid, ery.mailinFee,
           mi.price AS includedMemPrice, ma.price AS additionalMemPrice
FROM exhibitsRegionTypes ert
JOIN exhibitsRegions er ON er.regionType = ert.regionType
JOIN exhibitsRegionYears ery ON er.id = ery.exhibitsRegion
LEFT OUTER JOIN memList mi ON (ery.includedMemId = mi.id)
LEFT OUTER JOIN memList ma ON (ery.additionalMemId = ma.id)
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
       e.addr, e.addr2, e.city, e.state, e.zip, e.country, e.shipCompany, e.shipAddr, e.shipAddr2, e.shipCity, e.shipState, e.shipZip, e.shipCountry, e.publicity,
       p.id AS perid, p.first_name AS p_first_name, p.last_name AS p_last_name, n.id AS newid, n.first_name AS n_first_name, n.last_name AS n_last_name
FROM exhibitors e
LEFT OUTER JOIN exhibitorYears ey ON e.id = ey.exhibitorId
LEFT OUTER JOIN perinfo p ON p.id = e.perid
LEFT OUTER JOIN newperson n ON n.id = e.newperid
WHERE e.id=? AND ey.conid = ?;
EOS;

$infoR = dbSafeQuery($vendorQ, 'ii', array($exhibitor, $conid));
$info = $infoR->fetch_assoc();
if ($info['eNeedNew']) {
    drawChangePassword('You need to change your exhibitor password.', 3, true, $info, 'e');
    return;
}
if ($info['cNeedNew']) {
    drawChangePassword('You need to change your contact password.', 3, true, $info, 'c');
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
SELECT exRY.*, ey.exhibitorId,
    p.id AS perid, p.first_name AS p_first_name, p.last_name AS p_last_name, n.id AS newid, n.first_name AS n_first_name, n.last_name AS n_last_name
FROM exhibitorRegionYears exRY
JOIN exhibitorYears ey ON exRY.exhibitorYearId = ey.id
JOIN exhibitsRegionYears ery ON exRY.exhibitsRegionYearId = ery.id
JOIN exhibitsRegions er ON ery.exhibitsRegion = er.id 
JOIN exhibitsRegionTypes ert ON er.regionType = ert.regionType
LEFT OUTER JOIN perinfo p ON p.id = exRY.agentPerid
LEFT OUTER JOIN newperson n ON n.id = exRY.agentNewperson
WHERE ey.exhibitorId = ? AND ert.portalType = ?;
EOS;

$vendorPR = dbSafeQuery($vendorPQ, 'is', array($exhibitor, $portalType));
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

$exhibitorSR = dbSafeQuery($exhibitorSQ, 'iis', array($exhibitor, $condata['id'], $portalType));
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
    var exhibitor_info = <?php echo json_encode($info); ?>;
    var exhibitor_spacelist = <?php echo json_encode($exhibitorSpaceList); ?>;
    var exhibitor_regionyears = <?php echo json_encode($vendor_permlist); ?>;
    var regions = <?php echo json_encode($regions); ?>;
    var spaces = <?php echo json_encode($spaces); ?>;
    var country_options = <?php echo json_encode($countryOptions); ?>;
    </script>
<?php
draw_registrationModal($portalType, $portalName, $con, $countryOptions);
draw_passwordModal();
draw_exhibitorRequestModal();
draw_exhibitorInvoiceModal($exhibitor, $info, $countryOptions, $ini, $cc, $portalName, $portalType);
draw_exhibitorReceiptModal($portalType);
draw_itemRegistrationModal($portalType, $vendor_conf['artsheets'], $vendor_conf['artcontrol']);
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
                <button class="btn btn-secondary m-1" onclick="exhibitorProfile.profileModalOpen('update');">View/Change your profile</button>
                <button class='btn btn-secondary m-1' onclick='changePasswordOpen();'>Change your password</button>
                <button class='btn btn-secondary m-1' id='switchPortalbtn' onclick='switchPortal();'>Switch to XXX Portal</button>
                <button class="btn btn-secondary m-1" onclick="window.location='?logout';">Logout</button>
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
                    <button class='btn btn-primary m-1' onclick="requestPermission(<?php echo $region['id'] . ",'" . $region['shortname'] . "_div'"; ?>);">Request Permission to apply for space in the <?php echo $region['name'];?> </button>
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
                        $foundSpace = false;
                        if (array_key_exists($regionYearId, $space_list)) {
                            foreach ($space_list[$regionYearId] as $spaceId => $space) {
                                if ($space['exhibitsRegionYear'] != $region['id'])
                                    continue;

                                $regionSpaces[$space['id']] = $space;
                                $foundSpace = true;
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
                        }

                        if ($paid > 0) {
                            vendor_receipt($regionYearId, $regionName, $regionSpaces, $exhibitorSpaceList);
                            if ($portalType == 'artist') {
                                itemRegistrationOpenBtn($regionYearId);
                            }
                        }
                        else if ($approved > 0)
                            exhibitor_showInvoice($regionYearId, $regionName, $regionSpaces, $exhibitorSpaceList, $region_list[$regionYearId], $info);
                        else if ($requested > 0)
                            exhibitor_showRequest($regionYearId, $regionName, $regionSpaces, $exhibitorSpaceList);
                        else if ($foundSpace)
                            echo "<button class='btn btn-primary m-1' onclick = 'exhibitorRequest.openReq($regionYearId, 0);' > Request $regionName Space</button>" . PHP_EOL;
                        else
                            echo "There are no requestable items currently configured for this space, please email " .
                                $region['ownerName'] . " at <a href='mailto:" . $region['ownerEmail'] . "'>" . $region['ownerEmail'] . "</a> for further assistance." . PHP_EOL;
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
         <div class='row'>
             <?php drawBug(12); ?>
         </div>
    </div>
</body>
</html>
