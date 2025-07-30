<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$conid=getConfValue('con', 'id');
$nomDate = getConfValue('portal', 'nomDate', '2099-12-31');
$response['conid'] = $conid;

if (!array_key_exists('rights', $_POST)) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

validateLoginId();

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
    if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');

$response['personType'] = $loginType;
$response['personId'] = $loginId;

$rightsCheck = $_POST['rights'];
if (array_key_exists('NomNom', $rightsCheck) && $rightsCheck['NomNom'] == 1)
    $NomNom = true;
else
    $NomNom = false;

if (array_key_exists('Virtual', $rightsCheck) && $rightsCheck['Virtual'] == 1)
    $Virtual = true;
else
    $Virtual = false;

$worldCon = getConfValue('portal', 'worldcon', '0');

// Ok, we need the payload now, lets start with the main info
if ($loginType == 'p') {
    $piQ = <<<EOS
SELECT p.id AS perid, n.id AS newperid, p.first_name, p.last_name, p.email_addr, p.badge_name,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName
FROM perinfo p
LEFT OUTER JOIN newperson n ON n.perid = p.id
WHERE p.id = ?
ORDER BY n.id DESC;
EOS;
} else {
    $piQ = <<<EOS
SELECT NULL AS perid, id AS newperid, first_name, last_name, email_addr, badge_name,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', first_name, middle_name, last_name, suffix), '  *', ' ')) AS fullName
FROM newperson 
WHERE id = ?;
EOS;
}
$piR = dbSafeQuery($piQ, 'i', array($loginId));
if ($piR === false) {
    $response['error'] = "Error retrieving your personal information, seek assistance";
    ajaxSuccess($response);
    exit();
}
$pi = $piR->fetch_assoc(); // we only one row, the one with the highest newperid, if there is one at all.
$piR->free();

$payload = [];
$payload['email'] = $pi['email_addr'];
$payload['perid'] = $pi['perid'];
$payload['newperid'] = $pi['newperid'];
$payload['legalName'] = null;
$payload['first_name'] = $pi['first_name'];
$payload['last_name'] = $pi['last_name'];
$payload['fullName'] = $pi['fullName'];
if ($Virtual) {
    $payload['badgeName'] = $pi['badge_name'];
}
// set expiration time to 4 hours, and a fake restype of fullRights
$payload['exp'] = time() + 4 * 3600;
$payload['resType'] = 'fullRights';
// Now compute the rights - NomNom
$rights = '';
$key = null;

// process nom nom rights
if ($NomNom) {
    if ($loginType == 'p') {
        $rSQL = <<<EOS
SELECT r.perid AS perid, r.newperid AS newperid, m.label, m.memCategory, m.memType,
       t.create_date, t.complete_date, t.create_date < ? AS inTime
FROM reg r
LEFT OUTER JOIN transaction t ON r.complete_trans = t.id
LEFT OUTER JOIN memList m ON r.memId = m.id
WHERE r.perid = ? AND r.conid = ? AND r.status = 'paid';
EOS;
    } else {
        $rSQL = <<<EOS
SELECT NULL AS perid, r.newperid AS newperid, m.label, m.memCategory, m.memType,
       t.create_date, t.complete_date, t.create_date < ? AS inTime
FROM reg r
LEFT OUTER JOIN transaction t ON r.complete_trans = t.id
LEFT OUTER JOIN memList m ON r.memId = m.id
WHERE r.newperid = ? AND r.conid = ? AND r.status = 'paid';
EOS;
    }

    $rR = dbSafeQuery($rSQL, 'sii', array ($nomDate, $loginId, $conid));
    if ($rR === false) {
        $response['error'] = 'Error retrieving your rights information, seek assistance';
        ajaxSuccess($response);
        exit();
    }
    $regs = [];
    while ($rL = $rR->fetch_assoc()) {
        $regs[] = $rL;
    }
    $rR->free();

// build the rights
    $nom = '';
    $vote = '';
    $addlWSFS = getConfValue('portal', 'addlWSFS');
    if ($addlWSFS == '')
        $addlWSFS = [];
    else
        $addlWSFS = explode(',', $addlWSFS);

    for ($row = 0; $row < count($regs); $row++) {
        $reg = $regs[$row];
        if ((($reg['memCategory'] == 'wsfs' || $reg['memCategory'] == 'dealer') && $reg['inTime'] == 1) || in_array($reg['memId'], $addlWSFS)
            || ($reg['memCategory'] == 'wsfsnom')) {
            $nom = 'hugo_nominate';
            break;
        }
    }
    for ($row = 0; $row < count($regs); $row++) {
        $reg = $regs[$row];
        if (($reg['memCategory'] == 'wsfs' && str_contains(strtolower($reg['label']), ' only') == false) ||
            ($reg['memCategory'] == 'dealer') || in_array($reg['memId'], $addlWSFS)) {
            $vote = 'hugo_vote';
            break;
        }
    }

    $rights = $nom . (($nom != '' && $vote != '') ? ',' : '') . $vote;
    $key = getConfValue('portal', 'nomnomKey', '');
}

