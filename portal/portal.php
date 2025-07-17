<?php
// Registration  Portal - portal.php - Main page for the membership portal
require_once("lib/base.php");
require_once('lib/getAccountData.php');
require_once('lib/sessionManagement.php');
require_once('../lib/portalForms.php');
require_once('../lib/email__load_methods.php');
require_once("../lib/interests.php");
require_once("../lib/profile.php");
require_once("../lib/policies.php");
require_once("../lib/paymentPlans.php");
require_once("../lib/coupon.php");
require_once('../lib/cc__load_methods.php');

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$cc = get_conf('cc');
$condata = get_con();
load_cc_procs();

if (getConfValue('portal', 'suspended') == 1) {
    // the portal is now closed, redirect the user back as a logout and let them get the closed screen
    header('location:' . $portal_conf['portalsite'] . '?logout');
    exit();
}

$NomNomURL = getConfValue('portal', 'nomnomURL');
$BusinessMeetingURL = getConfValue('portal', 'businessmeetingURL');
$SiteSelectionURL = getConfValue('portal', 'siteselectionURL');
$virtualURL = getConfValue('portal', 'virtualURL');
$worldCon = getConfValue('portal', 'worldcon', '0');

if (isSessionVar('id') && isSessionVar('idType')) {
    // check for being resolved/baned
    $resolveUpdates = isResolvedBanned();
    if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
        header('location:' . $portal_conf['portalsite']);
        exit();
    }
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
$config_vars['required'] = getConfValue('reg', 'required', 'addr');
$config_vars['initCoupon'] = $initCoupon;
$config_vars['initCouponSerial'] = $initCouponSerial;
$config_vars['id'] = $loginId;
$config_vars['idType'] = $loginType;
$config_vars['conid'] = $conid;
$config_vars['worldcon'] = $worldCon;
$config_vars['nomnomExists'] = $NomNomURL != '';
$config_vars['businessExists'] = $BusinessMeetingURL != '';
$config_vars['virtualExists'] = $virtualURL != '';
$config_vars['siteExists'] = $SiteSelectionURL != '';
if ($NomNomURL != '')
    $config_vars['nomnomURL'] = $NomNomURL;
if ($BusinessMeetingURL != '')
    $config_vars['businessURL'] = $BusinessMeetingURL;
if ($SiteSelectionURL != '')
    $config_vars['siteURL'] = $SiteSelectionURL;
if ($virtualURL != '')
    $config_vars['virtualURL'] = $virtualURL;
if (array_key_exists('onedaycoupons', $con)) {
    $onedaycoupons = $con['onedaycoupons'];
} else {
    $onedaycoupons = 0;
}
$config_vars['onedaycoupons'] = $onedaycoupons;

$cdn = getTabulatorIncludes();
// default memberships to empty to handle the refresh case which never loads them.
$memberships = [];

// this section is for 'in-session' management
// build info array about the account holder
$info = getPersonInfo($conid);
if ($info === false) {
    echo 'Invalid Login, seek assistance';
    clearSession();
    portalPageFoot();
    exit();
}
$dolfmt = new NumberFormatter('', NumberFormatter::CURRENCY);

$hasWSFS = false;
$hasNom =  false;
$siteSelection = false;
$hasMeeting = false;
$tokenType = getSessionVar('tokenType');
$hasPasskey = $tokenType == 'passkey';
if ($hasPasskey == false) {
    // check for a potential passkey
    $passkeyQ = <<<EOS
SELECT count(*)
FROM passkeys
WHERE userName = ?;
EOS;
    $passKeyR = dbSafeQuery($passkeyQ, 's', array($info['email_addr']));
    if ($passKeyR !== false) {
        $numKeys = $passKeyR->fetch_row()[0];
        $passKeyR->free();

        $hasPasskey = $numKeys > 0;
    }
}

