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
    $response['user_id'] = $_SESSION['user'];
    $response['hasManager'] = check_atcon('manager', $conid);
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
SELECT id, conid, memCategory, memType, memAge, memGroup, label, shortname, sort_order, price,
    CASE 
        WHEN (atcon != 'Y') THEN 0
        WHEN (startdate > ?) THEN 0
        WHEN (enddate <= ?) THEN 0
        ELSE 1 
    END AS canSell      
FROM memLabel
WHERE
    conid IN (?, ?)
ORDER BY sort_order, price DESC;
EOS;

    $memarray = array();
    $r = dbSafeQuery($priceQ, 'ssii', array($searchdate, $searchdate, $conid, $conid + 1));
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

    $limit = 99999999;
    if ($find_type == 'unpaid') {
//
// Find Unpaid on latest transaction ID for those records
//
        $withClauseUnpaid = <<<EOS
WITH unpaids AS (
/* first the unpaid transactions from regs with their create_trans */
SELECT r.id, create_trans as tid
FROM reg r
JOIN memList m ON (m.id = r.memId)
WHERE r.price != r.paid AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
), tids AS (
/* add in unpaids from transactions in attach records in atcon_history */
SELECT u.id AS regid, CASE WHEN u.tid > IFNULL(h.tid, -999) THEN u.tid ELSE h.tid END AS tid
FROM unpaids u
LEFT OUTER JOIN atcon_history h ON (h.regid = u.id AND h.action = 'attach')
), maxtids AS (
/* find the most recent transaction (highest number) across each reg and the selected list of transactions */
SELECT regid, MAX(tid) AS tid
FROM tids
GROUP BY regid
), tidlist AS (
/* and get each tid only once */
SELECT DISTINCT tid 
FROM maxtids
), perids AS (
/* now get all the perinfo ids that are mentioned in each of those tid records, from both reg, and from atcon_history */
SELECT perid 
FROM reg r
JOIN tidlist t ON (t.tid = r.create_trans)
UNION SELECT perid 
FROM reg r
JOIN atcon_history h on (h.regid = r.id)
JOIN tidlist t ON (t.tid = h.tid)
), uniqueperids AS (
SELECT DISTINCT perid
FROM perids
)
EOS;
        $unpaidSQLP = <<<EOS
$withClauseUnpaid
SELECT DISTINCT u.perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
	p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    TRIM(REGEXP_REPLACE(concat(p.last_name, ', ', p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' ')) AS fullname,
    p.open_notes
FROM uniqueperids u
JOIN perinfo p ON (u.perid = p.id)
ORDER BY last_name, first_name;
EOS;
        $unpaidSQLM = <<<EOS
$withClauseUnpaid
, ridtid AS (
SELECT r.id as regid, create_trans as tid
FROM uniqueperids p
JOIN reg r ON (r.perid = p.perid)
UNION
SELECT h.regid, h.tid
FROM uniqueperids p
JOIN reg r ON (r.perid = p.perid)
JOIN atcon_history h ON (r.id = h.regid AND h.action = 'attach')
), uniqrids AS (
SELECT regid, MAX(tid) AS tid
FROM ridtid
GROUP BY regid
), notes AS (
SELECT h.regid, GROUP_CONCAT(CONCAT(h.userid, '@', h.logdate, ': ', h.notes) SEPARATOR '\n') AS reg_notes, COUNT(*) AS reg_notes_count
FROM unpaids m
JOIN atcon_history h ON (m.id = h.regid)
WHERE h.action = 'notes'
GROUP BY h.regid
), printcount AS (
SELECT h.regid, COUNT(*) printcount
FROM unpaids m
JOIN atcon_history h ON (m.id = h.regid)
WHERE h.action = 'print'
GROUP BY h.regid
)
SELECT DISTINCT r.perid, r.id as regid, m.conid, r.price, r.paid, r.create_date, u.tid, r.memId, IFNULL(pc.printcount, 0) AS printcount,
                n.reg_notes, n.reg_notes_count, m.memCategory, m.memType, m.memAge, m.label, m.shortname, m.memGroup
FROM uniqrids u
JOIN reg r ON (r.id = u.regid)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN printcount pc ON (r.id = pc.regid)
LEFT OUTER JOIN notes n ON (r.id = n.regid)
WHERE (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
ORDER BY create_date;
EOS;
        //web_error_log($unpaidSQLM);
        $rp = dbSafeQuery($unpaidSQLP, 'ii', array($conid, $conid + 1));
        $rm = dbSafeQuery($unpaidSQLM, 'iiii', array($conid, $conid + 1, $conid, $conid + 1));
    } else if (is_numeric($name_search)) {
//
// this is perid, or transid
//
        $withClause = <<<EOS
WITH regbytid AS (
/* first reg ids for this create transaction as specified as a number */
SELECT r.id AS regid, create_trans as tid
FROM reg r
JOIN memLabel m ON (r.memId = m.id)
WHERE create_trans = ? AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
/* then add in reg ids for this attach transaction */
UNION SELECT regid, tid
FROM atcon_history h
JOIN reg r ON (r.id = h.regid)
JOIN memLabel m ON (r.memId = m.id)
WHERE tid = ? AND h.action = 'attach' AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
), regbyperid AS (
/* is the number a perinfo?  find the reg id's for this person matching the number */
SELECT r.id AS regid
FROM reg r
JOIN memLabel m ON (r.memId = m.id)
WHERE perid = ? AND (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
), regs AS (
/* now get the transactions for these regids */
SELECT rs.regid, create_trans as tid
FROM regbyperid rs
JOIN reg r ON (r.id = rs.regid)
UNION SELECT h.regid, tid
FROM regbyperid rs
JOIN atcon_history h ON (h.regid = rs.regid AND h.action = 'attach')
), maxtid AS (
/* now take the most recent transaction */
SELECT regid, MAX(tid) AS tid
FROM regs
GROUP BY regid
), regpt AS (
/* now get all the regids for these transactions */
SELECT IFNULL(h.regid, r.id) AS regid, m.tid
FROM maxtid m
LEFT OUTER JOIN atcon_history h ON (h.tid = m.tid AND h.action = 'attach')
LEFT OUTER JOiN reg r ON (r.create_trans = m.tid)
), regids AS (
/* and pull both sets together */
SELECT regid, tid FROM regbytid
UNION SELECT regid, tid FROM regpt
)
EOS;
        $searchSQLP = <<<EOS
$withClause
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
    p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    TRIM(REGEXP_REPLACE(concat(p.last_name, ', ', p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' ')) AS fullname,
    p.open_notes
FROM regids rs
JOIN reg r ON (rs.regid = r.id)
JOIN perinfo p ON (p.id = r.perid)
UNION 
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
    p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    TRIM(REGEXP_REPLACE(concat(p.last_name, ', ', p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' ')) AS fullname,
    p.open_notes
FROM perinfo p
WHERE id = ?
ORDER BY last_name, first_name;
EOS;
        //web_error_log($searchSQLP);
        $searchSQLM = <<<EOS
$withClause
, notes AS (
SELECT h.regid, GROUP_CONCAT(CONCAT(h.userid, '@', h.logdate, ': ', h.notes) SEPARATOR '\n') AS reg_notes, COUNT(*) AS reg_notes_count
FROM regids m
JOIN atcon_history h ON (m.regid = h.regid)
WHERE h.action = 'notes'
GROUP BY h.regid
), printcount AS (
SELECT h.regid, COUNT(*) printcount
FROM regids m
JOIN atcon_history h ON (m.regid = h.regid)
WHERE h.action = 'print'
GROUP BY h.regid
), attachcount AS (
SELECT h.regid, COUNT(*) attachcount
FROM regids m
JOIN atcon_history h ON (m.regid = h.regid)
WHERE h.action = 'attach'
GROUP BY h.regid
)
SELECT DISTINCT r1.perid, r1.id as regid, m.conid, r1.price, r1.paid, r1.create_date, IFNULL(r1.create_trans, -1) as tid, r1.memId, IFNULL(pc.printcount, 0) AS printcount,
                IFNULL(ac.attachcount, 0) AS attachcount, n.reg_notes, n.reg_notes_count, m.memCategory, m.memType, m.memAge, m.label, m.shortname, m.memGroup, rs.tid as rstid
FROM regids rs
JOIN reg r ON (rs.regid = r.id)
JOIN perinfo p ON (p.id = r.perid)
JOIN reg r1 ON (r1.perid = r.perid)
JOIN memLabel m ON (r1.memId = m.id)
LEFT OUTER JOIN printcount pc ON (r1.id = pc.regid)
LEFT OUTER JOIN attachcount ac ON (r1.id = ac.regid)
LEFT OUTER JOIN notes n ON (r1.id = n.regid)
WHERE (r1.conid = ? OR (r1.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
ORDER BY create_date;
EOS;
        //web_error_log($searchSQLM);
        $rp = dbSafeQuery($searchSQLP, 'iiiiiiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1, $name_search));
        $rm = dbSafeQuery($searchSQLM, 'iiiiiiiiiii', array($name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1, $name_search, $conid, $conid + 1, $conid, $conid + 1));
    } else {
//
// this is the string search portion as the field is alphanumeric
//
        // name match
        $limit = 50; // only return 50 people's memberships
        $name_search = '%' . preg_replace('/ +/', '%', $name_search) . '%';
        //web_error_log("match string: $name_search");
        $searchSQLP = <<<EOS
SELECT DISTINCT p.id AS perid, p.first_name, p.middle_name, p.last_name, p.suffix, p.badge_name,
    p.address as address_1, p.addr_2 as address_2, p.city, p.state, p.zip as postal_code, p.country, p.email_addr, p.phone,
    p.share_reg_ok, p.contact_ok, p.active, p.banned,
    TRIM(REGEXP_REPLACE(concat(p.last_name, ', ', p.first_name,' ', p.middle_name, ' ', p.suffix), '  *', ' ')) AS fullname,
    p.open_notes
FROM perinfo p
WHERE 
(LOWER(concat_ws(' ', first_name, middle_name, last_name)) LIKE ? OR LOWER(badge_name) LIKE ? OR LOWER(email_addr) LIKE ?)
ORDER BY last_name, first_name LIMIT $limit;
EOS;
        $searchSQLM = <<<EOS
WITH limitedp AS (
/* first get the perid's for this name search */
    SELECT DISTINCT p.id, p.first_name, p.last_name
    FROM perinfo p
    WHERE (LOWER(concat_ws(' ', first_name, middle_name, last_name)) LIKE ? OR LOWER(badge_name) LIKE ? OR LOWER(email_addr) LIKE ?)
    ORDER BY last_name, first_name LIMIT $limit
), regids AS (
SELECT r.id AS regid
FROM limitedp p
JOIN reg r ON (r.perid = p.id)
JOIN memList m ON (r.memId = m.id)
WHERE (r.conid = ? OR (r.conid = ? AND m.memCategory in ('yearahead', 'rollover')))
), regtid AS (
SELECT r.id as regid, create_trans as tid
FROM regids rs
JOIN reg r ON (r.id = rs.regid)
UNION
SELECT h.regid, h.tid
FROM regids rs
JOIN atcon_history h ON (h.regid = rs.regid)
), maxtids AS (
SELECT regid, MAX(tid) as tid
FROM regtid
GROUP BY regid
), notes AS (
SELECT h.regid, GROUP_CONCAT(CONCAT(h.userid, '@', h.logdate, ': ', h.notes) SEPARATOR '\n') AS reg_notes, COUNT(*) AS reg_notes_count
FROM maxtids m
JOIN atcon_history h ON (m.regid = h.regid)
WHERE h.action = 'notes'
GROUP BY h.regid
), printcount AS (
SELECT h.regid, COUNT(*) printcount
FROM maxtids m
JOIN atcon_history h ON (m.regid = h.regid)
WHERE h.action = 'print'
GROUP BY h.regid
)
SELECT DISTINCT r.perid, t.regid, m.conid, r.price, r.paid, r.create_date, t.tid, r.memId, IFNULL(pc.printcount, 0) AS printcount,
                n.reg_notes, n.reg_notes_count, m.memCategory, m.memType, m.memAge, m.label, m.shortname, m.memGroup             
FROM maxtids t
JOIN reg r ON (r.id = t.regid)
JOIN limitedp p ON (p.id = r.perid)
JOIN memLabel m ON (r.memId = m.id)
LEFT OUTER JOIN notes n ON (r.id = n.regid)
LEFT OUTER JOIN printcount pc ON (r.id = pc.regid)
ORDER BY create_date;
EOS;
        $rp = dbSafeQuery($searchSQLP, 'sss', array($name_search, $name_search, $name_search));
        $rm = dbSafeQuery($searchSQLM, 'sssii', array($name_search, $name_search, $name_search, $conid, $conid + 1));
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
    if ($num_rows >= $limit) {
        $response['warn'] = "$num_rows memberships found, limited to $limit, use different search criteria to refine your search.";
    } else {
        $response['message'] = "$num_rows memberships found";
    }
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

// updateCartElements:
// update cart contents into the database
//      create new perinfo records, update existing ones
//      create new reg records, update existing ones
//      create new transaction records if none exist for this reg record
//  inputs:
//      cart_perinfo: perinfo records in the cart
//      cart_membership: membership records in the cart
//      cart_perinfo_map: map of perid to rows in cart_perinfo
//  Outputs:
//      message/error/warn: appropriate diagnostics
//      updated_perinfo: array of old rownum and new perid's
//      updated_memberhip: array of old rownum, new id, new creat_trans id's
function updateCartElements($conid): void
{
    $user_id = $_POST['user_id'];
    if ($user_id != $_SESSION['user']) {
        ajaxError("Invalid credentials passed");
    }
    $cart_perinfo = $_POST['cart_perinfo'];
    if (sizeof($cart_perinfo) <= 0) {
        ajaxError('No members are in the cart');
        return;
    }

    $cart_perinfo_map = $_POST['cart_perinfo_map'];
    $cart_membership = $_POST['cart_membership'];
    if (sizeof($cart_membership) <= 0) {
        ajaxError('No memberships are in the cart');
        return;
    }

    $updated_perinfo = [];
    $updated_membership = [];
    $update_permap = [];
    $error_message = '';

    $per_ins = 0;
    $per_upd = 0;
    $reg_ins = 0;
    $reg_upd = 0;
    $reg_del = 0;
    $total_price = 0;
    $total_paid = 0;

    $insPerinfoSQL = <<<EOS
INSERT INTO perinfo(last_name,first_name,middle_name,suffix,email_addr,phone,badge_name,address,addr_2,city,state,zip,country,contact_ok,share_reg_ok,open_notes,banned,active,creation_date)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'N','Y',now());
EOS;
    $updPerinfoSQL = <<<EOS
UPDATE perinfo SET
    last_name=?,first_name=?,middle_name=?,suffix=?,email_addr=?,phone=?,badge_name=?,address=?,addr_2=?,city=?,state=?,zip=?,country=?,open_notes=?,banned='N',update_date=NOW(),active='Y',contact_ok=?,share_reg_ok=?
WHERE id = ?;
EOS;
    $insRegSQL = <<<EOS
INSERT INTO reg(conid,perid,price,paid,create_user,create_trans,memId,create_date)
VALUES (?,?,?,?,?,?,?,now());
EOS;
    $updRegSQL = <<<EOS
UPDATE reg SET price=?,paid=?,memId=?,change_date=now()
WHERE id = ?;
EOS;
    $delRegSQL = <<<EOS
DELETE FROM reg
WHERE id = ?;
EOS;
    $insHistory = <<<EOS
INSERT INTO atcon_history(userid, tid, regid, action, notes)
VALUES (?, ?, ?, ?, ?);
EOS;
    // insert/update all perinfo records,
    for ($row = 0; $row < sizeof($cart_perinfo); $row++) {
        $cartrow = $cart_perinfo[$row];
        if (array_key_exists('open_notes', $cartrow)) {
            $open_notes = $cartrow['open_notes'];
            if ($open_notes == '')
                $open_notes = null;
        } else
            $open_notes = null;
        if ($cartrow['perid'] <= 0) {
            // insert this row
            $paramarray = array(
                $cartrow['last_name'],$cartrow['first_name'],$cartrow['middle_name'],$cartrow['suffix'],$cartrow['email_addr'],$cartrow['phone'],$cartrow['badge_name'],
                $cartrow['address_1'],$cartrow['address_2'],$cartrow['city'],$cartrow['state'],$cartrow['postal_code'],$cartrow['country'],
                $cartrow['contact_ok'],$cartrow['share_reg_ok'],$open_notes
            );
            $typestr = 'ssssssssssssssss';
            $new_perid = dbSafeInsert($insPerinfoSQL, $typestr, $paramarray);
            if ($new_perid === false) {
                $error_message .= "Insert of person $row failed<BR/>";
            } else {
                $updated_perinfo[] = array('rownum' => $row, 'perid' => $new_perid);
                $cart_perinfo_map[$new_perid] = $row;
                $update_permap[$cartrow['perid']] = $new_perid;
                $cart_perinfo[$row]['perid'] = $new_perid;
                $per_ins++;
            }
        } else {
            // update the row
            $paramarray = array(
                $cartrow['last_name'],$cartrow['first_name'],$cartrow['middle_name'],$cartrow['suffix'],$cartrow['email_addr'],$cartrow['phone'],$cartrow['badge_name'],
                $cartrow['address_1'],$cartrow['address_2'],$cartrow['city'],$cartrow['state'],$cartrow['postal_code'],$cartrow['country'],$open_notes,
                $cartrow['contact_ok'],$cartrow['share_reg_ok'],
                $cartrow['perid']
            );
            $typestr = 'ssssssssssssssssi';
            $per_upd += dbSafeCmd($updPerinfoSQL, $typestr, $paramarray);
        }
    }

    // create the controlling transaction, in case the master perinfo needed insertion
    $master_perid = $cart_perinfo[0]['perid'];
    $notes = 'Pickup by: ' . trim($cart_perinfo[0]['first_name'] . ' ' . $cart_perinfo[0]['last_name']);
    $insTransactionSQL = <<<EOS
INSERT INTO transaction(conid,perid,userid,price,paid,type,create_date)
VALUES (?,?,?,?,?,'atcon',now());
EOS;
    // now insert the master transaction
    $paramarray = array($conid, $master_perid, $user_id, 0, 0);
    $typestr = 'iiiss';
    $master_transid = dbSafeInsert($insTransactionSQL, $typestr, $paramarray);
    if ($master_transid === false) {
        ajaxError('Unable to create master transaction');
        return;
    }
    // now insert/update all reg records and compute the transaction price and paid fields
    for ($row = 0; $row < sizeof($cart_membership); $row++) {
        $cartrow = $cart_membership[$row];
        if (!array_key_exists('todelete', $cartrow)) {
            $total_price += $cartrow['price'];
            $total_paid += $cartrow['paid'];
        }
        if (!array_key_exists('regid', $cartrow) || $cartrow['regid'] <= 0) {
            // insert the membership
            if ($cartrow['perid'] <= 0) {
                $cartrow['perid'] = $update_permap[$cartrow['perid']];
            }
            $paramarray = array($cartrow['conid'], $cartrow['perid'], $cartrow['price'], $cartrow['paid'], $user_id, $master_transid, $cartrow['memId']);
            $typestr = 'iissiii';
            $new_regid = dbSafeInsert($insRegSQL, $typestr, $paramarray);
            if ($new_regid === false) {
                $error_message .= "Insert of membership $row failed<BR/>";
            }
            $updated_membership[] = array('rownum' => $row, 'perid' => $cartrow['perid'], 'create_trans' => $master_perid, 'id' => $new_regid);
            $cartrow['regid'] = $new_regid;
            $cart_membership[$row]['regid'] = $new_regid;
            $reg_ins++;
        } else {
            if (array_key_exists('todelete', $cartrow)) {
                // delete membership
                $paramarray = array($cartrow['regid']);
                $typestr = 'i';
                $reg_del += dbSafeCmd($delRegSQL, $typestr, $paramarray);
            } else {
                // update membership
                $paramarray = array($cartrow['price'], $cartrow['paid'], $cartrow['memId'], $cartrow['regid']);
                $typestr = 'ssii';
                $reg_upd += dbSafeCmd($updRegSQL, $typestr, $paramarray);
            }
        }
        if (!array_key_exists('todelete', $cartrow)) {
            // Now add the attach record for this item
            $paramarray = array($user_id, $master_transid, $cartrow['regid'], 'attach', $notes);
            $typestr = 'iiiss';
            $new_history = dbSafeInsert($insHistory, $typestr, $paramarray);
            if ($new_history === false) {
                $error_message .= "Unable to attach membership " . $cartrow['regid'] . "<BR/>";
            }
            // now if there is a new note for this row, add it now
            if (array_key_exists('new_reg_note', $cartrow)) {
                $paramarray = array($user_id, $master_transid, $cartrow['regid'], 'notes', $cartrow['new_reg_note']);
                $typestr = 'iiiss';
                $new_history = dbSafeInsert($insHistory, $typestr, $paramarray);
                if ($new_history === false) {
                    $error_message .= 'Unable to add note to membership ' . $cartrow['regid'] . '<BR/>';
                }
            }
        }
    }
    // update the transaction associated with this reg
    $updTransactionSQL = <<<EOS
UPDATE transaction
SET price = ?, paid = ?
WHERE id = ?
EOS;
    $paramarray = array($total_price, $total_paid, $master_transid);
    $typestr = 'ssi';
    if (dbSafeCmd($updTransactionSQL, $typestr, $paramarray) === false) {
        $error_message .= "Update of master transaction failed";
    }

    if ($error_message != '') {
        $response['error'] = $error_message;
        ajaxSuccess($response);
    }
    $response['message'] = "$per_ins members inserted, $per_upd members updated, $reg_ins memberships inserted, $reg_upd memberships updated, $reg_del memberships deleted";
    $response['updated_perinfo'] = $updated_perinfo;
    $response['updated_membership'] = $updated_membership;
    $response['master_tid'] = $master_transid;
    ajaxSuccess($response);
}

// processPayment
//  cart_membership: memberships to have payment applied
//  new_payment: payment being added
//  pay_tid: current master transaction
function processPayment():void {
    $user_id = $_POST['user_id'];
    if ($user_id != $_SESSION['user']) {
        ajaxError('Invalid credentials passed');
    }

    $master_tid = $_POST['pay_tid'];
    if ($master_tid <= 0) {
        ajaxError('No current transaction in process');
    }

    $cart_membership = $_POST['cart_membership'];
    if (sizeof($cart_membership) <= 0) {
        ajaxError('No memberships are in the cart');
        return;
    }

    $new_payment = $_POST['new_payment'];
    if (!array_key_exists('amt', $new_payment) || $new_payment['amt'] <= 0) {
        ajaxError('invalid payment amount passed');
        return;
    }

    $amt = (float) $new_payment['amt'];
    // validate that the payment ammount is not too large
    $total_due = 0;
    foreach ($cart_membership as $cart_row) {
        $total_due += $cart_row['price'] - $cart_row['paid'];
    }

    if (round($amt,2) > round($total_due,2)) {
        ajaxError('invalid payment amount passed');
        return;
    }

    // now add the payment and process to which rows it applies
    $upd_rows = 0;
    $insPmtSQL = <<<EOS
INSERT INTO payments(transid, type,category, description, source, amount, time, cc_approval_code, cashier)
VALUES (?,?,'reg',?,'cashier',?,now(),?, ?);
EOS;
    $typestr = 'issssi';
    if ($new_payment['type'] == 'check')
        $desc = 'Check No: ' . $new_payment['checkno'] . '; ';
    else
        $desc = '';
    $desc .= $new_payment['desc'];
    $paramarray = array($master_tid, $new_payment['type'], $desc, $new_payment['amt'], $new_payment['ccauth'], $user_id);
    $new_pid = dbSafeInsert($insPmtSQL, $typestr, $paramarray);
    if ($new_pid === false) {
        ajaxError("Error adding payment to database");
        return;
    }

    $new_payment['id'] = $new_pid;
    $response['prow'] = $new_payment;
    $response['message'] = "1 payment added";
$updPaymentSQL = <<<EOS
UPDATE reg
SET paid = ?
WHERE id = ?;
EOS;
$typestr = 'si';
    foreach ($cart_membership as $cart_row) {
        $unpaid = $cart_row['price'] - $cart_row['paid'];
        if ($unpaid > 0) {
            $amt_paid = min($amt, $unpaid);
            $cart_row['paid'] += $amt_paid;
            $cart_membership[$cart_row['index']] = $cart_row;
            $amt -= $amt_paid;
            $upd_rows += dbSafeCmd($updPaymentSQL, $typestr, array($cart_row['paid'], $cart_row['regid']));
            if ($amt <= 0)
                break;
        }
    }

    $response['message'] .= ", $upd_rows memberships updated.";
    $response['cart_membership'] = $cart_membership;
    ajaxSuccess($response);
}

// updatePrintcount
//      passed array of regid and print count
//      updates database
function updatePrintcount(): void {
    if (array_key_exists('regs', $_POST)) {
        $regs = $_POST['regs'];
        $user_id = $_POST['user_id'];
        if ($user_id != $_SESSION['user']) {
            ajaxError('Invalid credentials passed');
        }
        $tid = $_POST['tid'];
        $insertSQL = <<<EOS
INSERT INTO atcon_history(userid, tid, regid, action)
VALUES (?,?,?,'print');
EOS;
        $typestr = 'iii';

        foreach ($regs as $reg) {
            $paramarray = array($user_id, $tid, $reg['regid']);
            dbSafeInsert($insertSQL, $typestr,$paramarray);
        }
    }
}

// updatePerinfoNote:
// update the open_notes for a specific perid
//  inputs:
//      notes: new note string
//      perid: which person to update
//      cart_perinfo_map: map of perid to rows in cart_perinfo
//  Outputs:
//      message/error/warn: appropriate diagnostics
function updatePerinfoNote(): void
{
    $user_id = $_POST['user_id'];
    if ($user_id != $_SESSION['user']) {
        ajaxError('Invalid credentials passed');
    }
    $notes = $_POST['notes'];
    if ($notes === '')
        $notes = null;
    $perid = $_POST['perid'];

    if ($perid <= 0) {
        ajaxError('Invalid person to update');
        return;
    }
    $response = [];
    
    $updSQL = <<<EOS
UPDATE perinfo
SET open_notes = ?
WHERE id = ?;
EOS;
    $num_upd = dbSafeCmd($updSQL, 'si', array($notes, $perid));
    if ($num_upd === false) {
        $response['error'] = "Unable to update notes";
    } else {
        $response['message'] ="Notes Updated";
    }
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
    case 'updateCartElements':
        updateCartElements($conid);
        break;
    case 'processPayment':
        processPayment();
        break;
    case 'updatePrintcount':
        updatePrintcount();
        break;
    case 'updatePerinfoNote':
        updatePerinfoNote();
        break;
    default:
        $message_error = 'Internal error.';
        RenderErrorAjax($message_error);
        exit();
}
