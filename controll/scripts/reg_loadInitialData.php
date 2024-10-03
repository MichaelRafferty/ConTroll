<?php
// library AJAX Processor: reg_loadInitialData.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve load the mapping tables and session information into the javascript side of the registration tab

require_once '../lib/base.php';
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/coupon.php');
require_once '../../lib/memRules.php';
require_once '../../lib/policies.php';

$check_auth = google_init('ajax');
$perm = 'registration';

$response = array('post' => $_POST, 'get' => $_GET, 'perm' => $perm);

if ($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    RenderErrorAjax('Authentication Failed');
    exit();
}

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$atcon = get_conf('atcon');
$reg_conf = get_conf('reg');
$controll = get_conf('controll');
$debug = get_conf('debug');
$usps = get_conf('usps');
$conid = $con['id'];


$useUSPS = false;
if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
    $useUSPS = true;

$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'loadInitialData') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$cc = get_conf('cc');
load_cc_procs();
// loadInitialData:
// Load all the mapping tables for the POS function

$response['label'] = $con['label'];
$response['conid'] = $conid;
$response['discount'] = $atcon['discount'];
$response['badgePrinter'] = false; //$_SESSION['badgePrinter'][0] != 'None';
$response['receiptPrinter'] = false; //$_SESSION['receiptPrinter'][0] != 'None';
$response['user_id'] = $_SESSION['user_id'];
$response['cc_html'] = draw_cc_html($cc);
// do as if statement such that it can check for both database error and no rows returned
$Manager = checkAuth($check_auth['sub'], 'reg_admin');
if ($Manager !== false && sizeof($Manager) > 0)
    $Manger = true;
else
    $Manager= false;
$response['Manager'] = $Manager;
// get the start and end dates, and adjust for the memLabels based on the real dates versus today.
$condatesSQL = <<<EOS
SELECT startdate, enddate
FROM conlist
WHERE id=?;
EOS;
$r = dbSafeQuery($condatesSQL, 'i', array($conid));
if ($r->num_rows == 1) {
    $l = $r->fetch_assoc();
    $startdate = $l['startdate'];
    $enddate = $l['enddate'];
    $response['startdate'] = $startdate;
    $response['enddate'] = $enddate;
} else {
    RenderErrorAjax('Current convention ($conid) not in the database.');
    exit();
}
mysqli_free_result($r);
// get all types registration can set


// get all the memLabels
$priceQ = <<<EOS
WITH memitems AS (
    SELECT conid, memCategory, memType, memAge, shortname, price, min(startdate) as startdate, max(enddate) as enddate
    FROM memLabel
    WHERE conid IN (?, ?)
    GROUP BY conid, memCategory, memType, memAge, shortname, price    
), useIDs AS (
    SELECT id AS matchid, m.startdate, m.enddate
    FROM memitems m
    JOIN memLabel l ON (
        l.conid = m.conid AND l.memCategory = m.memCategory AND l.memType = m.memType
        AND l.memAge = m.memAge AND l.shortname = m.shortname AND l.price = m.price
    )
)
SELECT id, conid, memCategory, memType, memAge,
       CASE WHEN conid = ? THEN label ELSE concat(conid, ' ', label) END AS label, 
       shortname, sort_order, price, CAST(m.startdate AS date) AS startdate, CAST(m.enddate AS date) AS enddate,
       CASE WHEN u.startdate = m.startdate AND u.enddate = m.enddate THEN 1 ELSE 0 END AS canSell
FROM useIDs u
JOIN memLabel m ON (m.id = u.matchid)
ORDER BY sort_order, price DESC;
EOS;

$memarray = array();
$r = dbSafeQuery($priceQ, 'iii', array($conid, $conid + 1, $conid));
while ($l = $r->fetch_assoc()) {
    $memarray[] = $l;
}
mysqli_free_result($r);
$response['memLabels'] = $memarray;

// memTypes
$memTypeSQL = <<<EOS
SELECT memType
FROM memTypes
WHERE active = 'Y'
ORDER BY sortorder;
EOS;

$typearray = array();
$r = dbQuery($memTypeSQL);
while ($l = $r->fetch_assoc()) {
    $typearray[] = $l['memType'];
}
mysqli_free_result($r);
$response['memTypes'] = $typearray;

// memCategories
$memCategorySQL = <<<EOS
SELECT memCategory, onlyOne, standAlone, variablePrice, badgeLabel
FROM memCategories
WHERE active = 'Y'
ORDER BY sortorder;
EOS;

$catarray = array();
$r = dbQuery($memCategorySQL);
while ($l = $r->fetch_assoc()) {
    $catarray[] = $l;
}
mysqli_free_result($r);
$response['memCategories'] = $catarray;

// ageList
$ageListSQL = <<<EOS
SELECT ageType, label, shortname
FROM ageList
WHERE conid = ?
ORDER BY sortorder;
EOS;

$agearray = array();
$r = dbSafeQuery($ageListSQL, 'i', array($conid));
while ($l = $r->fetch_assoc()) {
    $agearray[] = $l;
}
mysqli_free_result($r);
$response['ageList'] = $agearray;

// coupons
$ret = load_coupon_list();
$response['num_coupons'] = $ret[0];
$response['couponList'] = $ret[1];

// membership rules, policies, configuration items
$response['memRules'] = getRulesData($conid);
$response['policies'] = getPolicies();
$response['label'] = $con['label'];
$cdebug = 0;
if (array_key_exists('controll_registration', $debug))
    $cdebug = $debug['controll_registration'];
$response['debug'] = $cdebug;
$response['required'] = $reg_conf['required'];
$response['useUSPS'] = $useUSPS;
$response['userId'] = $_SESSION['user_perid'];

ajaxSuccess($response);
