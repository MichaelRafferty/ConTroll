<?php
// Registration  Portal - index.php - Main page for the membership portal
require_once("lib/base.php");
require_once("lib/getLoginMatch.php");
require_once("lib/registrationForms.php");

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

// load country select
$countryOptions = '';
$fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
  $countryOptions .= '<option value="' . escape_quotes($data[1]) . '">' .$data[0] . '</option>' . PHP_EOL;
}
fclose($fh);

// this section is for 'in-session' management
// build info array

if ($personType == 'p') {
    $personSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.contact_ok, p.share_reg_ok, p.managedBy,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(pm.first_name, ''),' ', IFNULL(pm.middle_name, ''), ' ', IFNULL(pm.last_name, ''), ' ', IFNULL(pm.suffix, '')), '  *', ' ')) AS managedByName
    FROM perinfo p
    LEFT OUTER JOIN perinfo pm ON p.managedBy = pm.id
    WHERE p.id = ?;
EOS;
} else {
    $personSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    'N' AS banned, p.createtime AS creation_date, 'Y' AS active, p.contact_ok, p.share_reg_ok, p.managedByNew, p.managedBy,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(pm.first_name, ''),' ', IFNULL(pm.middle_name, ''), ' ', IFNULL(pm.last_name, ''), ' ', IFNULL(pm.suffix, '')), '  *', ' ')) AS managedByName
    FROM newperson p
    LEFT OUTER JOIN newperson pm ON p.managedByNew = pm.id
    WHERE p.id = ?;
EOS;
}
$personR = dbSafeQuery($personSQL, 'i', array($personId));
if ($personR === false || $personR->num_rows == 0) {
    echo 'Invalid Login, seek assistance';
    portal_page_foot();
    exit();
}
$info = $personR->fetch_assoc();
$personR->free();
$ownerRegSQL = <<<EOS
SELECT r.status, r.memId, m.*
FROM reg r
JOIN memLabel m ON m.id = r.memId
WHERE r.conid = ? AND (r.perid = ? OR r.newperid = ?);
EOS;
$ownerRegR = dbSafeQuery($ownerRegSQL, 'iii', array($conid, $personType == 'p' ? $personId : -1, $personType == 'n' ? $personId : -1));
if ($ownerRegR == false || $ownerRegR->num_rows == 0) {
    $ownerMemberhip = 'None';
} else {
    $ownerMembership = '';
    while ($ownerL = $ownerRegR->fetch_assoc()) {
        if ($ownerMembership != '')
            $ownerMembership .= '<br/>';
        $ownerMembership .= $ownerL['label'] . '[' . $ownerL['status'] . ']';
    }
    if ($ownerRegR != false)
        $ownerRegR->free();
}
if ($personType == 'p') {
    $managedSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.contact_ok, p.share_reg_ok, p.managedBy, NULL AS managedByNew,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
    r.status, r.memId, m.memCategory, m.memType, m.memAge, m.shortname, m. label, m.memGroup, 'p' AS personType
    FROM perinfo p
    LEFT OUTER JOIN reg r ON p.id = r.perid AND r.conid = ?
    LEFT OUTER JOIN memLabel m ON m.id = r.memId
    WHERE managedBy = ? AND p.id != p.managedBy
UNION
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.contact_ok, p.share_reg_ok, p.managedBy, p.managedByNew,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
    r.status, r.memId, m.memCategory, m.memType, m.memAge, m.shortname, m. label, m.memGroup, 'n' AS personType
    FROM newperson p    
    LEFT OUTER JOIN reg r ON p.id = r.newperid AND r.conid = ?
    LEFT OUTER JOIN memLabel m ON m.id = r.memId
    WHERE managedBy = ? AND p.managedBy != ? AND p.perid IS NULL;
EOS;
    $managedByR = dbSafeQuery($managedSQL, 'iiiii', array($conid, $personId, $conid, $personId, $personId));
} else {
    $managedSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.contact_ok, p.share_reg_ok, p.managedBy, NULL AS managedByNew,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
    r.status, r.memId, m.memCategory, m.memType, m.memAge, m.shortname, m. label, m.memGroup, 'n' AS personType
    FROM newperson p
    LEFT OUTER JOIN reg r ON p.id = r.newperid AND r.conid = ?
    LEFT OUTER JOIN memLabel m ON m.id = r.memId
    WHERE p.managedByNew = ? AND p.id != p.managedBy;
EOS;
    $managedByR = dbSafeQuery($managedSQL, 'iiii', array($conid, $personId, $conid, $personId));
}

$managed = [];
if ($managedByR != false) {
    while ($p = $managedByR->fetch_assoc()) {
        $managed[] = $p;
    }
    $managedByR->free();
}

portal_page_init($info['fullname'] . ($personType == 'p' ? ' (ID: ' : 'Temporary ID: ') . $personId . ')',
    /* css */ array($cdn['tabcss'],
        $cdn['tabbs5'],
    ),
    /* js  */ array( //$cdn['luxon'],
        $cdn['tabjs'],
        //'js/tinymce/tinymce.min.js',
        'js/base.js',
        'js/portal.js',
    ),
);
?>
    <?php
if ($portal_conf['open'] == 0) { ?>
    <p class='text-primary'>The membership portal is currently closed. Please check the website to determine when it will open or try again tomorrow.</p>
<?php
    exit;
}

