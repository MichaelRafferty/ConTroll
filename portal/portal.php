<?php
// Registration  Portal - portal.php - Main page for the membership portal
require_once("lib/base.php");
require_once("lib/portalForms.php");
require_once('lib/getAccountData.php');
require_once('../lib/email__load_methods.php');
require_once('../lib/cipher.php');
require_once('lib/sessionManagement.php');
require_once("../lib/interests.php");
require_once("../lib/policies.php");
require_once("../lib/paymentPlans.php");
require_once("../lib/coupon.php");
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

if (array_key_exists('currency', $con)) {
    $currency = $con['currency'];
} else {
    $currency = 'USD';
}

$transId = getSessionVar('transId');
$initCoupon = getSessionVar('curCoupon');
$initCouponSerial = getSessionVar('curCouponSerial');
$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = $debug['portal'];
$config_vars['uri'] = $portal_conf['portalsite'];
$config_vars['loadPlans'] = true;
$config_vars['required'] = $ini['required'];
$config_vars['initCoupon'] = $initCoupon;
$config_vars['initCouponSerial'] = $initCouponSerial;
$cdn = getTabulatorIncludes();

// this section is for 'in-session' management
// build info array about the account holder
$info = getPersonInfo($conid);
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
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        p.banned, p.creation_date, p.update_date, p.change_notes, p.active,
        p.managedBy, NULL AS managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        r.conid, r.status, r.memId, r.price AS actPrice, m.memCategory, m.memType, m.memAge, m.shortname, m.label,
        a.shortname AS ageShort, a.label AS ageLabel, 'p' AS personType,
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
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active,
        p.managedBy, p.managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        r.conid, r.status, r.memId, r.price AS actPrice, m.memCategory, m.memType, m.memAge, m.shortname, m.label,
        a.shortname AS ageShort, a.label AS ageLabel, 'n' AS personType,
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
), uppl AS (
    SELECT DISTINCT ppl.id, ppl.personType
    FROM ppl
), missPol AS (
    SELECT uppl.id, uppl.personType, IFNULL(count(*), 0) AS requiredMissing
    FROM uppl
    JOIN policies pl
    LEFT OUTER JOIN memberPolicies m ON m.policy = pl.policy AND m.conid = ? AND 
        ((uppl.id = IFNULL(m.perid, -1) AND uppl.personType = 'p') OR (uppl.id = IFNULL(m.newperid, -1) AND uppl.personType = 'n'))
    WHERE pl.ACTIVE = 'Y'  AND pl.required = 'Y' AND IFNULL(m.response, 'N') = 'N'
    GROUP BY uppl.id, uppl.personType
)
SELECT ppl.*, IFNULL(missPol.requiredMissing,0) AS missingPolicies
FROM ppl
LEFT OUTER JOIN missPol ON ppl.id = missPol.id
ORDER BY personType DESC, id ASC;
EOS;
        $managedByR = dbSafeQuery($managedSQL, 'iiiiii', array ($conid, $loginId, $conid, $loginId, $loginId, $conid));
    }
    else {
        $managedSQL = <<<EOS
WITH ppl AS (
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        p.banned, p.creation_date, p.update_date, p.change_notes, p.active,
        p.managedBy, NULL AS managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        r.conid, r.status, r.memId, r.price AS actPrice, m.memCategory, m.memType, m.memAge, m.shortname, m.label,
        a.shortname AS ageShort, a.label AS ageLabel, 'p' AS personType,
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
    WHERE p.managedByNew = ? AND p.id != p.managedByNew
    UNION
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        'N' AS banned, NULL AS creation_date, NULL AS update_date, '' AS change_notes, 'Y' AS active,
        p.managedBy, p.managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
        r.conid, r.status, r.memId, r.price AS actPrice, m.memCategory, m.memType, m.memAge, m.shortname, m.label,
        a.shortname AS ageShort, a.label AS ageLabel, 'n' AS personType,
        nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
        CASE
            WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
            WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
            WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
            ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
        END AS purchaserName
    FROM newperson p    
    LEFT OUTER JOIN reg r ON p.id = r.newperid AND r.conid >= ? AND status IN  ('unpaid', 'paid', 'plan', 'upgraded')
    LEFT OUTER JOIN reg r ON p.id = r.newperid AND r.conid >= ? AND status IN  ('unpaid', 'paid', 'plan', 'upgraded')
    LEFT OUTER JOIN memLabel m ON m.id = r.memId
    LEFT OUTER JOIN ageList a ON m.memAge = a.ageType AND r.conid = a.conid
    LEFT OUTER JOIN transaction t ON r.create_trans = t.id
    LEFT OUTER JOIN transaction tp ON r.complete_trans = tp.id
    LEFT OUTER JOIN perinfo pc ON t.perid = pc.id
    LEFT OUTER JOIN newperson nc ON t.newperid = nc.id
    LEFT OUTER JOIN perinfo pp ON tp.perid = pp.id
    LEFT OUTER JOIN newperson np ON tp.newperid = np.id
    WHERE p.managedByNew = ? AND p.id != ? AND p.perid IS NULL
), uppl AS (
    SELECT DISTINCT ppl.id, ppl.personType
    FROM ppl
), missPol AS (
    SELECT uppl.id, uppl.personType, IFNULL(count(*), 0) AS requiredMissing
    FROM uppl
    JOIN policies pl
    LEFT OUTER JOIN memberPolicies m ON m.policy = pl.policy AND m.conid = ? AND 
        ((uppl.id = IFNULL(m.perid, -1) AND uppl.personType = 'p') OR (uppl.id = IFNULL(m.newperid, -1) AND uppl.personType = 'n'))
    WHERE pl.ACTIVE = 'Y'  AND pl.required = 'Y' AND IFNULL(m.response, 'N') = 'N'
    GROUP BY uppl.id, uppl.personType
)
SELECT ppl.*, IFNULL(missPol.requiredMissing, 0) AS missingPolicies
FROM ppl
LEFT OUTER JOIN missPol ON ppl.id = missPol.id
ORDER BY personType DESC, id ASC;
EOS;
        $managedByR = dbSafeQuery($managedSQL, 'iiiiii', array ($conid, $loginId, $conid, $loginId, $loginId, $conid));
    }

    $managed = [];
    if ($managedByR != false) {
        while ($p = $managedByR->fetch_assoc()) {
            $managed[] = $p;
        }
        $managedByR->free();
    }

    $memberships = getAccountRegistrations($loginId, $loginType, $conid, 'all');