if (!$refresh) {
    $numPrimary = 0;
    $numPaidPrimary = 0;
    $numChild = 0;
// get the account holder's registrations
    $holderRegSQL = <<<EOS
SELECT r.status, r.memId, m.*, a.shortname AS ageShort, a.label AS ageLabel, a.ageType, m.taxable,
       r.price AS actPrice, IFNULL(r.paid, 0.00) AS actPaid, r.couponDiscount AS actCouponDiscount,
       r.conid, r.create_date, r.id AS regid, r.create_trans, r.complete_trans,
       r.perid AS regPerid, r.newperid AS regNewperid, r.planId,
       IFNULL(r.complete_trans, r.create_trans) AS sortTrans,
       IFNULL(tp.complete_date, t.create_date) AS transDate,
       IFNULL(tp.perid, t.perid) AS transPerid,
       IFNULL(tp.newperid, t.newperid) AS transNewPerid,
       nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
    CASE
        WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
        WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
        WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
        ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
    END AS purchaserName,
    CASE 
        WHEN rp.id IS NOT NULL THEN rp.managedBy
        WHEN rn.id IS NOT NULL THEN rn.managedBy
        ELSE NULL
    END AS managedBy,
    CASE 
        WHEN rp.id IS NOT NULL THEN rp.managedByNew
        WHEN rn.id IS NOT NULL THEN rn.managedByNew
        ELSE NULL
    END AS managedByNew,
    CASE 
        WHEN rp.id IS NOT NULL THEN rp.badge_name
        WHEN rn.id IS NOT NULL THEN rn.badge_name
        ELSE NULL
    END AS badge_name,
    CASE 
        WHEN rp.id IS NOT NULL THEN rp.email_addr
        WHEN rn.id IS NOT NULL THEN rn.email_addr
        ELSE NULL
    END AS email_addr,
    CASE 
        WHEN rp.id IS NOT NULL THEN rp.phone
        WHEN rn.id IS NOT NULL THEN rn.phone
        ELSE NULL
    END AS phone,
    CASE 
        WHEN rp.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT_WS(' ', rp.first_name, rp.middle_name, rp.last_name, rp.suffix), '  *', ' '))
        WHEN rn.id IS NOT NULL THEN TRIM(REGEXP_REPLACE(CONCAT_WS(' ', rn.first_name, rn.middle_name, rn.last_name, rn.suffix), '  *', ' '))
        ELSE NULL
    END AS fullName,
    CASE 
        WHEN rp.id IS NOT NULL THEN rp.id
        WHEN rn.id IS NOT NULL THEN rn.id
        ELSE NULL
    END AS memberId
FROM reg r
JOIN memLabel m ON m.id = r.memId
JOIN ageList a ON m.memAge = a.ageType AND r.conid = a.conid
LEFT OUTER JOIN transaction t ON r.create_trans = t.id
LEFT OUTER JOIN transaction tp ON r.complete_trans = tp.id
LEFT OUTER JOIN perinfo pc ON t.perid = pc.id
LEFT OUTER JOIN newperson nc ON t.newperid = nc.id
LEFT OUTER JOIN perinfo pp ON tp.perid = pp.id
LEFT OUTER JOIN newperson np ON tp.newperid = np.id
LEFT OUTER JOIN perinfo rp ON r.perid = rp.id
LEFT OUTER JOIN newperson rn ON r.newperid = rn.id
WHERE
    status IN  ('unpaid', 'paid', 'plan', 'upgraded') AND
    r.conid >= ? AND (r.perid = ? OR r.newperid = ?)
