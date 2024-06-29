<?php
// Registration  Portal - [prta;].php - Main page for the membership portal
require_once("lib/base.php");
require_once("lib/portalForms.php");
require_once('lib/getAccountData.php');
require_once('../lib/email__load_methods.php');
require_once('../lib/cipher.php');
require_once('lib/sessionManagement.php');
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

if (isSessionVar('id') && isSessionVar('idType')) {
    $loginType = getSessionVar('idType');
    $loginId = getSessionVar('id');
    $expiration = getSessionVar('tokenExpiration');
    $refresh = time() > $expiration;
} else {
    header('location:' . $portal_conf['portalsite']);
    exit();
}

    $con = get_conf('con');
    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    } else {
        $currency = 'USD';
    }

$transId = getSessionVar('transId');
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

if (!$refresh) {
// get the account holder's registrations
    $holderRegSQL = <<<EOS
SELECT r.status, r.memId, m.*, a.shortname AS ageShort, a.label AS ageLabel, r.price AS actPrice, r.conid, 
    nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
    CASE
        WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
        WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
        WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
        ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
    END AS purchaserName
FROM reg r
JOIN memLabel m ON m.id = r.memId
JOIN ageList a ON m.memAge = a.ageType AND r.conid = a.conid
LEFT OUTER JOIN transaction t ON r.create_trans = t.id
LEFT OUTER JOIN transaction tp ON r.complete_trans = tp.id
LEFT OUTER JOIN perinfo pc ON t.perid = pc.id
LEFT OUTER JOIN newperson nc ON t.newperid = nc.id
LEFT OUTER JOIN perinfo pp ON tp.perid = pp.id
LEFT OUTER JOIN newperson np ON tp.newperid = np.id
WHERE
    status IN  ('unpaid', 'paid', 'plan', 'upgraded') AND
    r.conid >= ? AND (r.perid = ? OR r.newperid = ?);
EOS;
    $holderRegR = dbSafeQuery($holderRegSQL, 'iii', array ($conid, $loginType == 'p' ? $loginId : -1, $loginType == 'n' ? $loginId : -1));
    $holderMembership = [];
    if ($holderRegR != false && $holderRegR->num_rows > 0) {
        while ($m = $holderRegR->fetch_assoc()) {
            if ($m['memType'] == 'donation') {
                $label = $dolfmt->formatCurrency((float)$m['actPrice'], $currency) . ' ' . $m['label'];
                $shortname = $dolfmt->formatCurrency((float)$m['actPrice'], $currency) . ' ' . $m['shortname'];
            }
            else {
                $label = $m['label'];
                $shortname = $m['shortname'];
            }
            $holderMembership[] = array ('label' => ($m['conid'] != $conid ? $m['conid'] . ' ' : '') . $label, 'status' => $m['status'],
                                         'memAge' => $m['memAge'], 'type' => $m['memType'], 'category' => $m['memCategory'],
                                         'shortname' => ($m['conid'] != $conid ? $m['conid'] . ' ' : '') . $shortname, 'ageShort' => $m['ageShort'], 'ageLabel' => $m['ageLabel'],
                                         'createNewperid' => $m['createNewperid'], 'completeNewperid' => $m['completeNewperid'],
                                         'createPerid' => $m['createPerid'], 'completePerid' => $m['completePerid'], 'purchaserName' => $m['purchaserName']
            );
        }
        $holderRegR->free();
    }
// get people managed by this account holder and their registrations
    if ($loginType == 'p') {
        $managedSQL = <<<EOS
WITH ppl AS (
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.contact_ok, p.share_reg_ok, p.managedBy, NULL AS managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        r.conid, r.status, r.memId, r.price AS actPrice, m.memCategory, m.memType, m.memAge, m.shortname, m.label, m.memGroup, a.shortname AS ageShort, a.label AS ageLabel, 'p' AS personType,
        nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
        CASE
            WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
            WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
            WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
            ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
        END AS purchaserName
    FROM perinfo p
    LEFT OUTER JOIN reg r ON p.id = r.perid AND r.conid >= ? AND status IN  ('unpaid', 'paid', 'plan', 'upgraded')
    LEFT OUTER JOIN memLabel m ON m.id = r.memId
    LEFT OUTER JOIN ageList a ON m.memAge = a.ageType AND r.conid = a.conid
    LEFT OUTER JOIN transaction t ON r.create_trans = t.id
    LEFT OUTER JOIN transaction tp ON r.complete_trans = tp.id
    LEFT OUTER JOIN perinfo pc ON t.perid = pc.id
    LEFT OUTER JOIN newperson nc ON t.newperid = nc.id
    LEFT OUTER JOIN perinfo pp ON tp.perid = pp.id
    LEFT OUTER JOIN newperson np ON tp.newperid = np.id
    WHERE p.managedBy = ? AND p.id != p.managedBy
    UNION
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.contact_ok, p.share_reg_ok, p.managedBy, p.managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        r.conid, r.status, r.memId, r.price AS actPrice, m.memCategory, m.memType, m.memAge, m.shortname, m.label, m.memGroup, a.shortname AS ageShort, a.label AS ageLabel, 'n' AS personType,
        nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
        CASE
            WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
            WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
            WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
            ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
        END AS purchaserName
    FROM newperson p    
    LEFT OUTER JOIN reg r ON p.id = r.newperid AND r.conid >= ? AND status IN  ('unpaid', 'paid', 'plan', 'upgraded')
    LEFT OUTER JOIN memLabel m ON m.id = r.memId
    LEFT OUTER JOIN ageList a ON m.memAge = a.ageType AND r.conid = a.conid
    LEFT OUTER JOIN transaction t ON r.create_trans = t.id
    LEFT OUTER JOIN transaction tp ON r.complete_trans = tp.id
    LEFT OUTER JOIN perinfo pc ON t.perid = pc.id
    LEFT OUTER JOIN newperson nc ON t.newperid = nc.id
    LEFT OUTER JOIN perinfo pp ON tp.perid = pp.id
    LEFT OUTER JOIN newperson np ON tp.newperid = np.id
    WHERE p.managedBy = ? AND p.id != ? AND p.perid IS NULL
)
SELECT *
FROM ppl
ORDER BY personType DESC, id ASC;
EOS;
        $managedByR = dbSafeQuery($managedSQL, 'iiiii', array ($conid, $loginId, $conid, $loginId, $loginId));
    }
    else {
        $managedSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active, p.contact_ok, p.share_reg_ok, p.managedBy, NULL AS managedByNew,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
    r.conid, r.status, r.memId, r.price AS actPrice, m.memCategory, m.memType, m.memAge, m.shortname, m.label, m.memGroup, a.shortname AS ageShort, a.label AS ageLabel, 'n' AS personType,
    nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
    CASE
        WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
        WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
        WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
        ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
    END AS purchaserName
FROM newperson p
LEFT OUTER JOIN reg r ON p.id = r.newperid AND r.conid >= ?  AND status IN  ('unpaid', 'paid', 'plan', 'upgraded')
LEFT OUTER JOIN memLabel m ON m.id = r.memId
LEFT OUTER JOIN ageList a ON m.memAge = a.ageType AND a.conid = r.conid
LEFT OUTER JOIN transaction t ON r.create_trans = t.id
LEFT OUTER JOIN transaction tp ON r.complete_trans = tp.id
LEFT OUTER JOIN perinfo pc ON t.perid = pc.id
LEFT OUTER JOIN newperson nc ON t.newperid = nc.id
LEFT OUTER JOIN perinfo pp ON tp.perid = pp.id
LEFT OUTER JOIN newperson np ON tp.newperid = np.id
WHERE p.managedByNew = ? AND p.id != p.managedByNew
ORDER BY id ASC;
EOS;
        $managedByR = dbSafeQuery($managedSQL, 'ii', array ($conid, $loginId));
    }

    $managed = [];
    if ($managedByR != false) {
        while ($p = $managedByR->fetch_assoc()) {
            $managed[] = $p;
        }
        $managedByR->free();
    }

    $memberships = getAccountRegistrations($loginId, $loginType, $conid, 'all');

// get the information for the interest block
    $interests = getInterests();
// get the payment plans
    $paymentPlans = getPaymentPlans(true);
}

