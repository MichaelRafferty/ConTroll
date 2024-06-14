<?php
// Registration  Portal - addUpgrade.php - add new person and membership(s) or just upgrade the memberships for an existing person you manage
require_once("lib/base.php");
require_once("lib/portalForms.php");
require_once("../lib/interests.php");
require_once("../lib/memRules.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$condata = get_con();

if (array_key_exists('id', $_SESSION) && array_key_exists('idType', $_SESSION)) {
    $personType = $_SESSION['idType'];
    $personId = $_SESSION['id'];
} else {
    header('location:' . $portal_conf['portalsite']);
    exit();
}

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = $debug['portal'];
$config_vars['conid'] = $conid;
$config_vars['uri'] = $portal_conf['portalsite'];
$config_vars['regadminemail'] = $con['regadminemail'];
$config_vars['personId'] = $personId;
$config_vars['personType'] = $personType;
$cdn = getTabulatorIncludes();

// are we add new or upgrade existing
$action = 'new';
if (array_key_exists('action', $_POST)) {
    $action = $_POST['action'];
}
// if upgrade, get parameters, if none found change to new
$upgradeType = '';
$upgradeId = -9999999;
if ($action == 'upgrade' && array_key_exists('upgradeType', $_POST)) {
    $upgradeType = $_POST['upgradeType'];
    $config_vars['upgradeType'] = $upgradeType;
} else {
    $action = 'new';
}
if ($action == 'upgrade' && array_key_exists('upgradeId', $_POST)) {
    $upgradeId = $_POST['upgradeId'];
    $config_vars['upgradeId'] = $upgradeId;
} else {
    $action = 'new';
}
$config_vars['action'] = $action;

$updateName = 'this new person';
if ($action == 'upgrade') {
    // check if we alredy manage this person
    if ($upgradeType == 'n' && $personType == 'n') {
        // both are newperson, field table is newperson and field is manangedByNew to find the managed by id
        $table = 'newperson';
        $field = 'managedByNew';
    } else if ($upgradeType == 'n') {
        $table = 'newperson';
        $field = 'managedBy'; // manager already exists
    } else {
        $table = 'perinfo';
        $field = 'managedBy';
    }
        $checkQ = <<<EOS
SELECT IFNULL($field, id) AS id,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM $table
WHERE id = ?;
EOS;
    $checkR = dbSafeQuery($checkQ, 'i', array($upgradeId));
    if ($checkR === false || $checkR->num_rows != 1) {
        header('location:' . $portal_conf['portalsite'] . '?messageFwd=' . urlencode("Person is not found, seek assistance") . '&type=e');
        exit();
    }
    $checkL = $checkR->fetch_assoc();
    if ($personId != $checkL['id']) {
        header('location:' . $portal_conf['portalsite'] . '?messageFwd=' . urlencode('You no longer manage this person') . '&type=e');
        exit();
    }
    $updateName = $checkL['fullname'];
}
// get the information for the interest block
$interests = getInterests();

// build info array about the account holder
$info = getPersonInfo();
if ($info === false) {
    echo 'Invalid Login, seek assistance';
    portalPageFoot();
    exit();
}

// get the data for the rules usage
$ruleData = getRulesData($conid);

// if we get here, we are logged in and it's a purely new person or we manage the person to be processed
portalPageInit('addUpgrade', $info['fullname'] . ($personType == 'p' ? ' (ID: ' : 'Temporary ID: ') . $personId . ')',
    /* css */ array($cdn['tabcss'],
        $cdn['tabbs5'],
    ),
    /* js  */ array( //$cdn['luxon'],
        $cdn['tabjs'],
        //'js/tinymce/tinymce.min.js',
        'js/base.js',
        'js/portal.js',
        'jslib/membershipRules.js',
        'js/memberships.js',
    ),
);
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
</script>
<?php
// get the info for the current person or set it all to NULL
$person = null;
$memberships = null;
// draw the skeleton
drawVariablePriceModal();
?>
    <div class="row mt-3">
        <div class="col-sm-12">
            <h3 id="auHeader">Creating a new person in your account</h3>
        </div>
    </div>
<?php
// step 1 - get their current age bracket (should we store this in perinfo?)
?>
    <div id="ageBracketDiv">
        <div class="row">
            <div class='col-sm-12'>
                <h3>Step 1: Verify Age</h3>
            </div>
        </div>
        <?php
        drawGetAgeBracket($updateName, $condata);
        ?>
        <hr/>
    </div>
<?php
    // step 2 - enter/verify the information for this persom
?>
<form id='addUpgradeForm' class='form-floating' action='javascript:void(0);'>
    <div id="verifyPersonDiv">
        <div class="row">
            <div class="col-sm-12">
                <h3>Step 2: Verify Personal Information</h3>
            </div>
        </div>
<?php
drawVerifyPersonInfo();
?>
        <hr/>
    </div>
</form>
<?php
// step 3 - enter/verify the interests for this persom
?>
    <div id="verifyInterestDiv">
        <div class="row">
            <div class="col-sm-12">
                <h3>Step 3: Verify Interests</h3>
            </div>
        </div>
        <?php
        drawVerifyInterestsBlock($interests);
        ?>
        <hr/>
    </div>
    <?php
// step 4 - draw the placeholder for memberships they can buy and add the memberships in the javascript
?>
    <div id="getNewMembershipDiv">
        <div class='row'>
            <div class='col-sm-12'>
                <h3>Step 4: Add new memberships</h3>
            </div>
        </div>
<?php
drawGetNewMemberships();
?>
        <hr/>
    </div>
<?php
// Only show the cart on step 4, draw the placeholder for the cart and add the current cart info in the javascript
?>
    <div id="cartDiv">
        <div class='row'>
            <div class='col-sm-12'>
                <h3>Memberships:</h3>
            </div>
        </div>
        <div id='cartContentsDiv'></div>
        <div class='row mt-3' id='step4submit'>
            <div class='col-sm-auto'>
                <button class='btn btn-sm btn-secondary' onclick='membership.gotoStep(3, true);'>Return to step 3: Interest Verification</button>
            </div>
            <div class='col-sm-auto'>
                <button class='btn btn-sm btn-primary' id='saveCartBtn' onclick='membership.saveCart();'>Return to the home page</button>
            </div>
        </div>
        <hr/>
    </div>
    <?php
// ending wrapup section php (currently none)
?>
<?php
portalPageFoot();
?>