ORDER BY create_date;
EOS;
    $holderRegR = dbSafeQuery($holderRegSQL, 'iii', array ($conid, $loginType == 'p' ? $loginId : -1, $loginType == 'n' ? $loginId : -1));
    $holderMembership = [];
    $paidOtherMembership = [];
    // determine business meeting, site selection and wsfs
    // wsfs is any WSFS membership except nomination only
    // nom = any wsfs membership
    // siteSelection = has paid token
    // meeting = (later has WSFS anded with it)
    //			any type ‘full’ (
    //				except ‘access caregiver’ (hard code 622) (yuck on the hard code) OR
    //				 ‘artist’ (mail in artist)
    //			)
    //			OR
    //			any type ‘virtual’ OR
    //			any type ‘oneday'
    $allowChild = getConfValue('portal', 'virtualChild', 0) == 1;
    if ($holderRegR !== false && $holderRegR->num_rows > 0) {
        while ($m = $holderRegR->fetch_assoc()) {
            // check if they have a WSFS rights membership (hasWSFS and hasNom)
            if (($m['memCategory'] == 'wsfs' || $m['memCategory'] == 'wsfsnom' || $m['memType'] == 'wsfsfree' || $m['memCategory'] == 'dealer') &&
                $m['status'] == 'paid') {
                $hasNom = true;
                if ($m['memCategory'] != 'wsfsnom')
                    $hasWSFS = true;
                }

            // site selection
            if ($m['memCategory'] == 'sitesel' && $m['status'] == 'paid')
                $siteSelection = true;

            // hasMeeting
            if ( ($m['memType'] == 'full' && $m['memCategory'] != 'artist' && $m['shortname'] != 'Access Caregiver')
                    || $m['memType'] == 'virtual' || strtolower($m['memType']) == 'oneday')
                $hasMeeting = true;

            // check age to prevent virtual, allowChild is true if child is allowed
            if (($m['ageType'] == 'child' && !$allowChild) || $m['ageType'] == 'kit')
                $numChild++;

            if ($m['memType'] == 'donation') {
                $label = $dolfmt->formatCurrency((float)$m['actPrice'], $currency) . ' ' . $m['label'];
                $shortname = $dolfmt->formatCurrency((float)$m['actPrice'], $currency) . ' ' . $m['shortname'];
            }
            else {
                $label = $m['label'];
                $shortname = $m['shortname'];
            }
            $item = array ('label' => ($m['conid'] != $conid ? $m['conid'] . ' ' : '') . $label, 'status' => $m['status'],
                'memAge' => $m['memAge'], 'type' => $m['memType'], 'category' => $m['memCategory'],
                'shortname' => ($m['conid'] != $conid ? $m['conid'] . ' ' : '') . $shortname, 'ageShort' => $m['ageShort'], 'ageLabel' => $m['ageLabel'],
                'createNewperid' => $m['createNewperid'], 'completeNewperid' => $m['completeNewperid'],
                'createPerid' => $m['createPerid'], 'completePerid' => $m['completePerid'], 'purchaserName' => $m['purchaserName'],
                'startdate' => $m['startdate'], 'enddate' => $m['enddate'], 'online' => $m['online'],
                'actPrice' => $m['actPrice'], 'actPaid' => $m['actPaid'], 'actCouponDiscount' => $m['actCouponDiscount'],
                'email_addr' => $m['email_addr'], 'phone' => $m['phone'],
                'transPerid' => $m['transPerid'], 'transNewPerid' => $m['transNewPerid'], 'taxable' => $m['taxable'],
            );
            $holderMembership[] = $item;
            if ($item['completePerid'] != NULL) {
                $compareId = $item['completePerid'];
                $compareType = 'p';
            } else if ($item['completeNewperid'] != NULL) {
                $compareId = $item['completeNewperid'];
                $compareType = 'n';
            } else if ($item['createPerid'] != NULL) {
                $compareId = $item['createPerid'];
                $compareType = 'p';
            } else if ($item['createNewperid'] != NULL) {
                $compareId = $item['createNewperid'];
                $compareType = 'n';
            } else {
                $compareId = '';
                $compareType = '';
            }
            if ($compareId != $loginId || $compareType != $loginType) {
                $item['create_date'] = $m['create_date'];
                $item['create_trans'] = $m['create_trans'];
                $item['complete_trans'] = $m['complete_trans'];
                $item['regid'] = $m['regid'];
                $item['memId'] = $m['memId'];
                $item['conid'] = $m['conid'];
                $item['regPerid'] = $m['regPerid'];
                $item['regNewperid'] = $m['regNewperid'];
                $item['sortTrans'] = $m['sortTrans'];
                $item['transDate'] = $m['transDate'];
                $item['age'] = $m['memAge'];
                $item['online'] = $m['online'];
                $item['managedBy'] = $m['managedBy'];
                $item['managedByNew'] = $m['managedByNew'];
                $item['badge_name'] = $m['badge_name'];
                $item['fullName'] = $m['fullName'];
                $item['memberId'] = $m['memberId'];
                $item['planId'] = $m['planId'];
                $paidOtherMembership[] = $item;
            }
            if (isPrimary($m, $conid)) {
                $numPrimary++;
                if ($m['status'] == 'paid')
                    $numPaidPrimary++;
            }
        }
        $holderRegR->free();
    }
    $config_vars['numPrimary'] = $numPrimary;
    $config_vars['numPaidPrimary'] = $numPaidPrimary;

    if (!$hasWSFS)
        $hasMeeting = false;
    $hasVirtual = $numPaidPrimary > 0 && $numChild == 0 && ((!$worldCon) || $hasWSFS);
