<?php
// Registration  Portal - addUpgrade.php - add new person and membership(s) or just upgrade the memberships for an existing person you manage
require_once("lib/base.php");
require_once("lib/portalForms.php");
require_once("../lib/interests.php");
require_once("../lib/profile.php");
require_once("../lib/policies.php");
require_once("../lib/memRules.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
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

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = $debug['portal'];
$config_vars['conid'] = $conid;
$config_vars['uri'] = $portal_conf['portalsite'];
$config_vars['regadminemail'] = $con['regadminemail'];
$config_vars['id'] = $loginId;
$config_vars['idType'] = $loginType;
$config_vars['personEmail'] = getSessionVar('email');
$config_vars['required'] = $ini['required'];
$cdn = getTabulatorIncludes();

if ($loginType == 'n') {
    $mfield  = 'managedByNew';
} else {
    $mfield = 'managedBy';
}
// we need the list of people we are managing so we can check for matching email addresses and allow them
$emQ = <<<EOS
SELECT LOWER(email_addr) AS email_addr,
TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM newperson
WHERE $mfield = ?
UNION SELECT LOWER(email_addr) AS email_addr,
TRIM(REGEXP_REPLACE(CONCAT(IFNULL(first_name, ''),' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''), ' ', IFNULL(suffix, '')), '  *', ' ')) AS fullname
FROM perinfo
WHERE $mfield = ?;
EOS;
$emails = [];
$emR = dbSafeQuery($emQ, 'ii', array($loginId, $loginId));
if ($emR !== false) {
    while ($emL = $emR->fetch_assoc()) {
        $emails[$emL['email_addr']] = $emL['fullname'];
    }
    $emR->free();
}
$emails[getSessionVar('email')] = 'Yourself';

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
    if ($upgradeType == 'n' && $loginType == 'n') {
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
SELECT IFNULL($field, -1) AS mid, id,
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
    if ($loginId != $checkL['id'] && $loginId != $checkL['mid']) {
        header('location:' . $portal_conf['portalsite'] . '?messageFwd=' . urlencode('You no longer manage this person') . '&type=e');
        exit();
    }
    $updateName = $checkL['fullname'];
}
// get the information for the policies and interest blocks
$interests = getInterests();
$policies = getPolicies();

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
    var policies = <?php echo json_encode($policies); ?>;
    var emailsManaged = <?php echo json_encode($emails); ?>;
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
            <h1 class="size-h2" id="auHeader">Creating a new person in your account and buy them memberships</h1>
        </div>
    </div>
<?php
// for new additions, step 0 is get the email address and check if it exists
// step 0
?>
    <div id="emailDiv">
        <div class='row'>
            <div class='col-sm-12'>
                <h2 class='size-h3 text-primary'>Check if this new person is already in the system</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-auto">Enter the email address for this new person</div>
            <div class="col-sm-auto"><input type="text" size="64", maxlength="254" id="newEmailAddr", name="newEmailAddr"></div>
            <div class="col-sm-auto"><button class="btn btn-sm btn-primary" type="button" onclick="membership.checkNewEmail(0);">Check Email
                    Address</button></div>
        </div>
        <div class="row mt-2" id="verifyMe" hidden>
            <div class='col-sm-auto'>This is an email address you manage, do you wish to create the new person with this same email address?</div>
            <div class="col-sm-auto">
                <button class="btn btn-sm btn-primary" type='button' onclick="membership.checkNewEmail(1);">Yes, Use the same email address</button>
            </div>
        </div>
        <?php outputCustomText('main/step0');?>
    </div>

<?php
// step 1 - get their current age bracket (should we store this in perinfo?)
?>
    <div id="ageBracketDiv">
        <div class="row">
            <div class='col-sm-12'>
                <h3 class="size-h3">Age Verification</h3>
            </div>
        </div>
        <?php
        outputCustomText('main/step1');
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
                <h2 class="size-h3">Verify Personal Information</h2>
            </div>
        </div>
<?php
outputCustomText('main/step2');
drawVerifyPersonInfo($policies);
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
                <h2 class="size-h3">Verify Interests</h2>
            </div>
        </div>
        <?php
        if ($interests != null) {
            outputCustomText('main/step3');
            drawVerifyInterestsBlock($interests);
        }
        ?>
        <hr/>
    </div>
    <?php
    if ($interests == null) {
        $step3num = 2;
        $step4num = 3;
    } else {
        $step3num = 3;
        $step4num = 4;
    }
// step 4 - draw the placeholder for memberships they can buy and add the memberships in the javascript
?>
    <div id="getNewMembershipDiv">
        <div class='row'>
            <div class='col-sm-12'>
                <h2 class="size-h3">Add new memberships</h2>
            </div>
        </div>
<?php
    outputCustomText('main/step4');
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
                <h2 class="size-h3">Cart:</h2>
            </div>
        </div>
        <div id='cartContentsDiv'></div>
        <div class='row' id='step4submit'>

<?php if ($step3num == 3) { ?>
            <div class='col-sm-auto  mt-3'>
                <button class='btn btn-sm btn-secondary' onclick='membership.gotoStep(3, true);'>Return to Interest Verification</button>
            </div>
<?php } ?>
            <div class='col-sm-auto mt-3'>
                <button class='btn btn-sm btn-secondary' onclick='membership.gotoStep(2, true);'>Return to Personal Information Verification</button>
            </div>
            <div class='col-sm-auto mt-3'>
                <button class='btn btn-sm btn-secondary' onclick='membership.gotoStep(1, true);'>Return to Age Verification</button>
            </div>
            <div class='col-sm-auto mt-3'>
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
