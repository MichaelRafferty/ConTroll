<?php

// library AJAX Processor: regposTasks.php
// Balticon Registration System
// Author: Syd Weinstein
// Perform tasks under the Volunteer Rollovrer page

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
    $response['user_id'] = $_SESSION['user'];

    // get the memId and label for conid + 1, volunteer rollover
    $priceQ = <<<EOS
SELECT id, label, shortname
FROM memLabel
WHERE
    conid = ? AND shortname = 'Volunteer';
EOS;

    $memarray = array();
    $r = dbSafeQuery($priceQ, 'i', array($conid + 1));
    if ($r->num_rows != 1) {
        ajaxError("Volunteer type not defined for conid " . ($conid + 1));
        return;
    }
    $l = fetch_safe_assoc($r);
    $response['rollover_memId'] = $l['id'];
    $response['rollover_label'] = $l['label'];
    $response['rollover_shortname'] = $l['shortname'];
    mysqli_free_result($r);
    ajaxSuccess($response);
}

// findRecord:
// load all perinfo/reg records matching the search string or unpaid if that flag is passed
function findRecord($conid):void {
    $name_search = $_POST['name_search'];
    $rollover_memId = $_POST['rollover_memId'];
    $response['name_search'] = $name_search;

    $limit = 99999999;
    if (is_numeric($name_search)) {
//
// this is perid
//
        $searchSQL = <<<EOS
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
    p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    TRIM(REGEXP_REPLACE(concat(p.last_name, ', ', p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' ')) AS fullname,
    p.open_notes, r.id AS regid, m.label, rn.id AS roll_regid, mn.shortname
FROM perinfo p
JOIN reg r ON (r.perid = p.id)
LEFT OUTER JOIN reg rn ON (rn.perid = p.id AND rn.conid = ? and rn.memId = ?)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN memLabel mn ON (rn.memId = mn.id)
WHERE p.id = ? AND r.conid = ? AND m.memCategory in ('upgrade', 'rollover', 'freebie', 'standard', 'yearahead')
ORDER BY r.id;
EOS;
        //web_error_log($searchSQLM);
        $r = dbSafeQuery($searchSQL, 'iiii', array($conid + 1, $rollover_memId, $name_search, $conid));
    } else {
//
// this is the string search portion as the field is alphanumeric
//
        // name match
        $limit = 50; // only return 50 people's memberships
        $name_search = '%' . preg_replace('/ +/', '%', $name_search) . '%';
        //web_error_log("match string: $name_search");
        $searchSQL = <<<EOS
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
    p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    TRIM(REGEXP_REPLACE(concat(p.last_name, ', ', p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' ')) AS fullname,
    p.open_notes, r.id AS regid, m.label, rn.id AS roll_regid, mn.shortname
FROM perinfo p
JOIN reg r ON (r.perid = p.id)
LEFT OUTER JOIN reg rn ON (rn.perid = p.id AND rn.conid = ? and rn.memId = ?)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN memLabel mn ON (rn.memId = mn.id)
WHERE r.conid = ? AND (LOWER(concat_ws(' ', first_name, middle_name, last_name)) LIKE ? OR LOWER(badge_name) LIKE ? OR LOWER(email_addr) LIKE ?)
AND  m.memCategory in ('upgrade', 'rollover', 'freebie', 'standard', 'yearahead')
ORDER BY last_name, first_name
LIMIT $limit;
EOS;
        $r = dbSafeQuery($searchSQL, 'iiisss', array($conid + 1, $rollover_memId, $conid, $name_search, $name_search, $name_search));
    }
    // now process the search results
    $perinfo = [];
    $index = 0;
    $perids = [];
    $num_rows = $r->num_rows;
    while ($l = fetch_safe_assoc($r)) {
        if (!array_key_exists($l['perid'], $perids)) {
            $perids[$l['perid']] = $index;
            $l['index'] = $index;
            $perinfo[] = $l;
        }
        $index++;
    }
    $response['perinfo'] = $perinfo;
    if ($num_rows >= $limit) {
        $response['warn'] = "$num_rows memberships found, limited to $limit, use different search criteria to refine your search.";
    } else {
        $response['message'] = "$num_rows memberships found";
    }
    mysqli_free_result($r);
    ajaxSuccess($response);
}

// rolloverMember
// create the rollover membership for this user
//  inputs:
//      member: info on the member to rollover
//      rollover_memId: memberhip to assign
//      user_id: user doing rollover
//  Outputs:
//      updated member record
function rolloverMember($conid): void
{
    $user_id = $_POST['user_id'];
    if ($user_id != $_SESSION['user']) {
        ajaxError("Invalid credentials passed");
    }
    $member = $_POST['member'];
    if (sizeof($member) <= 0) {
        ajaxError('no member passed');
        return;
    }
    $memId = $_POST['rollover_memId'];
    if ($memId === null) {
        ajaxError("No rollover type passed");
    }
    $shortname = $_POST['rollover_shortname'];
    $response['index'] = $_POST['index'];

    // create the controlling transaction for the rollover
    $notes = 'Volunteer Rollover';
    $insTransactionSQL = <<<EOS
INSERT INTO transaction(conid,perid,userid,price,paid,type,create_date)
VALUES (?,?,?,0,0,'atcon',now());
EOS;
    // now insert the master transaction
    $paramarray = array($conid + 1, $member['perid'], $user_id);
    $typestr = 'iii';
    $master_transid = dbSafeInsert($insTransactionSQL, $typestr, $paramarray);
    if ($master_transid === false) {
        ajaxError('Unable to create master transaction');
        return;
    }
    // now insert the rollover membership

    $insRegSQL = <<<EOS
INSERT INTO reg(conid,perid,price,paid,create_user,create_trans,memId,create_date)
VALUES (?,?,0,0,?,?,?,now());
EOS;
    $paramarray = array($conid + 1, $member['perid'], $user_id, $master_transid, $memId);
    $typestr = 'iiiii';
    $new_regid = dbSafeInsert($insRegSQL, $typestr, $paramarray);
    if ($new_regid === false) {
        $error_message .= "Insert of membership $row failed<BR/>";
    }
    $member['roll_regid'] = $new_regid;
    $member['shortname'] = $shortname;
    $member['roll_tid'] = $master_transid;

    $response['message'] = "Member volunteer rollover created as tid: $master_transid, id: $new_regid";
    $response['member'] = $member;
    $response['master_tid'] = $master_transid;
    ajaxSuccess($response);
}

// outer ajax wrapper
// method - permission required to access this AJAX function
// action - passed in from the javascript

$method = 'vol_roll';
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
    case 'rolloverMember':
        rolloverMember($conid);
        break;
    default:
        $message_error = 'Internal error.';
        RenderErrorAjax($message_error);
        exit();
}