portalPageInit('portal', $info['fullname'] . ($loginType == 'p' ? ' (ID: ' : ' (Temporary ID: ') . $loginId . ')',
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
    false // refresh
);
if ($refresh) {
    echo "refresh needed<br/>\n";
    echo refreshSession();
    exit();
}
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
draw_recieptModal();
if (count($paymentPlans) > 0) {
    draw_customizePlanModal('portal');
    draw_payPlanModal('portal');
}

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
    <div class="col-sm-3"><b>Badge Name</b></div>
    <div class="col-sm-4"><b>Actions</b></div>
</div>
<?php
drawManagedPerson($loginId, $loginType, $info, $holderMembership, $interests != null);

$managedMembershipList = '';
$currentId = -1;
$curMB = [];
// now for the people managed by this account holder

foreach ($managed as $m) {
    if ($currentId != $m['id']) {
        if ($currentId > 0) {
            drawManagedPerson($loginId, $loginType, $curPT, $curMB,$interests != null);
        }
        $curPT = $m;
        $currentId = $m['id'];
        $currentId = $m['id'];
        $curMB = [];
    }
    if ($m['memId'] != null) {
        if ($m['memType'] == 'donation') {
            $label = $dolfmt->formatCurrency((float) $m['actPrice'], $currency) . ' ' . $m['label'];
            $shortname = $dolfmt->formatCurrency((float) $m['actPrice'], $currency) . ' ' . $m['shortname'];
        } else {
            $label = $m['label'];
            $shortname = $m['shortname'];
        }
        $curMB[] = array('label' => ($m['conid'] != $conid ? $m['conid'] . ' ' : '') . $label, 'status' => $m['status'],
            'memAge' => $m['memAge'], 'type' => $m['memType'], 'category' => $m['memCategory'],
            'shortname' => ($m['conid'] != $conid ? $m['conid'] . ' ' : '') . $shortname, 'ageShort' => $m['ageShort'], 'ageLabel' => $m['ageLabel'],
            'createNewperid' => $m['createNewperid'], 'completeNewperid' => $m['completeNewperid'],
            'createPerid' => $m['createPerid'], 'completePerid' => $m['completePerid'], 'purchaserName' => $m['purchaserName']
        );
    }
}
drawManagedPerson($loginId, $loginType, $curPT, $curMB,$interests != null);

