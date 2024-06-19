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
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.contact_ok, p.share_reg_ok, p.managedBy, NULL AS managedByNew, p.managedReason,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        'p' AS personType
        FROM perinfo p
        WHERE managedBy = ? AND p.id != p.managedBy
    UNION
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.contact_ok, p.share_reg_ok, p.managedBy, p.managedByNew, p.managedReason,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
         'n' AS personType
        FROM newperson p
        WHERE managedBy = ? AND p.id != ? AND p.perid IS NULL
)
SELECT *
FROM ppl
ORDER BY personType DESC, id ASC;
EOS;
    $managedByR = dbSafeQuery($managedSQL, 'iii', array($personId, $personId, $personId));
} else {
    $managedSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.contact_ok, p.share_reg_ok, p.managedBy, NULL AS managedByNew,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
    managedReason, 'n' AS personType
FROM newperson p
WHERE p.managedByNew = ? AND p.id != p.managedBy
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
portalPageInit('accountSettings', $info['fullname'] . ($personType == 'p' ? ' (ID: ' : 'Temporary ID: ') . $personId . ')',
    /* css */ array($cdn['tabcss'],
        $cdn['tabbs5'],
    ),
    /* js  */ array( //$cdn['luxon'],
        $cdn['tabjs'],
        //'js/tinymce/tinymce.min.js',
        'js/base.js',
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
            <h3 id="auHeader">Account Settings</h3>
        </div>
    </div>
<?php
// Members Managed
?>
    <div id="managed">
        <div class='row mt-3'><h4>Managed:</h4></div>
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
                $id = 'Temp ' . $person['id'];
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

        <hr/>
    </div>
<?php
// identities
?>
    <div id="identitiesDiv">
        <div class="row mt-3"><h4>Identities:</h4></div>
        <div class="row">
            <div class='col-sm-2'><b>Provider</b></div>
            <div class='col-sm-4'><b>Email Address</b></div>
            <div class="col-sm-3"><b>Subscriber ID</b></div>
            <div class="col-sm-1"><b>Created</b></div>
            <div class="col-sm-1"><b>Last Used</b></div>
            <div class="col-sm-1"><b>Use Count</b></div>
        </div>
<?php
        foreach ($identities as $identity) {
            $createDate = date_format(date_create($identity['creationTS']), 'Y-m-d');
            $lastUsed = date_format(date_create($identity['lastUsedTS']), 'Y-m-d');
?>
        <div class="row">
            <div class='col-sm-2'><?php echo $identity['provider'];?>></div>
            <div class='col-sm-4'><?php echo $identity['email_addr'];?></div>
            <div class='col-sm-3'><?php echo $identity['subscriberId'];?></div>
            <div class='col-sm-1'><?php echo $createDate;?></div>
            <div class='col-sm-1'><?php echo $lastUsed;?></div>
            <div class='col-sm-1'><?php echo $identity['useCount'];?></div>
        </div>
<?php
        }
?>
        <hr/>
    </div>
<?php
portalPageFoot();
?>