// get people managed by this account holder and their registrations
    if ($loginType == 'p') {
        $managedSQL = <<<EOS
WITH ppl AS (
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        p.banned, p.creation_date, p.update_date, p.change_notes, p.active,
        p.managedBy, NULL AS managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName,
        r.conid, r.status, r.memId, r.create_date,
        r.price AS actPrice, IFNULL(r.paid, 0.00) AS actPaid, r.couponDiscount AS actCouponDiscount,        
        m.memCategory, m.memType, m.memAge, m.shortname, m.label, m.startdate, m.enddate, m.online,
        a.shortname AS ageShort, a.label AS ageLabel, 'p' AS personType, m.taxable,
        nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
        CASE
            WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
            WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
            WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
            ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
        END AS purchaserName,
        IFNULL(tp.perid, t.perid) AS transPerid,
        IFNULL(tp.newperid, t.newperid) AS transNewPerid
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
        TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName,
        r.conid, r.status, r.memId, r.create_date, 
        r.price AS actPrice, IFNULL(r.paid, 0.00) AS actPaid, r.couponDiscount AS actCouponDiscount,
        m.memCategory, m.memType, m.memAge, m.shortname, m.label, m.startdate, m.enddate, m.online,
        a.shortname AS ageShort, a.label AS ageLabel, 'n' AS personType, m.taxable,
        nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
        CASE
            WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
            WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
            WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
            ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
        END AS purchaserName,
        IFNULL(tp.perid, t.perid) AS transPerid,
        IFNULL(tp.newperid, t.newperid) AS transNewPerid
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
ORDER BY personType DESC, id ASC, create_date;
EOS;
        $managedByR = dbSafeQuery($managedSQL, 'iiiiii', array ($conid, $loginId, $conid, $loginId, $loginId, $conid));
    } else {
        $managedSQL = <<<EOS
WITH ppl AS (
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
        p.address, p.addr_2, p.city, p.state, p.zip, p.country,
        p.banned, p.creation_date, p.update_date, p.change_notes, p.active,
        p.managedBy, NULL AS managedByNew,
        TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName,
        r.conid, r.status, r.memId, r.create_date, m.memCategory, m.memType, m.memAge, m.shortname, m.label,
        r.price AS actPrice, IFNULL(r.paid, 0.00) AS actPaid, r.couponDiscount AS actCouponDiscount,
        m.startdate, m.enddate, m.online,
        a.shortname AS ageShort, a.label AS ageLabel, 'p' AS personType, m.taxable,
        nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
        CASE
            WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
            WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
            WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
            ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
        END AS purchaserName,
        IFNULL(tp.perid, t.perid) AS transPerid,
        IFNULL(tp.newperid, t.newperid) AS transNewPerid
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
        TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName,
        r.conid, r.status, r.memId, r.create_date, m.memCategory, m.memType, m.memAge, m.shortname, m.label,
        r.price AS actPrice, IFNULL(r.paid, 0.00) AS actPaid, r.couponDiscount AS actCouponDiscount,
        m.startdate, m.enddate, m.online,
        a.shortname AS ageShort, a.label AS ageLabel, 'n' AS personType, m.taxable,
        nc.id AS createNewperid, np.id AS completeNewperid, pc.id AS createPerid, pp.id AS completePerid,
        CASE
            WHEN pp.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pp.first_name, pp.last_name))
            WHEN np.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', np.first_name, np.last_name))
            WHEN pc.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', pc.first_name, pc.last_name))
            ELSE TRIM(CONCAT_WS(' ', nc.first_name, nc.last_name))
        END AS purchaserName,
        IFNULL(tp.perid, t.perid) AS transPerid,
        IFNULL(tp.newperid, t.newperid) AS transNewPerid
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
ORDER BY personType DESC, id ASC, create_date;
EOS;
        $managedByR = dbSafeQuery($managedSQL, 'iiiiii', array ($conid, $loginId, $conid, $loginId, $loginId, $conid));
    }

    $managed = [];
    if ($managedByR !== false) {
        while ($p = $managedByR->fetch_assoc()) {
            $managed[] = $p;
        }
        $managedByR->free();
    }

    $memberships = getAccountRegistrations($loginId, $loginType, $conid, 'all');

// get the information for the interest  and policies blocks
    $interests = getInterests();
    $policies = getPolicies();
// Does this person have interests, if none in the system force them to go to the interests modal
    $config_vars['needInterests'] = 0;
    if ($interests != null && count($interests) > 0) {
        if ($loginType == 'p') {
            $pfield = 'perid';
            } else {
            $pfield = 'newperid';
        }
        $iQ = <<<EOS
SELECT COUNT(*)
FROM memberInterests
WHERE $pfield = ? AND conid = ?;
EOS;
        $iR = dbSafeQuery($iQ, 'ii', array($loginId, $conid));
        if ($iR !== false) {
            $intCount = $iR->fetch_row()[0];
            $iR->free();
            if ($intCount == 0) {
                $config_vars['needInterests'] = 1;
            }
        }
    }
}

// get the payment plans
$paymentPlansData = getPaymentPlans(true);
$activePaymentPlans = false;
if (array_key_exists('payorPlans', $paymentPlansData)) {
    $payorPlan = $paymentPlansData['payorPlans'];
    foreach ($payorPlan as $p) {
        if ($p['status'] == 'active') {
            $activePaymentPlans = true;
            break;
        }
    }
} else {
    $payorPlan = [];
}
if (array_key_exists('plans', $paymentPlansData)) {
    $paymentPlans = $paymentPlansData['plans'];
} else
    $paymentPlans = [];

// get valid coupons
$numCoupons = num_coupons();

$now = date_format(date_create('now'), 'Y-m-d H:i:s');

// compute total due so we can display it up top as well...
$totalDue = 0;
$totalUnpaid = 0;
$totalPaid = 0;
$numExpired = 0;
$disablePay = '';

