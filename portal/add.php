<?php
// Registration  Portal - add.php - add new person and manage them
require_once("lib/base.php");
require_once("../lib/portalForms.php");
require_once("../lib/interests.php");
require_once("../lib/profile.php");
require_once("../lib/policies.php");

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

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = getConfValue('debug', 'portal', 0);
$config_vars['conid'] = $conid;
$config_vars['uri'] = $portal_conf['portalsite'];
$config_vars['regadminemail'] = $con['regadminemail'];
$config_vars['id'] = $loginId;
$config_vars['idType'] = $loginType;
$config_vars['personEmail'] = getSessionVar('email');
$config_vars['required'] = getConfValue('reg', 'required', 'addr');

$cdn = getTabulatorIncludes();

if ($loginType == 'n') {
    $mfield  = 'managedByNew';
} else {
    $mfield = 'managedBy';
}
// we need the list of people we are managing so we can check for matching email addresses and allow them
$emQ = <<<EOS
SELECT LOWER(email_addr) AS email_addr, 
    TRIM(REGEXP_REPLACE(CONCAT(first_name, ' ', middle_name, ' ', last_name, ' ', suffix), ' +', ' ')) AS fullName
FROM newperson
WHERE $mfield = ?
UNION SELECT LOWER(email_addr) AS email_addr,
    TRIM(REGEXP_REPLACE(CONCAT(first_name, ' ', middle_name, ' ', last_name, ' ', suffix), ' +', ' ')) AS fullName   
FROM perinfo
WHERE $mfield = ?;
EOS;
$emails = [];
$emR = dbSafeQuery($emQ, 'ii', array($loginId, $loginId));
if ($emR !== false) {
    while ($emL = $emR->fetch_assoc()) {
        $emails[$emL['email_addr']] = $emL['fullName'];
    }
    $emR->free();
}
$emails[getSessionVar('email')] = 'Yourself';
// get the information for the policies and interest blocks
$interests = getInterests();
$policies = getPolicies();
[$ageList, $ageListIdx] = getAgeList($config_vars['conid']);

// build info array about the account holder
$info = getPersonInfo($conid);
if ($info === false) {
    echo 'Invalid Login, seek assistance';
    portalPageFoot();
    exit();
}

// if we get here, we are logged in and it's a purely new person or we manage the person to be processed
portalPageInit('add', $info,
    /* css */ array(),
    /* js  */ array(
        'js/portal.js',
        'js/add.js',
    ),
);
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var ageList = <?php echo json_encode($ageList); ?>;
    var ageListIdx = <?php echo json_encode($ageListIdx); ?>;
    var ageByDate = <?php echo '"' . $condata['startdate'] . '"'; ?>;
    var policies = <?php echo json_encode($policies); ?>;
    var interests = <?php echo json_encode($interests); ?>;
    var emailsManaged = <?php echo json_encode($emails); ?>;
</script>
<?php
// get the info for the current person or set it all to NULL
$person = null;
// draw the skeleton
?>
    <div class="row mt-3">
        <div class="col-sm-12">
            <h1 class="size-h2" id="auHeader">Creating a new person in your account</h1>
        </div>
    </div>
<?php
// get the email address and check if it exists
?>
    <div id="emailDiv">
        <?php outputCustomText('email/before');?>
        <div class='row'>
            <div class='col-sm-12'>
                <h2 class='size-h3 text-primary'>Check if this new person is already in the system</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-auto"><label for="newEmailAddr">Enter the email address for this new person</label></div>
            <div class="col-sm-auto"><input type="text" size="64" maxlength="254" id="newEmailAddr" name="newEmailAddr"></div>
            <div class="col-sm-auto">
                <button class="btn btn-sm btn-primary" type="button" onclick="add.checkNewEmail(0);">Check Email Address</button>
            </div>
        </div>
        <div class="row mt-2" id="verifyMe" hidden>
            <div class='col-sm-auto'>This is an email address you manage, do you wish to create the new person with this same email address?</div>
            <div class="col-sm-auto">
                <button class="btn btn-sm btn-primary" type='button' onclick="add.checkNewEmail(1);">Yes, Use the same email address</button>
            </div>
        </div>
        <?php outputCustomText('email/after');?>
    </div>

<?php
    // step 2 - enter/verify the information for this persom
?>
    <div id='verifyPersonDiv'>
        <form id='addUpgradeForm' class='form-floating' action='javascript:void(0);'>
<?php
outputCustomText('main/step2');
drawVerifyPersonInfo($policies, $condata['startdate'], $ageList);
?>
            <hr/>
        </form>
        <?php
        if ($interests != null && count($interests) > 0) {
            drawVerifyInterestsBlock($interests);
        }
        ?>
        <div class='row'>
            <div class='col-sm-auto mt-3'>
                <button class='btn btn-sm btn-primary' id='addNewPerson' onclick='add.addPerson();'>Add New Person to Your Account</button>
            </div>
        </div>
    </div>
    <?php
// ending wrapup section php (currently none)
?>
<?php
portalPageFoot();
