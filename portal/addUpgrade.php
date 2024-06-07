<?php
// Registration  Portal - addUpgrade.php - add new person and membership(s) or just upgrade the memberships for an existing person you manage
require_once("lib/base.php");
require_once("lib/portalForms.php");

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
$config_vars['uri'] = $portal_conf['portalsite'];
$cdn = getTabulatorIncludes();

// are we add new or upgrade existing
$action = 'new';
if (array_key_exists('action', $_POST)) {
    $action = $_POST['action'];
}
// if upgrade, get parameters, if none found change to new
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
SELECT IFNULL($field, id) FROM $table WHERE id = ?;
EOS;
    $checkR = dbSafeQuery($checkQ, 'i', array($upgradeId));
    if ($checkR === false || $checkR->num_rows != 1) {
        header('location:' . $portal_conf['portalsite'] . '?messageFwd=' . urlencode("Person is not found, seek assistance") . '&type=e');
        exit();
    }
    $checkL = $checkR->fetch_row();
    if ($personId != $checkL[0]) {
        header('location:' . $portal_conf['portalsite'] . '?messageFwd=' . urlencode('You no longer manage this person') . '&type=e');
        exit();
    }
}
// build info array about the account holder
$info = getPersonInfo();
if ($info === false) {
    echo 'Invalid Login, seek assistance';
    portalPageFoot();
    exit();
}
$info = getPersonInfo();
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
        'js/memberships.js',
    ),
);
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
</script>
<?php
// get the info for the current person or set it all to NULL
$person = null;
$memberships = null;
// for now draw the mockups
?>
<form id='addUpgradeForm' class='form-floating' action='javascript:void(0);'>
    <div class="row">
        <div class="col-sm-12">Placeholder for start of AddUpdate page</div>
    </div>
<?php
    // step 1 - enter/verify the information for this persom
?>
    <div id="verifyPersonDiv">
        <div class="row">
            <div class="col-sm-12">Verify Person Section</div>
        </div>
<?php
drawVerifyPersonInfo();
?>
    </div>
<?php
// step 2 - get their current age bracket (should we store this in perinfo?)
?>
    <div id="ageBrackedDiv">
        <div class="row">
            <div class='col-sm-12'>Verify Person Section</div>
        </div>
<?php
drawGetAgeBracket();
?>
    </div>
<?php
// step 3 - draw the placeholder for memberships they can buy and add the memberships to the javascript
?>
    <div id="getNewMembershipDiv">
        <div class='row'>
            <div class='col-sm-12'>New Membership Section</div>
        </div>
<?php
drawGetNewMemberships();
?>
    </div>
<?php
// step 4 - draw the placeholder for the cart and add the current cart info to the javascript
?>
    <div class="cartDiv">
        <div class='row'>
            <div class='col-sm-12'>Cart Section</div>
        </div>
<?php
drawCart();
?>
    </div>
    <?php
// ending wrapup section php
?>
    <div id="wrapup">
        <div class='row'>
            <div class='col-sm-12'>wrap up Section</div>
        </div>
    </div>
</form>
<?php
portalPageFoot();
?>
