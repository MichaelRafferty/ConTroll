<?php
require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$portal_conf = get_conf('portal');
if (array_key_exists('nomdate', $portal_conf))
    $nomDate = $portal_conf['nomdate'];
else
    $nomDate = '2099-12-31';

$response['conid'] = $conid;

if (!array_key_exists('NomNom', $_POST)) {
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

// Ok, we need the payload now, lets start with the main info
if ($loginType == 'p') {
    $piQ = <<<EOS
SELECT p.id AS perid, n.id AS newperid, p.first_name, p.last_name, p.email_addr,
    TRIM(REGEXP_REPLACE(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix), '  *', ' ')) AS fullName
FROM perinfo p
LEFT OUTER JOIN newperson n ON n.perid = p.id
WHERE p.id = ?
ORDER BY n.id DESC;
EOS;
} else {
    $piQ = <<<EOS
SELECT NULL AS perid, id AS newperid, first_name, last_name, email_addr,
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
// set expiration time to 4 hours, and a fake restype of fillRights
$payload['exp'] = time() + 4 * 3600;
$payload['resType'] = 'fullRights';
// Now compute the rights

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

$rR = dbSafeQuery($rSQL, 'sii', array($nomDate, $loginId, $conid));
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
for ($row = 0; $row < count($regs); $row++) {
    $reg = $regs[$row];
    if ((($reg['memCategory'] == 'wsfs' || $reg['memCategory'] == 'dealer') && $reg['inTime'] == 1) || ($reg['memType'] == 'wsfsfree')
        || ($reg['memCategory'] == 'wsfsnom')) {
        $nom = 'hugo_nominate';
        break;
    }
}
for ($row = 0; $row < count($regs); $row++) {
    $reg = $regs[$row];
    if (($reg['memCategory'] == 'wsfs' && str_contains(strtolower($reg['label']), ' only') == false) ||
        ($reg['memCategory'] == 'dealer') || ($reg['memType'] == 'wsfsfree')) {
        $vote = 'hugo_vote';
         break;
    }
}

$rights = $nom . (($nom != '' && $vote != '') ? ',' : '') . $vote;
$payload['rights'] = $rights;
$response['rights'] = $rights;

// now build the key
$key = null;
if (array_key_exists('nomnomKey', $portal_conf))
    $key = $portal_conf['nomnomKey'];
setJWTKey($key);
$jwt = genJWT($payload);
$response['payload'] = $payload;
$response['jwt'] = $jwt;
ajaxSuccess($response);
