<?php
// Registration  Portal - membershipHistory.php - display the recent years membership history for this account
require_once("lib/base.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$condata = get_con();

if (isSessionVar('id') && isSessionVar('idType')) {
    $loginType = getSessionVar('idType');
    $loginId = getSessionVar('id');
} else {
    header('location:' . $portal_conf['portalsite']);
    exit();
}

if (array_key_exists('start', $_GET))
    $start = $_GET['start'];
else
    $start = $conid;

if (array_key_exists('end', $_GET))
    $end = $_GET['end'];
else
    $end = $conid;

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = $debug['portal'];
$config_vars['conid'] = $conid;
$config_vars['uri'] = $portal_conf['portalsite'];
$config_vars['regadminemail'] = $con['regadminemail'];
$config_vars['personId'] = $loginId;
$config_vars['personType'] = $loginType;
$config_vars['start'] = $start;
$config_vars['end'] = $end;
$cdn = getTabulatorIncludes();

// build info array about the account holder
$info = getPersonInfo();
if ($info === false) {
    echo 'Invalid Login, seek assistance';
    portalPageFoot();
    exit();
}

if ($loginType == 'p') {
// get people managed by this account
// get people managed by this account holder
    $managedSQL = <<<EOS
WITH ppl AS (
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.contact_ok, p.share_reg_ok, p.managedBy, NULL AS managedByNew, p.managedReason,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        'p' AS personType
        FROM perinfo p
        WHERE managedBy = ? OR p.id = ?
    UNION
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
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
    $managedByR = dbSafeQuery($managedSQL, 'iiii', array($loginId, $loginId, $loginId, $loginId));

    $managed = [];
    if ($managedByR != false) {
        while ($p = $managedByR->fetch_assoc()) {
            $key = $p['personType'] . $p['id'];
            $managed[$key] = $p;
        }
        $managedByR->free();
    }
}

// if we get here, we are logged in and it's a purely new person or we manage the person to be processed
portalPageInit('membershipHistory', $info,
    /* css */ array($cdn['tabcss'],
        $cdn['tabbs5'],
    ),
    /* js  */ array( //$cdn['luxon'],
        $cdn['tabjs'],
        //'js/tinymce/tinymce.min.js',
        'js/base.js',
        'js/history.js',
    ),
);
// get the list of cons
$conListQ = <<<EOS
SELECT id, name, label
FROM conlist
ORDER BY id;
EOS;
$conListR = dbQuery($conListQ);
$cons = [];
while ($con = $conListR->fetch_assoc()) {
    $cons[$con['id']] = $con;
}
$conListR->free();
if ($loginType == 'n') {
?>
    <div class="row mt-3">
        <div class="col-sm-auto"><span style="font-size: 1.5rem;"><b>Membership history will not be available to you until you are assigned a permanant
                    id</b></span></div>
    </div>
<?php
    portalPageFoot();
    exit();
}
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
</script>
    <div class="row mt-3">
        <div class="col-sm-auto"><b>List of memberships for Conventions ID's from:</b></div>
        <div class="col-sm-auto">
            <select id="fromId" name="fromId">
<?php
foreach ($cons as $id => $con) {
    if ($id <= $conid + 1)
        echo '<option value="' . $id . '"' . ($id == $start ? ' selected' : '') . '>' . $con['label'] . '</option>';
}
?>
            </select>
        </div>
        <div class="col-sm-auto"> to: </div>
        <div class="col-sm-auto">
            <select id='toId' name='toId'>
<?php
foreach ($cons as $id => $con) {
    if ($id <= $conid + 1)
        echo '<option value="' . $id . '"' . ($id == $end ? '" selected' : '') . '>' . $con['label'] . '</option>';
}
?>
            </select>
        </div>
        <div class="col-sm-auto"><button class="btn btn-sm pt-0 pb-0 btn-primary" onclick="updateSelection();">Update Selection</button></div>
    </div>
<?php

// get the memberships

$membershipQ = <<<EOS
SELECT p.id AS perid, n.id AS newperid, n.perid AS nperid, r.conid, m.*
FROM reg r
JOIN memLabel m ON r.memId = m.id
LEFT OUTER JOIN perinfo p ON p.id = r.perid
LEFT OUTER JOIN newperson n ON r.newperid = n.id AND n.perid IS NULL
WHERE (r.status IN  ('unpaid', 'paid', 'plan', 'upgraded') OR r.status = NULL)
AND r.conid >= ? AND r.conid <= ?
AND ((p.managedBy = ? OR p.id = ?) OR (n.perid IS NULL AND n.managedBy = ?))
ORDER BY n.id, p.id, r.conid DESC, r.create_date ASC
EOS;
$membershipR = dbSafeQuery($membershipQ, 'iiiii', array($start, $end, $loginId, $loginId, $loginId));
$curP = null;
$curN = null;
$conLabel = '';

while ($reg = $membershipR->fetch_assoc()) {
    if ($curP != $reg['perid'] || $curN != $reg['newperid']) {
        if ($reg['nperid'] == null && $reg['newperid'] != null) {
            $key = 'n' . $reg['newperid'];
            $id = 'Temp ' . $reg['newperid'];
        } else {
            $key = 'p' . $reg['perid'];
            $id = $reg['perid'];
        }
        $person = $managed[$key];
        $curCon = 0;
?>
        <hr/>
        <div class="row mt-1">
            <div class="col-sm-1"><?php echo $id; ?></div>
            <div class="col-sm-auto"><?php echo $person['fullname'] ;?></div>
        </div>
<?php
        $conLabel = $reg['conid'];
        $curCon = $reg['conid'];
        $curP = $reg['perid'];
        $curN = $reg['newperid'];
    }
    if ($curCon != $reg['conid']) {
        $conLabel = $reg['conid'];
        $curCon = $reg['conid'];
    }
?>
    <div class="row">
        <div class="col-sm-1"></div>
        <div class="col-sm-1"><?php echo $conLabel; ?></div>
        <div class="col-sm-1"><?php echo $reg['memType']; ?></div>
        <div class="col-sm-1"><?php echo $reg['memCategory']; ?></div>
        <div class="col-sm-4"><?php echo $reg['label']; ?></div>

    </div>
<?php
    $conLabel = '';
}

$membershipR->free();

portalPageFoot();