if ($info['managedByName'] != null) {
    ?>
<div class='row mt-4'>
    <div class='col-sm-auto'><b>This person record is managed by <?php echo $info['managedByName']; ?></b></div>
    <div class='col-sm-auto'><button class="btn btn-warning btn-small p-1" onclick="disassociate();">Dissociate from <?php echo $info['managedByName']; ?></button></div>
</div>
<?php } ?>
<div class='row mt-4'>
    <div class='col-sm-12'><h3>People managed by this account:</h3></div>
</div>
<div class="row">
    <div class="col-sm-1" style='text-align: right;'><b>ID</b></div>
    <div class="col-sm-4"><b>Person</b></div>
    <div class="col-sm-3"><b>Memberships</b></div>
    <div class="col-sm-4"><b>Actions</b></div>
</div>
<div class="row">
    <div class='col-sm-1' style='text-align: right;'><?php echo ($personType == 'n' ? 'Temp ' : '') . $personId; ?></div>
    <div class='col-sm-4'><?php echo $info['fullname']; ?></div>
    <div class="col-sm-3"><?php echo $ownerMemberhip; ?></div>
    <div class='col-sm-4 p-1'>
        <button class='btn btn-small btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="editPerson(<?php echo $personId . ",'" . $personType . "'"; ?>);">Edit Person Record</button>
        <button class='btn btn-small btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="addMembership(<?php echo $personId . ",'" . $personType . "'"; ?>);">Add/Upgrade Memberships</button>
    </div>
</div>
<?php
if (count($managed) > 0) {
    foreach ($managed as $m) {
        ?>
    <div class='row'>
        <div class='col-sm-1' style='text-align: right;'><?php echo ($m['personType'] == 'n' ? 'Temp ' : '') . $m['id']; ?></div>
        <div class='col-sm-4'><?php echo $m['fullname']; ?></div>
        <div class="col-sm-3"><?php echo ($m['memId'] == null ? 'None' : ($m['label'] . '[' . $m['status']) . ']');?></div>
        <div class='col-sm-4 p-1'>
            <button class='btn btn-small btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="editPerson(<?php echo $m['id'] . ",'" . $m['personType'] . "'"; ?>);">Edit Person</button>
            <button class='btn btn-small btn-primary p-1' style='--bs-btn-font-size: 80%;' onclick="addMembership(<?php echo $m['id'] . ",'" . $m['personType'] . "'"; ?>);">Add/Upgrade Memberships</button>
    </div>
    </div>
    <?php }
}

?>
<div class='row'>
    <div class='col-sm-12'><h3>Memberships purchased by this account:</h3></div>
</div>
<?php
// get memberships purchased by this person
if ($personType == 'p') {
    $membershipsQ = <<<EOS
WITH mems AS (
    SELECT t.id, r.create_date, r.memId, m.label, m.memType, m.memCategory, p.managedBy, 
       TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname
    FROM transaction t
    JOIN reg r ON t.id = r.create_trans
    JOIN memLabel m ON m.id = r.memId
    JOIN perinfo p ON p.id = r.perid
    WHERE t.perid = ? AND t.conid = ?
    UNION
    SELECT t.id, r.create_date, r.memId, m.label, m.memType, m.memCategory, p.managedBy, 
       TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname
    FROM transaction t
    JOIN reg r ON t.id = r.create_trans
    JOIN memLabel m ON m.id = r.memId
    JOIN newperson p ON p.id = r.newperid
    WHERE t.perid = ? AND t.conid = ?
)
SELECT DISTINCT *
FROM mems
ORDER BY fullname, create_date
EOS;
    $membershipsR = dbSafeQuery($membershipsQ, 'iiii', array($personId, $conid,$personId, $conid));
} else {
    $membershipsQ = <<<EOS
SELECT t.id, r.create_date, r.memId, m.label, m.memType, m.memCategory, p.managedBy, 
   TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname
FROM transaction t
JOIN reg r ON t.id = r.create_trans
JOIN memLabel m ON m.id = r.memId
JOIN newperson p ON p.id = r.newperid
WHERE t.newperid = ? AND t.conid = ?
ORDER BY fullname, create_date
EOS;
    $membershipsR = dbSafeQuery($membershipsQ, 'ii', array($personId, $conid));
}

if ($membershipsR == false || $membershipsR->num_rows == 0) {
    ?>
<div class="row">
    <div class="col-sm-1"></div>
    <div class="col-sm-auto">None</div>
<?php
} else if ($membershipsR->num_rows > 0) {
?>
        <div class='row'>
            <div class='col-sm-1' style='text-align: right;'><b>Trans ID</b></div>
            <div class='col-sm-1'><b>Created</b></div>
            <div class='col-sm-4'><b>Person</b></div>
            <div class='col-sm-1'><b>Type</b></div>
            <div class='col-sm-1'><b>Category</b></div>
            <div class='col-sm-4'><b>Membership</b></div>
        </div>
<?php

    while ($membership = $membershipsR->fetch_assoc()) {
        if ($membership['managedBy'] != $personId) {
            $membership['fullname'] = 'Someone Else';
        }
?>
<div class="row">
    <div class='col-sm-1' style='text-align: right;'><?php echo $membership['create_trans'];?></div>
    <div class="col-sm-1">echo $membership['create_date'];?></div>
    <div class="col-sm-4">echo $membership['fullname'];?></div>
    <div class="col-sm-1">echo $membership['memType'];?></div>
    <div class="col-sm-1">echo $membership['memCategory'];?></div>
    <div class="col-sm-4">echo $membership['label'];?></div>
</div>
<?php
    }
}
?>
</div>
<?php
portal_page_foot();
?>
