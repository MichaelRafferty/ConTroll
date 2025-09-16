<?php
// library AJAX Processor: pos_loadInitialData.php
// Balticon Registration System
// Author: Syd Weinstein
// Retrieve load the mapping tables and session information into the javascript side of the registration tab

require_once '../lib/base.php';
require_once('../../lib/cc__load_methods.php');
require_once('../../lib/coupon.php');
require_once '../../lib/memRules.php';
require_once '../../lib/policies.php';

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$con = get_conf('con');
$atcon = get_conf('atcon');
$controll = get_conf('controll');
$debug = get_conf('debug');
$usps = get_conf('usps');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'loadInitialData') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

$useUSPS = false;
if (($usps != null) && array_key_exists('secret', $usps) && ($usps['secret'] != ''))
    $useUSPS = true;

$cc = get_conf('cc');
load_cc_procs();
// loadInitialData:
// Load all the mapping tables for the POS function


$response['label'] = $con['label'];
$response['conid'] = $conid;
$response['discount'] = $atcon['discount'];
$response['badgePrinter'] = getSessionVar('badgePrinter')['name'] != 'None';
$response['receiptPrinter'] = getSessionVar('receiptPrinter')['name'] != 'None';
if (isSessionVar('terminal'))
    $response['terminal'] = getSessionVar('terminal')['name'] != 'None';
else
    $response['terminal'] = false;
$response['user_id'] = getSessionVar('user');
$response['Manager'] = check_atcon('manager', $conid);

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
$r->free();
$searchdate = date('Y-m-d');
if (getConfValue('atcon', 'precon', 0) == 0) {
    // get all types registration can set
    // if now is pre or post con set search date to first day of con
    //web_error_log("start = " . strtotime($startdate) . ", end = " . strtotime($enddate) . ", now = " . time());
    if (time() < strtotime($startdate) || strtotime($enddate) + 24 * 60 * 60 < time()) {
        $searchdate = $startdate;
    }
}
//web_error_log("Search date now $searchdate");


// get all the memLabels
$priceQ = <<<EOS
SELECT id, conid, memCategory, memType, memAge, taxable,
       CASE WHEN conid = ? THEN label ELSE concat(conid, ' ', label) END AS label, ageShortName, 
       shortname, sort_order, price, CAST(startdate AS date) AS startdate, CAST(enddate AS date) AS enddate,
    CASE 
        WHEN (atcon != 'Y') THEN 0
        WHEN (startdate > ?) THEN 0
        WHEN (enddate <= ?) THEN 0
        ELSE 1 
    END AS canSell, glNum
FROM memLabel
WHERE
    conid IN (?, ?)
ORDER BY sort_order, price DESC;
EOS;

$memarray = array();
$r = dbSafeQuery($priceQ, 'issii', array($conid, $searchdate, $searchdate, $conid, $conid + 1));
while ($l = $r->fetch_assoc()) {
    $memarray[] = $l;
}
$r->free();
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
$r->free();
$response['memTypes'] = $typearray;

// memCategories
$memCategorySQL = <<<EOS
SELECT memCategory, onlyOne, standAlone, variablePrice, taxable, badgeLabel
FROM memCategories
WHERE active = 'Y'
ORDER BY sortorder;
EOS;

$catarray = array();
$r = dbQuery($memCategorySQL);
while ($l = $r->fetch_assoc()) {
    $catarray[] = $l;
}
$r->free();
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
$r->free();
$response['ageList'] = $agearray;

// coupons
$ret = load_coupon_list();
$response['num_coupons'] = $ret[0];
$response['couponList'] = $ret[1];

// membership rules, policies, configuration items
$ruleData = getRulesData($conid, false, true);

$response['gageList'] = $ruleData['ageList'];
$response['gageListIdx'] = $ruleData['ageListIdx'];
$response['gmemTypes'] = $ruleData['memTypes'];
$response['gmemCategories'] = $ruleData['memCategories'];
$response['gmemList'] = $ruleData['memList'];
$response['gmemListIdx'] = $ruleData['memListIdx'];
$response['gmemRules'] = $ruleData['memRules'];
$response['policies'] = getPolicies();
$cdebug = 0;
if (array_key_exists('controll_registration', $debug))
    $cdebug = $debug['controll_registration'];
$response['debug'] = $cdebug;
$response['required'] = getConfValue('reg', 'required', 'addr');
$response['useUSPS'] = $useUSPS;

ajaxSuccess($response);