// rights for virtual
if ($Virtual) {
    // if we got to this routine, virtual was alredy verified in portal, but we can just check for the attending part and skip the WSFS part here.
    $numPaidPrimary = 0;
    $numChild = 0;
    $hasWSFS = false;

// get the account holder's registrations
    $holderRegSQL = <<<EOS
SELECT r.status, r.memId, m.*, a.shortname AS ageShort, a.label AS ageLabel, a.ageType, m.taxable, m.ageShortName,
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
    // determine business meeting, site selection and wsfs
    // wsfs is any WSFS membership except nomination only
    // meeting = (later has WSFS anded with it)
    //			any type ‘full’ (
    //				except ‘access caregiver’ (hard code 622) (yuck on the hard code) OR
    //				 ‘artist’ (mail-in artist)
    //			)
    //			OR
    //			any type ‘virtual’ OR
    //			any type ‘oneday'
    $memberships = [];
    $addlWSFS = getConfValue('portal', 'addlWSFS');
    if ($addlWSFS == '')
        $addlWSFS = [];
    else
        $addlWSFS = explode(',', $addlWSFS);

    if ($holderRegR !== false && $holderRegR->num_rows > 0) {
        while ($m = $holderRegR->fetch_assoc()) {
            // check if they have a WSFS rights membership (hasWSFS and hasNom)
            if (($m['memCategory'] == 'wsfs' || $m['memCategory'] == 'dealer' || in_array($m['memId'], $addlWSFS)) && $m['status'] == 'paid') {
                $hasWSFS = true;
            }

            // check age to prevent virtual
            if ($m['ageType'] == 'child' || $m['ageType'] == 'kit')
                $numChild++;

            if (isPrimary($m, $conid) && $m['status'] == 'paid')
                $numPaidPrimary++;
            else
                continue;

            if ($m['conid'] != $conid)
                $shortname = $m['conid'] . ' ' . $m['shortname'];
            else
                $shortname = $m['shortname'];

            $memberships[] .= implode(':', array ($m['memAge'], $m['memType'], $m['memCategory'], $shortname));
        }
        $holderRegR->free();
    }

    $hasVirtual = ($numPaidPrimary > 0) && ((!$worldCon) || $hasWSFS) && ($worldCon || $numChild == 0);
    if ($hasVirtual) {
        if ($rights != '')
            $rights .= ',';
        $rights .= 'virtual,' . implode(',', $memberships);
    }
    if ($key == null)
        $key = getConfValue('portal', 'virtualKey', '');
}

$payload['rights'] = $rights;
$response['rights'] = $rights;

setJWTKey($key);
$jwt = genJWT($payload);
$response['payload'] = $payload;
$response['jwt'] = $jwt;
ajaxSuccess($response);