foreach ($memberships as $key => $membership) {
    $label = ($membership['conid'] != $conid ? $membership['conid'] . ' ' : '') . $membership['label'];
    if ($membership['status'] == 'unpaid') {
        $totalUnpaid++;
        $totalDue += round($membership['price'] - ($membership['paid'] + $membership['couponDiscount']), 2);
        $due = round($membership['price'] - ($membership['paid'] + $membership['couponDiscount']), 2);
        $status = 'Balance due: ' . $dolfmt->formatCurrency((float) $due, $currency);

        if ($membership['startdate'] > $now || $membership['enddate'] < $now || $membership['online'] == 'N') {
            $label = "<span class='text-danger'><b>Expired: </b>$label</span>";
            $numExpired++;
        }
    }
    if ($membership['status'] == 'plan') {
        $totalUnpaid++;
    }
    if ($membership['status'] == 'paid') {
        $totalPaid++;
    }
    $memberships[$key]['displayLabel'] = $label;
}
if ($numExpired > 0) {
    $disablePay = ' disabled';
}

$VirtualButton = '';
if ($virtualURL != '') {
    if (array_key_exists('virtualBtn', $portal_conf))
        $VirtualButtonTxt = $portal_conf['virtualBtn'];
    else
        $VirtualButtonTxt = $con['label'] . 'Virtual Portal';

    if (!$hasVirtual) {
        $VirtualButton .= '<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" ' .
            'data-bs-title="Add and pay for ' . ($worldCon ? "a WSFS and " : "") . 'an attending or virtual membership to be able to attend the virtual convention.">';
        }

    $VirtualButton .= "<button class='btn btn-primary p-1' type='button' " .
        ($hasVirtual ? 'onclick="portal.virtual();"' : ' disabled') . ">$VirtualButtonTxt</button>";
    if (!$hasVirtual)
        $VirtualButton .= '</span>';

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
    var paymentPlanList = <?php echo json_encode($paymentPlans); ?>;
    var payorPlans = <?php echo json_encode($payorPlan); ?>;
    var membershipsPurchased = <?php echo json_encode($memberships); ?>;
    var paidOtherMembership = <?php echo json_encode($paidOtherMembership); ?>;
    var numCoupons = <?php echo $numCoupons; ?>;
    var policies = <?php echo json_encode($policies); ?>;
</script>
<?php
// draw all the modals for this screen
draw_editPersonModal('portal', $policies);
if ($interests != null && count($interests) > 0) {
    draw_editInterestsModal($interests);
}
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
    <div class='row mt-2 mb-2' id="managedByDiv">
        <div class='col-sm-auto'><h1 class='size-h4'>Your record is managed by <?php echo $info['managedByName']; ?>:</h1></div>
        <div class='col-sm-auto'><button class="btn btn-warning btn-sm p-1" onclick="portal.disassociate();">Dissociate from <?php echo $info['managedByName']; ?></button></div>
<?php if ($VirtualButton != '') { ?>
        <div class='col-sm-auto'><?php echo $VirtualButton; ?></div>
<?php } ?>
    </div>
<?php
    if ($NomNomURL != '' || $BusinessMeetingURL != '' || $SiteSelectionURL != '')
        drawWSFSButtons($NomNomURL != '', $BusinessMeetingURL != '', $SiteSelectionURL != '', $hasWSFS, $hasNom, $hasMeeting, $siteSelection, $loginId, $loginType, $info);
}
$totalDueFormatted = '';
if ($totalDue > 0 || $activePaymentPlans) {
    if ($totalDue > 0) {
        $totalDueFormatted = 'Total due: ' . $dolfmt->formatCurrency((float)$totalDue, $currency);
    } else {
        $totalDueFormatted = "You have an active payment plan, check to see if it needs paying: ";
    }
    if (count($paymentPlans) > 0) {
        $payHtml = " $totalDueFormatted   " .
            '<button class="btn btn-sm btn-primary pt-1 pb-1 ms-1 me-2" onclick="portal.setFocus(\'paymentDiv\');"' .
            $disablePay . '>Go to Payment Section</button>';
    } else {
        $payHtml = " $totalDueFormatted   " .
            '<button class="btn btn-sm btn-primary pt-1 pb-1 ms-1 me-2" name="payBalanceBTNs" onclick="portal.payBalance(' . $totalDue . ', true);"' .
            $disablePay . '>Pay Total Amount Due</button>';
    }
?>
    <div class='row mt-4'>
        <div class='col-sm-12'>
            <h1 class='size-h3'><?php echo $payHtml;?></h1>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr style='height:4px;width:98%;margin:auto;margin-top:0px;margin-bottom:0px;color:#333333;background-color:#333333;'/>
        </div>
    </div>
<?php
}
?>
<div class='row mt-2'>
    <div class='col-sm-12'>
        <h1 class="size-h3">This account's information:
<?php
    if (!$hasPasskey && getConfValue('portal', 'passkeyRpLevel', 'd') != 'd' &&
        array_key_exists('HTTPS', $_SERVER) && (isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'on')) {
?>
        <button class='btn btn-primary ms-1 p-1' type='button'
                        onclick="window.location='<?php echo $portal_conf['portalsite']; ?>/accountSettings.php?passkey=create';">
                    <img src='lib/passkey.png'>Create Passkey
                </button>
<?php
    }
    if ($info['managedByName'] == null) {
?>
                <button class='btn btn-primary ms-1 p-1' type='button'
                        onclick="window.location='<?php echo $portal_conf['portalsite']; ?>/addUpgrade.php';">
                    Add Another Person and<br/>Create a New Membership for Them
                </button>
                <?php echo $VirtualButton;
    }
?>
        </h1>
    </div>
</div>
<?php
    if ($info['managedByName'] == null && ($NomNomURL != '' || $BusinessMeetingURL != '' || $SiteSelectionURL != ''))
        drawWSFSButtons($NomNomURL != '', $BusinessMeetingURL != '', $SiteSelectionURL != '', $hasWSFS, $hasNom, $hasMeeting, $siteSelection, $loginId, $loginType, $info);

    outputCustomText('main/people');
?>
<div class="row mt-2">
    <div class="col-sm-1" style='text-align: right;'><b>ID</b></div>
    <div class="col-sm-2"><b>Person</b></div>
    <div class="col-sm-2"><b>Badge Name</b></div>
    <div class="col-sm-2"><b>Email Address</b></div>
    <div class="col-sm-1"><b>Actions</b></div>
</div>
<?php
$totalMemberships = count($holderMembership);
$paidByOthers = drawPersonRow($loginId, $loginType, $info, $holderMembership, $interests != null && count($interests) > 0, false, $now);

$managedMembershipList = '';
$currentId = -1;
$curMB = [];
// now for the people managed by this account holder
if ($info['managedByName'] == null && count($managed) > 0) {
?>
<div class='row'>
    <div class='col-sm-12 ms-0 me-0 align-center'>
        <hr style='height:4px;width:95%;margin:auto;margin-top:18px;margin-bottom:10px;color:#333333;background-color:#333333;'/>
    </div>
</div>
<div class='row mt-2'>
    <div class='col-sm-12'>
        <h1 class='size-h3'>
            <?php echo 'People managed by ' . $info['first_name'] . ' (' . $info['email_addr'] . '):'; ?>
        </h1>
    </div>
</div>
<?php
    $hrshow = false;
    foreach ($managed as $m) {
        if ($currentId != $m['id']) {
            if ($currentId > 0) {
                $totalMemberships += count($curMB);
                drawPersonRow($loginId, $loginType, $curPT, $curMB, $interests != null && count($interests) > 0, $hrshow, $now);
                $hrshow = true;
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
                'createPerid' => $m['createPerid'], 'completePerid' => $m['completePerid'], 'purchaserName' => $m['purchaserName'],
                'startdate' => $m['startdate'], 'enddate' => $m['enddate'], 'online' => $m['online'],
                'actPrice' => $m['actPrice'], 'actPaid' => $m['actPaid'], 'actCouponDiscount' => $m['actCouponDiscount'],
            );
        }
    }
}
if ($currentId > 0) { // if there are any at all
    $totalMemberships += count($curMB);
    drawPersonRow($loginId, $loginType, $curPT, $curMB, $interests != null && count($interests) > 0, true, $now);
}
// only draw the legend if someone has membership
if ($totalMemberships > 0)
    drawPortalLegend();

// create a div and bg color it to separate it logically from the other parts
if ($totalDue > 0 || count($payorPlan) > 0 || $paidByOthers > 0) {
?>
    <div class='container-fluid p-0 m-0' id="paymentSectionDiv" style="background-color: #F0F0FF;">
<?php
}

$payHtml = '';
$totalDueFormatted = '';
if ($totalDue > 0) {
    $totalDueFormatted = '&nbsp;&nbsp;Total due:' . $dolfmt->formatCurrency((float) $totalDue, $currency);
    $payHtml = " $totalDueFormatted   " .
        '<button class="btn btn-sm btn-primary pt-1 pb-1 ms-1 me-2" name="payBalanceBTNs" onclick="portal.payBalance(' . $totalDue . ', true);"' .
        $disablePay . '>Pay Total Amount Due</button>';
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
    drawPaymentPlans($info, $paymentPlansData);
}
if ($paidByOthers > 0) {
    // compute a list of mem id's, and the total amount due
    OutputCustomText('main/purchOthers');
    $otherDueFormatted = '<span id="otherDueAmountSpan">' . $dolfmt->formatCurrency((float) $paidByOthers, $currency) . '</span>' .
        '&nbsp;&nbsp;<button class="btn btn-sm btn-primary pt-1 pb-1 ms-1 me-2" onclick="portal.payOther(' . $paidByOthers . ');"' .
        $disablePay . '>Optionally Pay All or Part</button>';
?>
    <div class='row mt-5'>
        <div class='col-sm-12'><h1 class='size-h3'>Memberships Purchased by Others for You Total: <?php echo $otherDueFormatted; ?></h1></div>
    </div>

<?php
}
if ($totalDue > 0 || count($payorPlan) > 0 || $paidByOthers > 0 ) {
?>
    </div>
<?php
}