// compute total due so we can display it up top as well...
$totalDue = 0;
foreach ($memberships as $membership) {
    if ($membership['status'] == 'unpaid') {
        $totalDue += round($membership['price'] - ($membership['paid'] + $membership['couponDiscount']), 2);
    }
}
$payHtml = '';
if ($totalDue > 0) {
    $totalDueFormatted = 'Total due: ' . $dolfmt->formatCurrency((float) $totalDue, $currency);
    $payHtml = " $totalDueFormatted   " . '<button class="btn btn-sm btn-primary pt-1 pb-1" id="payBalanceTopBTN" onclick="portal.payBalance(' . $totalDue . ');">Pay Balance</button>';
    setSessionVar('totalDue', $totalDue); // used for validation in payment side
}

if (array_key_exists('payorPlans', $paymentPlans)) {
    $payorPlan = $paymentPlans['payorPlans'];
    if (count($payorPlan) > 0) {
    ?>
    <div class='row mt-4'>
        <div class='col-sm-12'><h3>Payment Plans for this account:</h3></div>
    </div>
    <?php
    drawPaymentPlans($info, $paymentPlans);
    }
}
if (count($memberships) > 0) {
?>
    <div class='row mt-4'>
        <div class='col-sm-auto'><h3>Memberships purchased by this account:<?php echo $payHtml; ?></h3>
    </div>
    <div class='row'>
        <div class='col-sm-1' style='text-align: right;'><b>Trans ID</b></div>
        <div class='col-sm-2'><b>Date</b></div>
        <div class='col-sm-1'><b>Receipt</b></div>
    </div>
    <div class='row'>
        <div class='col-sm-1'></div>
        <div class='col-sm-2'><b>Status</b></div>
        <div class='col-sm-3'><b>Membership</b></div>
        <div class='col-sm-4'><b>Full Name / Badge Name</b></div>
    </div>
    <div class='row'>
        <div class="col-sm-12 ms-0 me-0 align-center"><hr style="height:4px;width:95%;margin:auto;margin-top:6px;margin-bottom:10px;color:#333333;background-color:#333333;"/></div>
    </div>
<?php
// loop over the transactions outputting the memberships
    $currentId = -99999;
    foreach ($memberships as $membership)  {
        if ($currentId != $membership['sortTrans']) {
            if ($currentId > -10000) {
?>
        <div class='row'>
            <div class='col-sm-12 ms-0 me-0 align-center'>
                <hr style='height:4px;width:95%;margin:auto;margin-top:10px;margin-bottom:10px;color:#333333;background-color:#333333;'/>
            </div>
        </div>
<?php
            }
            $currentId = $membership['sortTrans'];
            if ($membership['complete_trans']) {
                $receipt = "<button class='btn btn-sm btn-secondary p-1 pt-0 pb-0' style='--bs-btn-font-size: 80%;' " .
                    'onclick="portal.transReceipt(' . $membership['complete_trans'] . ');">Receipt</button>';
            } else {
                $receipt = '';
            }
            $transDate = date_format(date_create($membership['transDate']), 'Y-m-d');
?>
        <div class='row'>
            <div class='col-sm-1' style='text-align: right;'><?php echo $currentId; ?></div>
            <div class="col-sm-2"><?php echo $transDate; ?></div>
            <div class='col-sm-1'><?php echo $receipt; ?></div>
        </div>
<?php
        }
        if ($membership['status'] == 'unpaid') {
            $due = round($membership['price'] - ($membership['paid'] + $membership['couponDiscount']), 2);
            $status = 'Balance due: ' . $dolfmt->formatCurrency((float) $due, $currency);
        }
        else {
            $status = $membership['status'];
        }
?>
    <div class="row">
        <div class='col-sm-1'></div>
        <div class='col-sm-2'><?php echo $status; ?></div>
        <div class='col-sm-3'><?php echo ($membership['conid'] != $conid ? $membership['conid'] . ' ' : '') . $membership['label']; ?></div>
        <div class="col-sm-6"><?php echo $membership['fullname'] . ' / ' . $membership['badge_name'];?></div>
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
