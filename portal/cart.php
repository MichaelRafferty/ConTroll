<?php
// Registration  Portal - addUpgrade.php - add new person and membership(s) or just upgrade the memberships for an existing person you manage
require_once("lib/base.php");
require_once("../lib/portalForms.php");
require_once("../lib/memRules.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$condata = get_con();

if (isSessionVar('id') && isSessionVar('idType')) {
    // check for being resolved/baned
    $resolveUpdates = isResolvedBanned();
    if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
        header('location:' . $portal_conf['portalsite']);
        exit();
    }
    $loginType = getSessionVar('idType');
    $loginId = getSessionVar('id');
} else {
    header('location:' . $portal_conf['portalsite']);
    exit();
}

if (array_key_exists('multioneday', $con))
    $multiOneDay =$con['multioneday'];
else
    $multiOneDay = 0;

if (array_key_exists('oneoff', $con))
    $oneoff =$con['oneoff'];
else
    $oneoff = 0;

if (!(array_key_exists('cartId', $_POST) && array_key_exists('cartType', $_POST) && array_key_exists('action', $_POST))) {
    header('location:' . $portal_conf['portalsite'] . 'messageFwd = "Invalid call to add to cart, seek assistance"');
    exit();
}

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = getConfValue('debug', 'portal', 0);
$config_vars['conid'] = $conid;
$config_vars['uri'] = $portal_conf['portalsite'];
$config_vars['regadminemail'] = $con['regadminemail'];
$config_vars['id'] = $loginId;
$config_vars['idType'] = $loginType;
$config_vars['cartId'] = $_POST['cartId'];
$config_vars['cartType'] = $_POST['cartType'];
$config_vars['personEmail'] = getSessionVar('email');
$config_vars['required'] = getConfValue('reg', 'required', 'addr');
$config_vars['multiOneDay'] = $multiOneDay;
$config_vars['oneoff'] = $oneoff;
$cdn = getTabulatorIncludes();

// build info array about the account holder
$info = getPersonInfo($conid);
if ($info === false) {
    echo 'Invalid Login, seek assistance';
    portalPageFoot();
    exit();
}

// get the data for the rules usage
$ruleData = getRulesData($conid);

// if we get here, we are logged in and it's a purely new person or we manage the person to be processed
portalPageInit('addUpgrade', $info,
    /* css */ array($cdn['tabcss'],
        $cdn['tabbs5'],
    ),
    /* js  */ array( //$cdn['luxon'],
        $cdn['tabjs'],
        //'js/tinymce/tinymce.min.js',
        'js/portal.js',
        'jslib/membershipRules.js',
        'js/cart.js',
    ),
);
    // get the info for the current person
    $person = getPersonInfo($conid, $_POST['cartType'], $_POST['cartId']);
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var ageList = <?php echo json_encode($ruleData['ageList']); ?>;
    var ageListIdx = <?php echo json_encode($ruleData['ageListIdx']); ?>;
    var memTypes = <?php echo json_encode($ruleData['memTypes']); ?>;
    var memCategories = <?php echo json_encode($ruleData['memCategories']); ?>;
    var memList = <?php echo json_encode($ruleData['memList']); ?>;
    var memListIdx = <?php echo json_encode($ruleData['memListIdx']); ?>;
    var memRules = <?php echo json_encode($ruleData['memRules']); ?>;
    var person = <?php echo json_encode($person); ?>;
</script>
<?php

$memberships = null;
outputCustomText('main/top');
// draw the skeleton
drawVariablePriceModal('cart');
$ageName = $ruleData['ageListIdx'][$person['currentAgeType']]['shortname'] . ' [' . $ruleData['ageListIdx'][$person['currentAgeType']]['label'] . ']';
?>
    <div class="row mt-3">
        <div class="col-sm-12">
            <h1 class="size-h2" id="auHeader">
                Add/Edit Memberships and Purchases to Your Cart for
                <?php echo $person['fullName'] . ' (' . $ageName . ')'; ?>
            </h1>
        </div>
    </div>
<?php
// draw the placeholder for memberships they can buy and add the memberships in the javascript
?>
    <div id="getNewMembershipDiv">
        <div class='row'>
            <div class='col-sm-12'>
                <h2 class="size-h3">Add new memberships</h2>
            </div>
        </div>
<?php
    outputCustomText('main/memberships');
    drawGetNewMemberships();
?>
        <hr/>
    </div>
<?php
    outputCustomText('main/cart');
?>
    <div id="cartDiv">
        <div class='row'>
            <div class='col-sm-12'>
                <h2 class="size-h3">Cart:</h2>
            </div>
        </div>
        <div id='cartContentsDiv'></div>
        <div class='row' id='cartSubmit'>
            <div class='col-sm-auto mt-3'>
                <button class='btn btn-sm btn-secondary' id='discardCartBtn' onclick='cart.discardCart();'>
                    Discard Changes to the Cart and Return to the Home Page
                </button>
            </div>
            <div class='col-sm-auto mt-3'>
                <button class='btn btn-sm btn-primary' id='saveCartBtn' onclick='cart.saveCart();'>Save Cart and Return to the Home Page</button>
            </div>
        </div>
        <hr/>
    </div>
    <?php
// ending wrapup section php (currently none)
?>
<?php
portalPageFoot();