if (count($memberships) > 0) {
    if ($totalUnpaid > 0) {
        $showAll = 'disabled';
        $showUnpaid = 'disabled';
        $hideAll = 'disabled';
    }
    else {
        $showAll = 'disabled';
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
<?php
    }
?>
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
            if ($membership['status'] == 'plan' && $status == 'paid')
                $status = 'plan';
        }
    }
    $trans['t-' . $currentId] = $status;
    $currentId = -99999;
    $color = true;
    echo '<div class="container-fluid p-0 m-0" name="t-' . $trans['t-' . $memberships[0]['sortTrans']] .'">' .  PHP_EOL;
    foreach ($memberships as $membership)  {
        if ($loginType == 'p' && array_key_exists('transPerid', $membership) && $membership['transPerid'] != $loginId)
            continue;
        if ($loginType == 'n' && array_key_exists('transNewPerid', $membership) && $membership['transNewPerid'] != $loginId)
            continue;

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
            $status = '<b>Balance due: ' . $dolfmt->formatCurrency((float) $due, $currency) . '</b>';
        } else if ($membership['status'] == 'paid') {
            $status = 'paid: ' . $dolfmt->formatCurrency((float) $membership['paid'], $currency);
        } else if ($membership['status'] == 'plan') {
            $status = 'plan';
        }
?>
        <div class='row'>
            <div class='col-sm-1'></div>
            <div class='col-sm-2'><?php echo $status; ?></div>
            <div class='col-sm-3'><?php echo $membership['displayLabel']; ?></div>
            <div class="col-sm-6"><?php echo $membership['fullName'] . ' / ' . $membership['badge_name'];?></div>
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
    <div class="col-sm-4">
        <button class="btn btn-sm btn-primary pt-1 pb-1" id="payBalanceBTN" name='payBalanceBTNs' onclick="portal.payBalance(<?php echo $totalDue;?>);"<?php echo $disablePay;?>>
            Pay Balance
        </button>
    </div>
<?php
        if ($numExpired > 0) {
            if ($numExpired == 1)
                $expMsg = "one unpaid item  in your purchased list that is";
            else
                $expMsg = $numExpired . " unpaid items in your purchased list that are";
?>
    <div class="row mt-4">
        <div class="col-sm-12">
            <p>
                <span class="text-danger"><b>NOTE:</span> You have <?php echo $expMsg;?> no longer valid for purchase. This is bacause they
                either are no longer available for sale via the portal or the date for which they could have been purchased has passed.</b>
            </p>
            <p>
                You must use the "Add To/Edit Cart" for each person who has expired items in the list above and delete those items from the account.
                You can then replace them with items that are currently available for purchase. If you have issues with this please reach out to registration at
                the email address below.
            </p>
        </div>
    </div>
    <div class='row'>
        <div class='col-sm-12 p-0 m-0 align-center'>
            <hr style='height:4px;width:98%;margin:auto;margin-top:0px;margin-bottom:0px;color:#333333;background-color:#333333;'/>
        </div>
    </div>
<?php
        }
    }
}
?>
</div>
<?php
portalPageFoot();

