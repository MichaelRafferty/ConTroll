<?php
// Registration  Portal - accountSettings.php - maintain the list of mananged members and the account identities email addresses
require_once("lib/base.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$condata = get_con();

if (isSessionVar('id') && isSessionVar('idType')) {
    $personType = getSessionVar('idType');
    $personId = getSessionVar('id');
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

// build info array about the account holder
$info = getPersonInfo();
if ($info === false) {
    echo 'Invalid Login, seek assistance';
    portalPageFoot();
    exit();
}

// get people managed by this account
// get people managed by this account holder
if ($personType == 'p') {
    $managedSQL = <<<EOS
WITH ppl AS (
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.managedBy, NULL AS managedByNew, p.managedReason,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        'p' AS personType
        FROM perinfo p
        WHERE managedBy = ? AND p.id != p.managedBy
    UNION
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.managedBy, p.managedByNew, p.managedReason,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
         'n' AS personType
        FROM newperson p
        WHERE managedByNew = ? AND p.id != ? AND p.perid IS NULL
)
SELECT *
FROM ppl
ORDER BY personType DESC, id ASC;
EOS;
    $managedByR = dbSafeQuery($managedSQL, 'iii', array($personId, $personId, $personId));
} else {
    $managedSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
       p.address, p.addr_2, p.city, p.state, p.zip, p.country,
       'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.managedBy, NULL AS managedByNew,
       TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
       managedReason, 'n' AS personType
FROM newperson p
WHERE p.managedByNew = ? AND p.id != p.managedByNew
ORDER BY id ASC;
EOS;
    $managedByR = dbSafeQuery($managedSQL, 'i', array($personId));
}

$managed = [];
if ($managedByR != false) {
    while ($p = $managedByR->fetch_assoc()) {
        $key = $p['personType'] . $p['id'];
        $managed[$key] = $p;
    }
    $managedByR->free();
}

// get the identities
$identitiesSQL = <<<EOS
SELECT *
FROM perinfoIdentities
WHERE perid = ?
ORDER BY email_addr, provider;
EOS;
$identitiesR = dbSafeQuery($identitiesSQL, 'i', array($personId));
$identities = [];
if ($identitiesR != false) {
    while ($p = $identitiesR->fetch_assoc()) {
        $identities[] = $p;
    }
    $identitiesR->free();
}

// if we get here, we are logged in and it's a purely new person or we manage the person to be processed
portalPageInit('accountSettings', $info,
    /* css */ array($cdn['tabcss'],
        $cdn['tabbs5'],
    ),
    /* js  */ array( //$cdn['luxon'],
        $cdn['tabjs'],
        //'js/tinymce/tinymce.min.js',
        'js/settings.js',
    ),
);
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var managed = <?php echo json_encode($managed); ?>;
    var identities = <?php echo json_encode($identities); ?>;
</script>
<?php
// get the info for the current person or set it all to NULL
$person = null;
$memberships = null;
?>
    <div class="row mt-3">
        <div class="col-sm-12">
            <h1 class="size-h3" id="auHeader">Account Settings</h1>
        </div>
    </div>
<?php
// Members Managed
?>
    <div id="managed">
        <div class='row mt-3'><h2 class="size-h4">Managed:</h2></div>
<?php
            outputCustomText('main/managed');
?>
        <div class='row'>
            <div class='col-sm-1'></div>
            <div class='col-sm-1'><b>ID</b></div>
            <div class='col-sm-3'><b>Full Name</b></div>
            <div class='col-sm-3'><b>Email Address</b></div>
            <div class='col-sm-2'><b>Management Reason</b></div>
        </div>
<?php
        foreach ($managed as $key => $person) {
            if ($person['personType'] == 'p') {
                $id = $person['id'];
            } else {
                $id = 'In Progress';
            }
?>
        <div class='row'>
            <div class='col-sm-1'>
                <button class="btn btn-warning btn-sm pt-0 pb-0" onclick="settings.disassociate('<?php echo $person['personType'] . $person['id']; ?>');">Remove</button>
            </div>
            <div class='col-sm-1'><?php echo $id; ?></div>
            <div class='col-sm-3'><?php echo $person['fullname']; ?></div>
            <div class='col-sm-3'><?php echo $person['email_addr']; ?></div>
            <div class='col-sm-2'><?php echo $person['managedReason']; ?></div>
        </div>
<?php
        }
?>
        <div class='row mt-4'>
            <div class='col-sm-auto'><button class="btn btn-sm btn-primary" id="attachBtn" onclick="settings.attach();">Request to Manage</button></div>
            <div class='col-sm-auto'><label for="acctId">Account id:</label></div>
            <div class='col-sm-auto'><input type='number' class='no-spinners' inputmode='numeric' id='acctId' name='acctId' style='width:6em;'/></div>
            <div class='col-sm-auto'><label for="emaiLAddr">Email:</label></div>
            <div class='col-sm-auto'><input type='text' id='emailAddr' name='emailAddr' size=64 maxlength=254 /></div>
        </div>
        <div class='row mt-1'>
            <div class='col-sm-1'></div>
            <div class='col-sm-auto'>An email will be sent to the email address with a link to verify that they consent to allowing you access.<br/>The account will not be added until they click on the link in that email.</div>
        </div>
        <hr/>
    </div>
<?php
// identities
if ($personType == 'p') {

?>
    <div id="identitiesDiv">
        <div class="row mt-3"><h2 class="size-h4">Identities:</h2></div>
    </div>
<?php
    outputCustomText('main/identities');
?>
        <div class="row">
            <div class='col-sm-1'></div>
            <div class='col-sm-1'><b>Provider</b></div>
            <div class='col-sm-4'><b>Email Address</b></div>
            <div class="col-sm-3"><b>Subscriber ID</b></div>
            <div class="col-sm-1"><b>Created</b></div>
            <div class="col-sm-1"><b>Last Used</b></div>
            <div class="col-sm-1"><b>Use Count</b></div>
        </div>
<?php
    foreach ($identities as $identity) {
            $createDate = date_format(date_create($identity['creationTS']), 'Y-m-d');
            $lastUsed = '';
            if ($identity['lastUseTS'] != null) {
                $lastUsed = date_format(date_create($identity['lastUseTS']), 'Y-m-d');
            }
            $key = $identity['provider'] . '~' . $identity['email_addr'];
?>
        <div class="row">
            <div class="col-sm-1"><button class="btn btn-sm btn-warning pt-0 pb-0" onclick="settings.deleteIdentity('<?php echo $key;?>')">Delete</button></div>
            <div class='col-sm-1'><?php echo $identity['provider'];?></div>
            <div class='col-sm-4'><?php echo $identity['email_addr'];?></div>
            <div class='col-sm-3'><?php echo $identity['subscriberID'];?></div>
            <div class='col-sm-1'><?php echo $createDate;?></div>
            <div class='col-sm-1'><?php echo $lastUsed;?></div>
            <div class='col-sm-1'><?php echo $identity['useCount'];?></div>
        </div>
<?php
        }
?>
        <div class='row mt-4'>
            <div class='col-sm-1'>
                <button class='btn btn-sm btn-primary' id='newIdentity' onclick='settings.newIdentity();'>Add New</button>
            </div>
            <div class='col-sm-auto'><label for='provider'>Provider:</label></div>
            <div class='col-sm-auto'><input type='text' id='provider' name='provider' size=16 maxlength=16 /></div>
            <div class='col-sm-auto'><label for="emaiLAddr">Email:</label></div>
            <div class='col-sm-auto'><input type='text' id='identityEmailAddr' name='identityEmailAddr' size=64 maxlength=254 /></div>
        </div>
        <div class="row mt-1">
            <div class="col-sm-1"></div>
            <div class="col-sm-auto">Leave the provder name blank for Authentication Link via Email.</div>
        </div>
        <div class='row mt-1'>
            <div class='col-sm-1'></div>
            <div class="col-sm-auto">Currently supported providers are: email and google.</div>
        </div>
        <div class='row mt-1'>
            <div class='col-sm-1'></div>
            <div class='col-sm-auto'>An email will be sent to the email address with a link to verify that you own that email address.<br/>The identity will not be added until you click on the link in that email.</div>
        </div>
        <hr/>
    </div>
<?php
}
portalPageFoot();
?>