// get the information for the interest  and policies blocks
    $interests = getInterests();
    $policies = getPolicies();
// get the payment plans
    $paymentPlans = getPaymentPlans(true);
// get valid coupons

    $numCoupons = num_coupons();
}

portalPageInit('portal', $info,
    /* css */ array($cdn['tabcss'],
        $cdn['tabbs5'],
    ),
    /* js  */ array( //$cdn['luxon'],
        $cdn['tabjs'],
        //'js/tinymce/tinymce.min.js',
        'jslib/paymentPlans.js',
        'jslib/coupon.js',
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
    var numCoupons = <?php echo $numCoupons; ?>;
    var policies = <?php echo json_encode($policies); ?>;
</script>
<?php
// draw all the modals for this screen
$policies = getPolicies();
draw_editPersonModal('portal', $policies);
draw_editInterestsModal($interests);
draw_paymentDueModal();
draw_makePaymentModal();
draw_recieptModal();
draw_couponModal();
drawChangeEmailModal();
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
    <div class='col-sm-12'>
        <h1 class="size-h3">
<?php
    if ($info['managedByName'] == null) {
?>
            People managed by this account:
                <button class='btn btn-primary ms-2' type='button'
                        onclick="window.location='<?php echo $portal_conf['portalsite']; ?>/addUpgrade.php';">
                    Add Another Person and Create a New Membership for Them
                </button>
<?php
    } else {
?>
            This account's information:
<?php
    }
?>
        </h1>
    </div>
</div>
<?php
    outputCustomText('main/people');
?>
<div class="row">
    <div class="col-sm-1" style='text-align: right;'><b>ID</b></div>
    <div class="col-sm-3"><b>Person</b></div>
    <div class="col-sm-3"><b>Badge Name</b></div>
    <div class="col-sm-1"><b>Actions</b></div>
</div>
<?php
drawPersonRow($loginId, $loginType, $info, $holderMembership, $interests != null, false);

$managedMembershipList = '';
$currentId = -1;
$curMB = [];
// now for the people managed by this account holder

foreach ($managed as $m) {
    if ($currentId != $m['id']) {
        if ($currentId > 0) {
            drawPersonRow($loginId, $loginType, $curPT, $curMB, $interests != null, true);
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
if ($currentId > 0) { // if there are any at all
    drawPersonRow($loginId, $loginType, $curPT, $curMB, $interests != null, true);
    drawPortalLegend();
}

// compute total due so we can display it up top as well...
$totalDue = 0;
$totalUnpaid = 0;
$totalPaid = 0;
foreach ($memberships as $membership) {
    if ($membership['status'] == 'unpaid') {
        $totalUnpaid++;
        $totalDue += round($membership['price'] - ($membership['paid'] + $membership['couponDiscount']), 2);
    }
    if ($membership['status'] == 'plan') {
        $totalUnpaid++;
    }
    if ($membership['status'] == 'paid') {
        $totalPaid++;
    }
}
if (array_key_exists('payorPlans', $paymentPlans)) {
    $payorPlan = $paymentPlans['payorPlans'];
} else
    $payorPlan = [];

// create a div and bg color it to separate it logically from the other parts
if ($totalDue > 0 || count($payorPlan) > 0) {
?>
    <div class='container-fluid p-0 m-0' style="background-color: #F0F0FF;">
<?php
}

$payHtml = '';
if ($totalDue > 0) {
    $totalDueFormatted = '&nbsp;&nbsp;Total due: <span name="totalDueAmountSpan">' . $dolfmt->formatCurrency((float) $totalDue, $currency) . "</span>";
    $payHtml = " $totalDueFormatted   " . '<button class="btn btn-sm btn-primary pt-1 pb-1 ms-1 me-2"
        onclick="portal.payBalance(' . $totalDue . ', true);">Pay Total Amount Due</button>';
    setSessionVar('totalDue', $totalDue); // used for validation in payment side
    if ($numCoupons > 0) {
        $payHtml .= ' <button class="btn btn-primary btn-sm pt-1 pb-1 ms-0 me-2" id="addCouponButton" onclick="coupon.ModalOpen(1)">Add Coupon</button>';
    }
}

if (count($payorPlan) > 0) {
?>
    <div class='row mt-5'>
        <div class='col-sm-12'><h1 class="size-h3">Payment Plans for this account:</h1></div>
    </div>
<?php
    outputCustomText('main/plan');
    drawPaymentPlans($info, $paymentPlans);
}
if ($totalDue > 0 || count($payorPlan) > 0) {
?>
    </div>
<?php
}

if (count($memberships) > 0) {
    if ($totalUnpaid > 0) {
        $showAll = '';
        $showUnpaid = 'disabled';
        $hideAll = '';
    }
    else {
        $showAll = '';
        $showUnpaid = 'hidden';
        $hideAll = 'disabled';
    }
?>
    <div class='row mt-4'>
        <div class='col-sm-auto'>
            <h1 class="size-h3">
                Purchased by this account: <?php echo $payHtml; ?>
                <div class="btn-group" data-toggle="buttons">
                <button class="btn btn-sm btn-info text-white me-0 ps-3" style="border-top-left-radius: 20px; border-bottom-left-radius: 20px;" id="btn-showAll"
                        type="button" onclick="portal.showAll();"
                    <?php echo $showAll;?>><b>Show All</b></button>
<?php
                    if ($totalUnpaid > 0) {
?>
                <button class="btn btn-sm btn-info text-white m-0" id="btn-showUnpaid"
                        type="button" onclick="portal.showUnpaid();"
                    <?php echo $showUnpaid; ?>><b>Show Unpaid</b></button>
<?php } ?>
                <button class="btn btn-sm btn-info text-white ms-0 pe-3" id="btn-hideAll" style='border-top-right-radius: 20px; border-bottom-right-radius:
                20px;'
                        type="button"  onclick="portal.hideAll();"
                    <?php echo $hideAll;?>><b>Hide All</b></button>
                </div>
            </h1>
        </div>
    </div>
<?php
    outputCustomText('main/purchased');
    if ($numCoupons > 0) {
?>
    <div class='container-fluid' id='couponDiv' style="background-color: rgba(0,255,128,0.1)" hidden>
        <div class="row">
            <div class="col-sm-auto"><b>Coupon</b></div>
        </div>
        <div class="row">
            <div class="col-sm-12" id="couponDetailDiv"></div>
        </div>
        <div class='row'>
            <div class='col-sm-12'>
                <button class='btn btn-sm btn-secondary' onclick='coupon.ModalOpen(1);' id='changeCouponBTN'>Change/Remove Coupon</button>
            </div>
        </div>
        <div class='row mt-4'>
            <div class='col-sm-2'>
                Subtotal before coupon:
            </div>
            <div class='col-sm-1 text-end' id='subTotalColDiv'></div>
        </div>
        <div class='row'>
            <div class='col-sm-2'>
                Coupon Discount:
            </div>
            <div class='col-sm-1 text-end' id='couponDiscountDiv'></div>
        </div>
    </div>
<?php
    }
?>
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
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr style='height:4px;width:98%;margin:auto;margin-top:0px;margin-bottom:0px;color:#333333;background-color:#333333;'/>
        </div>
    </div>
<?php
// loop over the transactions outputting the memberships
    // first find all the transactions and set their status
    $currentId = -99999;
    $status = 'paid';
    foreach ($memberships as $membership) {
        if ($currentId != $membership['sortTrans']) {
            if ($currentId > -10000) {
                $trans['t-' . $currentId] = $status;
            }
            $currentId = $membership['sortTrans'];
            $status = 'paid';
        }
        if ($membership['status'] != $status) {
            if ($membership['status'] == 'unpaid')
                $status = 'unpaid';
            if ($membership['status'] == 'plan' && $status = 'paid')
                $status = 'plan';
        }
    }
    $trans['t-' . $currentId] = $status;
    $currentId = -99999;
    $color = true;
    echo '<div class="container-fluid p-0 m-0" name="t-' . $trans['t-' . $memberships[0]['sortTrans']] .'">' .  PHP_EOL;
    foreach ($memberships as $membership)  {
        if ($currentId != $membership['sortTrans']) {
            if ($currentId > -10000) {
                $bgcolor = $color ? ' bg-light' : '';
                $color = !$color
?>
        </div>
        <div class="container-fluid<?php echo $bgcolor; ?> p-0 m-0" name="t-<?php echo $trans['t-' . $membership['sortTrans']];?>">
        <div class='row'>
            <div class='col-sm-12 p-0 m-0 align-center'>
                <hr style='height:4px;width:98%;margin:auto;margin-top:0px;margin-bottom:0px;color:#333333;background-color:#333333;'/>
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
        <div class='row pt-1'>
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
        <div class='row'>
            <div class='col-sm-1'></div>
            <div class='col-sm-2'><?php echo $status; ?></div>
            <div class='col-sm-3'><?php echo ($membership['conid'] != $conid ? $membership['conid'] . ' ' : '') . $membership['label']; ?></div>
            <div class="col-sm-6"><?php echo $membership['fullname'] . ' / ' . $membership['badge_name'];?></div>
        </div>
<?php
    }
    echo "        </div>";
    if ($totalDue > 0) {
?>
    <div class='row'>
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr color="black" style='height:3px;width:98%;margin:auto;margin-top:10px;margin-bottom:2px;'/>
        </div>
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr color="black" style='height:3px;width:98%;margin:auto;margin-top:2px;margin-bottom:20px;'/>
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