function drawWSFSButtons($NomNomExists, $BusinessExists, $SiteExists, $hasWSFS, $hasNom, $hasMeeting, $hasSiteSelection, $loginId, $loginType, $info) {
    $portal_conf = get_conf('portal');

// buttons are NomNom, Site Selection, Virtual Business Meeting
    $NomNomButton = '';
    if ($NomNomExists) {
        if (!$hasNom)
            $NomNomButton .= '<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" ' .
                'data-bs-title="Add and pay for a WSFS membership to be able to nominate or vote.">';
        if (array_key_exists('nomnomBtn', $portal_conf))
            $nomnomBtnText = $portal_conf['nomnomBtn'];
        else
            $nomnomBtnText = 'Log into the Hugo System';

        $NomNomButton .= "<button class='btn btn-primary p-1' type='button' " .
            ($hasNom ? 'onclick="portal.vote();"' : ' disabled') . ">$nomnomBtnText</button>";
        if (!$hasNom)
            $NomNomButton .= '</span>';
    }

    $siteSelectionButton = '';
    if ($SiteExists) {
        $sslToken = '';
        if (!$hasSiteSelection) {
            $siteSelectionButton .= '<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" ' .
                'data-bs-title="Add and pay for a Site Selection Token using the \'Add To/Edit Cart\' button to the right, to be able to vote in site selection.">';
        }
        if ($SiteExists) {
            if ($hasSiteSelection && $loginType == 'p' && array_key_exists('siteselectionURL', $portal_conf)) {
                $key = $portal_conf['siteselectionKey'];
                $url = $portal_conf['siteselectionURL'];
                $sslQ = <<<EOS
SELECT CAST(AES_DECRYPT(encTokenKey, ?) AS char)
FROM siteSelectionTokens
WHERE perid = ?;
EOS;
                $sslR = dbSafeQuery($sslQ, 'si', array ($key, $loginId));
                if ($sslR !== false && $sslR->num_rows > 0) { // don't care if more than one, just get the first, in case of an error
                    $sslToken = $sslR->fetch_row()[0];
                    $site = $url . '/' . $sslToken;
                } else { // it hasn't been assigned yet, assign one
                    if ($sslR !== false)
                        $sslR->free();
                    $loginId = intval($loginId); // force it to be integer to avoid sql issues
                    $sslM = <<<EOS
SELECT @next := MIN(id) FROM siteSelectionTokens WHERE perid IS NULL;
UPDATE siteSelectionTokens
	SET perid = $loginId
    WHERE id = @next;
EOS;

                    $sslR = dbMultiQuery($sslM);
                    if ($sslR !== false) {
                        while (dbNextResult());
                        $sslR->free();
                        $sslR = dbSafeQuery($sslQ, 'si', array ($key, $loginId));
                        if ($sslR !== false && $sslR->num_rows > 0) {
                            $sslToken = $sslR->fetch_row()[0];
                            $site = $url . '/' . $sslToken;
                        } else {
                            $sslToken = '';
                            $site = '';
                        }
                    }
                }
                if ($sslR !== false)
                    $sslR->free();
            }
            if (array_key_exists('siteselectionBtn', $portal_conf))
                $siteSelectionBtnTxt = $portal_conf['siteselectionBtn'];
            else
                $siteSelectionBtnTxt = 'Vote in Site Selection';

            $siteSelectionButton .= "<button class='btn btn-primary p-1' type='button' " .
                (($hasSiteSelection && $sslToken != '') ? 'onclick="portal.siteSelect(' . "'$site'" . ');"' : ' disabled') . ">$siteSelectionBtnTxt</button>";
            if ($sslToken != '')
                $siteSelectionButton .= "<br/>Token: $sslToken";
            if (!$hasSiteSelection)
                $siteSelectionButton .= '</span>';
        }
    }

    $businessMeetingButton = '';
    $businessBtnSubText = '';
    if ($BusinessExists) {
        if (array_key_exists('businessBtn', $portal_conf))
            $businessBtnText = $portal_conf['businessBtn'];
        else
            $businessBtnText = 'Log into the Business Meeting';

        if (!$hasMeeting) {
            $businessMeetingButton .= '<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" ' .
                'data-bs-title="You must have a WSFS membership AND one of the following supplements: Friend, Attending, Virtual or One Day.">';
        } else {
            // compute the LUMI password, note this is Seattle Worldcon specific, so it will need to be modified for future worldcons
            $salt = 'SeattleIn2025';
            $pw = substr(preg_replace('/[a-f]/i', '', md5($loginId . $salt)), 0, 6);
            $un = $info['id'];
            $businessBtnSubText = "<br/>Membership Number: $un<br/>Password: $pw";
        }

        $businessURL = $portal_conf['businessmeetingURL'];

        $businessMeetingButton .= "<button class='btn btn-primary p-1' type='button' " .
            ($hasMeeting ? 'onclick="window.open(' . "'$businessURL');" .'"' : ' disabled') . ">$businessBtnText</button>";
        if (!$hasMeeting)
            $businessMeetingButton .= '</span>';
    }
    ?>
    <div class='row' id='wsfsButtonDiv'>
    <?php if ($NomNomButton != '') { ?>
        <div class='col-sm-auto'><?php echo $NomNomButton; ?></div>
    <?php }
        if ($businessMeetingButton != '') { ?>
            <div class='col-sm-auto'><?php echo $businessMeetingButton; if ($businessBtnSubText != '') echo "$businessBtnSubText"; ?></div>
        <?php }
        if ($siteSelectionButton != '') { ?>
            <div class='col-sm-auto'><?php echo $siteSelectionButton; ?></div>
        <?php } ?>
    </div>
<?php
}
