<?php
// Registration  Portal - [prta;].php - Main page for the membership portal
require_once("lib/base.php");
require_once("lib/portalForms.php");
require_once('lib/getAccountData.php');
require_once("../lib/interests.php");
require_once("../lib/paymentPlans.php");
require_once('../lib/cc__load_methods.php');

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$cc = get_conf('cc');
$condata = get_con();
load_cc_procs();

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
$config_vars['loadPlans'] = true;
$cdn = getTabulatorIncludes();

// this section is for 'in-session' management
// build info array about the account holder
$info = getPersonInfo();
if ($info === false) {
    echo 'Invalid Login, seek assistance';
    portalPageFoot();
    exit();
}
$dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

// get the account holder's registrations
$holderRegSQL = <<<EOS
SELECT r.status, r.memId, m.*
FROM reg r
JOIN memLabel m ON m.id = r.memId
WHERE
    status IN  ('unpaid', 'paid', 'plan', 'upgraded') AND
    r.conid >= ? AND (r.perid = ? OR r.newperid = ?);
EOS;
$holderRegR = dbSafeQuery($holderRegSQL, 'iii', array($conid, $personType == 'p' ? $personId : -1, $personType == 'n' ? $personId : -1));
if ($holderRegR == false || $holderRegR->num_rows == 0) {
    $holderMemberhip = 'None';
} else {
    $holderMembership = '';
    while ($holderL = $holderRegR->fetch_assoc()) {
        if ($holderMembership != '')
            $holderMembership .= '<br/>';
        $holderMembership .= ($holderL['conid'] != $conid ? $holderL['conid'] . ' ' : '') . $holderL['label'] . ' (' . $holderL['status'] . ')';
    }
    if ($holderRegR != false)
        $holderRegR->free();
}
// get people managed by this account holder and their registrations
if ($personType == 'p') {
    $managedSQL = <<<EOS
WITH ppl AS (
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.contact_ok, p.share_reg_ok, p.managedBy, NULL AS managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        r.conid, r.status, r.memId, m.memCategory, m.memType, m.memAge, m.shortname, m. label, m.memGroup, 'p' AS personType
        FROM perinfo p
        LEFT OUTER JOIN reg r ON p.id = r.perid AND r.conid >= ?
        LEFT OUTER JOIN memLabel m ON m.id = r.memId
        WHERE managedBy = ? AND p.id != p.managedBy
    UNION
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.contact_ok, p.share_reg_ok, p.managedBy, p.managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        r.conid, r.status, r.memId, m.memCategory, m.memType, m.memAge, m.shortname, m. label, m.memGroup, 'n' AS personType
        FROM newperson p    
        LEFT OUTER JOIN reg r ON p.id = r.newperid AND r.conid >= ?
        LEFT OUTER JOIN memLabel m ON m.id = r.memId
        WHERE managedBy = ? AND p.id != ? AND p.perid IS NULL
)
SELECT *
FROM ppl
ORDER BY personType DESC, id ASC;
EOS;
    $managedByR = dbSafeQuery($managedSQL, 'iiiii', array($conid, $personId, $conid, $personId, $personId));
} else {
    $managedSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.contact_ok, p.share_reg_ok, p.managedBy, NULL AS managedByNew,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
    r.conid, r.status, r.memId, m.memCategory, m.memType, m.memAge, m.shortname, m. label, m.memGroup, 'n' AS personType
FROM newperson p
LEFT OUTER JOIN reg r ON p.id = r.newperid AND r.conid >= ?
LEFT OUTER JOIN memLabel m ON m.id = r.memId
WHERE p.managedByNew = ? AND p.id != p.managedBy
ORDER BY id ASC;
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

$memberships = getAccountRegistrations($personId, $personType, $conid, 'all');

// get the information for the interest block
$interests = getInterests();
// get the payment plans
$paymentPlans = getPaymentPlans(true);

portalPageInit('portal', $info['fullname'] . ($personType == 'p' ? ' (ID: ' : 'Temporary ID: ') . $personId . ')',
    /* css */ array($cdn['tabcss'],
        $cdn['tabbs5'],
    ),
    /* js  */ array( //$cdn['luxon'],
        $cdn['tabjs'],
        //'js/tinymce/tinymce.min.js',
        'jslib/paymentPlans.js',
        'js/base.js',
        'js/portal.js',
    ),
);
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var paymentPlanList = <?php echo json_encode($paymentPlans['plans']); ?>;
    var payorPlans = <?php echo json_encode($paymentPlans['payorPlans']); ?>;
    var membershipsPurchased = <?php echo json_encode($memberships); ?>;
</script>
<?php
// draw all the modals for this screen
draw_editPersonModal();
draw_editInterestsModal($interests);
draw_paymentDueModal();
draw_makePaymentModal();

// if this person is managed, print a banner and let them disassociate from the manager.
if ($info['managedByName'] != null) {
?>
    <div class='row mt-4' id="managedByDiv">
        <div class='col-sm-auto'><b>This person record is managed by <?php echo $info['managedByName']; ?></b></div>
        <div class='col-sm-auto'><button class="btn btn-warning btn-sm p-1" onclick="portal.disassociate();">Dissociate from <?php echo $info['managedByName']; ?></button></div>
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
<?php
drawManagedPerson($info, $holderMembership, $interests != null);


$managedMembershipList = '';
$currentId = -1;
// now for the people managed by this account holder
if (count($managed) > 0) {
    foreach ($managed as $m) {
        if ($currentId != $m['id']) {
            if ($currentId > 0) {
                drawManagedPerson(['id' => $currentId, 'personType' => $curPT, 'fullname' => $curFN ], $curMB,$interests != null);
            }
            $curPT = $m['personType'];
            $currentId = $m['id'];
            $curFN = $m['fullname'];
            $curMB = '';
        }
        if ($curMB != '') {
            $curMB .= '<br/>';
        }
        $curMB .= $m['memId'] == null ? 'None' : (($m['conid'] != $conid ? $m['conid'] . ' ' : '') .  $m['label'] . ' (' . $m['status']) . ')';
    }
    if ($curMB != '') {
        drawManagedPerson(['id' => $currentId, 'personType' => $curPT, 'fullname' => $curFN ], $curMB,$interests != null);
    }
}
// compute total due so we can display it up top as well...
$totalDue = 0;
foreach ($memberships as $membership) {
    if ($membership['status'] == 'unpaid') {
        $totalDue += round($membership['price'] - ($membership['paid'] + $membership['couponDiscount']), 2);
    }
}
$payHtml = '';
if ($totalDue > 0) {
    $totalDueFormatted = 'Total due: ' . $dolfmt->formatCurrency((float) $totalDue, 'USD');
    $payHtml = " $totalDueFormatted   " . '<button class="btn btn-sm btn-primary pt-1 pb-1" id="payBalanceTopBTN" onclick="portal.payBalance($totalDue);">Pay Balance</button>';
    $_SESSION['totalDue'] = $totalDue; // used for validation in payment side
}
?>
<div class='row mt-4'>
    <div class='col-sm-auto'><h3>Memberships purchased by this account:<?php echo $payHtml; ?></h3>
</div>
<?php
// loop over the transactions outputting the memberships
if (count($memberships) == 0) {
?>
    <div class="row">
        <div class="col-sm-1"></div>
        <div class="col-sm-auto">None</div>
    </div>
<?php
} else {
?>
    <div class='row'>
        <div class='col-sm-1' style='text-align: right;'><b>Trans ID</b></div>
        <div class='col-sm-2'><b>Created</b></div>
        <div class='col-sm-4'><b>Badge Name</b></div>
        <div class='col-sm-5'><b>Membership</b></div>
    </div>
    <div class='row'>
        <div class='col-sm-1'></div>
        <div class='col-sm-2'><b>Status</b></div>
        <div class='col-sm-7'><b>Full Name</b></div>
        <div class='col-sm-1'><b>Type</b></div>
        <div class='col-sm-1'><b>Category</b></div>
    </div>
    <div class='row'>
        <div class="col-sm-12 ms-0 me-0 align-center"><hr style="height:4px;width:95%;margin:auto;margin-top:6px;margin-bottom:10px;color:#333333;background-color:#333333;"/></div>
    </div>
<?php

    $rowId = -9999;
    $currentId = -99999;
    foreach ($memberships as $membership)  {
        if ($currentId > -10000 && $currentId != $membership['memberId']) {
?>
        <div class='row'>
            <div class='col-sm-12 ms-0 me-0 align-center'>
                <hr style='height:4px;width:95%;margin:auto;margin-top:10px;margin-bottom:10px;color:#333333;background-color:#333333;'/>
            </div>
        </div>
<?php
        }
        $currentId = $membership['memberId'];
        if ($currentId == null)
            $currentId = $rowId;
        $rowId++;
        if ($membership['status'] == 'unpaid') {
            $due = round($membership['price'] - ($membership['paid'] + $membership['couponDiscount']), 2);
            $status = 'Balance due: ' . $dolfmt->formatCurrency((float) $due, 'USD');
        }
        else
            $status = $membership['status'];
?>
    <div class="row">
        <div class='col-sm-1' style='text-align: right;'><?php echo $membership['id'];?></div>
        <div class="col-sm-2"><?php echo $membership['create_date'];?></div>
        <div class="col-sm-4"><?php echo $membership['badge_name'];?></div>
        <div class="col-sm-5"><?php echo ($membership['conid'] != $conid ? $membership['conid'] . ' ' : '') . $membership['label'];?></div>
    </div>
    <div class='row'>
        <div class="col-sm-1" style='text-align: right;'><button class="btn btn-sm btn-secondary p-1 pt-0 pb-0" style='--bs-btn-font-size: 80%;' onclick="portal.transReceipt(<?php echo $membership['id'] ?>);">Receipt</button></div>
        <div class="col-sm-2"><?php echo $status; ?></div>
        <div class="col-sm-7"><?php echo $membership['fullname']; ?></div>
        <div class="col-sm-1"><?php echo $membership['memType']; ?></div>
        <div class="col-sm-1"><?php echo $membership['memCategory']; ?></div>
    </div>
<?php
    }
    if ($totalDue > 0) {
?>
    <div class='row'>
        <div class='col-sm-12 ms-0 me-0 align-center'>
            <hr color="black" style='height:3px;width:95%;margin:auto;margin-top:10px;margin-bottom:2px;'/>
        </div>
        <div class='col-sm-12 ms-0 me-0 align-center'>
            <hr color="black" style='height:3px;width:95%;margin:auto;margin-top:2px;margin-bottom:20px;'/>
        </div>
    </div>
<div class="row">
    <div class="col-sm-1"></div>
    <div class="col-sm-2"><b><?php echo $totalDueFormatted; ?></b></div>
    <div class="col-sm-4"><button class="btn btn-sm btn-primary pt-1 pb-1" id="payBalanceBTN" onclick="portal.payBalance(<?php echo $totalDue;?>);">Pay Balance</button>
    </div>
<?php
        }
    }
?>
</div>
<?php
portalPageFoot();
?>
