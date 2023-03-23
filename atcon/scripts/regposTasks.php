<?php

// library AJAX Processor: regposTasks.php
// Balticon Registration System
// Author: Syd Weinstein
// Perform tasks under the POS pages

require_once('../lib/base.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

// loadInitialData:
// Load all the mapping tables for the POS function
function loadInitialData($conid, $con): void
{
    $response['label'] = $con['label'];
    $response['conid'] = $conid;
    $response['badgePrinter'] = $_SESSION['badgePrinter'][0] != 'None';
    $response['receiptPrinter'] = $_SESSION['receiptPrinter'][0] != 'None';
    // get the start and end dates, and adjust for the memLabels based on the real dates versus today.
    $condatesSQL = <<<EOS
SELECT startdate, enddate
FROM conlist
WHERE id=?;
EOS;
    $r = dbSafeQuery($condatesSQL, 'i', array($conid));
    if ($r->num_rows == 1) {
        $l = fetch_safe_assoc($r);
        $startdate = $l['startdate'];
        $enddate = $l['enddate'];
        $response['startdate'] = $startdate;
        $response['enddate'] = $enddate;
    } else {
        RenderErrorAjax('Current convention ($conid) not in the database.');
        exit();
    }
    mysqli_free_result($r);
    // if now is pre or post con set search date to first day of con
    //web_error_log("start = " . strtotime($startdate) . ", end = " . strtotime($enddate) . ", now = " . time());
    if (time() < strtotime($startdate) || strtotime($enddate) +24*60*60 < time()) {
        $searchdate = $startdate;
    } else {
        $searchdate = date('Y-m-d');
    }
    //web_error_log("Search date now $searchdate");

    // get all the memLabels
    $priceQ = <<<EOS
SELECT id, conid, memCategory, memType, memAge, memGroup, label, shortname, sort_order, price
FROM memLabel
WHERE
    ((conid=? AND memCategory != 'yearahead') OR (conid=? AND memCategory in ('yearahead', 'rollover')))
    AND atcon = 'Y'
    AND startdate <= ?
    AND enddate > ?
ORDER BY sort_order, price DESC;
EOS;

    $memarray = array();
    $r = dbSafeQuery($priceQ, 'iiss', array($conid, $conid + 1, $searchdate, $searchdate));
    while ($l = fetch_safe_assoc($r)) {
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
    while ($l = fetch_safe_assoc($r)) {
        $typearray[] = $l['memType'];
    }
    mysqli_free_result($r);
    $response['memTypes'] = $typearray;

    // memCategories
    $memCategorySQL = <<<EOS
SELECT memCategory
FROM memCategories
WHERE active = 'Y'
ORDER BY sortorder;
EOS;

    $catarray = array();
    $r = dbQuery($memCategorySQL);
    while ($l = fetch_safe_assoc($r)) {
        $catarray[] = $l['memCategory'];
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
    while ($l = fetch_safe_assoc($r)) {
        $agearray[] = $l;
    }
    mysqli_free_result($r);
    $response['ageList'] = $agearray;

    ajaxSuccess($response);
}

// findRecord:
// load all perinfo/reg records matching the search string or unpaid if that flag is passed
function findRecord($conid):void {
    $find_type = $_POST['find_type'];
    $name_search = $_POST['name_search'];

    $response['find_type'] = $find_type;
    $response['name_search'] = $name_search;

    if ($find_type == 'unpaid') {
        $unpaidSQLP = <<<EOS
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
	p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    TRIM(REGEXP_REPLACE(concat(p.last_name, ', ', p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' ')) AS fullname
FROM reg r
JOIN perinfo p ON (p.id = r.perid)
WHERE r.conid = ? AND r.price != r.paid
ORDER BY last_name, first_name;
EOS;
        $unpaidSQLM = <<<EOS
SELECT DISTINCT r.perid, r.id as regid, r.conid, r.price, r.paid, r.create_trans as tid, r.memid, 0 as printcount,
                m.memCategory, m.memType, m.memAge, m.label, m.shortname, m.memGroup
FROM reg r
JOIN perinfo p ON (p.id = r.perid)
JOIN memLabel m ON (r.memId = m.id)
WHERE r.conid = ? AND r.price != r.paid
ORDER BY create_trans;
EOS;
        $rp = dbSafeQuery($unpaidSQLP, 'i', array($conid));
        $rm = dbSafeQuery($unpaidSQLM, 'i', array($conid));
    } else if (is_numeric($name_search)) {
        // this is perid, or transid
        $searchSQLP = <<<EOS
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
    p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    TRIM(REGEXP_REPLACE(concat(p.last_name, ', ', p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' ')) AS fullname
FROM reg r
JOIN perinfo p ON (p.id = r.perid)
WHERE r.conid = ? AND (r.create_trans = ? OR p.id = ?)
ORDER BY last_name, first_name;
EOS;
        $searchSQLM = <<<EOS
SELECT DISTINCT r.perid, r.id as regid, r.conid, r.price, r.paid, r.create_trans as tid, r.memid, 0 as printcount,
                m.memCategory, m.memType, m.memAge, m.label, m.shortname, m.memGroup
FROM reg r
JOIN perinfo p ON (p.id = r.perid)
JOIN memLabel m ON (r.memId = m.id)
WHERE r.conid = ? AND (r.create_trans = ? OR p.id = ?)
ORDER BY create_trans;
EOS;
        $rp = dbSafeQuery($searchSQLP, 'iii', array($conid, $name_search, $name_search));
        $rm = dbSafeQuery($searchSQLM, 'iii', array($conid, $name_search, $name_search));
    } else {
            // name match
        $name_search = '%' . preg_replace('/ +/', '%', $name_search) . '%';
        //web_error_log("match string: $name_search");
        $searchSQLP = <<<EOS
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
    p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    TRIM(REGEXP_REPLACE(concat(p.last_name, ', ', p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' ')) AS fullname
FROM reg r
JOIN perinfo p ON (p.id = r.perid)
WHERE r.conid = ? AND (LOWER(concat_ws(' ', first_name, middle_name, last_name)) LIKE ? OR LOWER(badge_name) LIKE ? OR LOWER(email_addr) LIKE ?)
ORDER BY last_name, first_name;
EOS;
        $searchSQLM = <<<EOS
SELECT DISTINCT r.perid, r.id as regid, r.conid, r.price, r.paid, r.create_trans as tid, r.memid, 0 as printcount,
                m.memCategory, m.memType, m.memAge, m.label, m.shortname, m.memGroup
FROM reg r
JOIN perinfo p ON (p.id = r.perid)
JOIN memLabel m ON (r.memId = m.id)
WHERE r.conid = ? AND (LOWER(concat_ws(' ', p.first_name, p.middle_name, p.last_name)) LIKE ? OR LOWER(p.badge_name) LIKE ? OR LOWER(p.email_addr) LIKE ?)
ORDER BY create_trans;
EOS;
        $rp = dbSafeQuery($searchSQLP, 'isss', array($conid, $name_search, $name_search, $name_search));
        $rm = dbSafeQuery($searchSQLM, 'isss', array($conid, $name_search, $name_search, $name_search));
    }

    $perinfo = [];
    $index = 0;
    $perids = [];
    $num_rows = $rp->num_rows;
    while ($l = fetch_safe_assoc($rp)) {
        $l['index'] = $index;
        $perinfo[] = $l;
        $perids[$l['perid']] = $index;
        $index++;
    }
    $response['perinfo'] = $perinfo;
    $response['message'] = "$num_rows memberships found";
    mysqli_free_result($rp);

    $membership = [];
    $index = 0;
    while ($l = fetch_safe_assoc($rm)) {
        $l['pindex'] = $perids[$l['perid']];
        $l['index'] = $index;
        $membership[] = $l;
        $index++;
    }
    $response['membership'] = $membership;
    mysqli_free_result($rm);
    ajaxSuccess($response);
}

// outer ajax wrapper
// method - permission required to access this AJAX function
// action - passed in from the javascript

$method = 'cashier';
if ($_POST && array_key_exists('nopay', $_POST)) {
    if ($_POST['nopay'] == 'true') {
        $method = 'data_entry';
    }
}

$con = get_conf('con');
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action == '') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}
if (!check_atcon($method, $conid)) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}
switch ($ajax_request_action) {
    case 'loadInitialData':
        loadInitialData($conid, $con);
        break;
    case 'findRecord':
        findRecord($conid);
        break;
    case 'updateUsers':
        updateUsers($conid);
        break;
    case 'updatePrinters':
        updatePrinters($conid);
        break;
    default:
        $message_error = 'Internal error.';
        RenderErrorAjax($message_error);
        exit();
}
