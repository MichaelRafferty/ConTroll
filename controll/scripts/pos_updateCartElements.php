<?php
// library AJAX Processor: reg_updateCartElements.php
// Balticon Registration System
// Author: Syd Weinstein
// Store the cart into the system using add/update/delete and create appropriate transaction records

require_once '../lib/base.php';

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
$conid = $con['id'];
$ajax_request_action = '';
if ($_POST && $_POST['ajax_request_action']) {
    $ajax_request_action = $_POST['ajax_request_action'];
}
if ($ajax_request_action != 'updateCartElements') {
    RenderErrorAjax('Invalid calling sequence.');
    exit();
}

$user_id = $_POST['user_id'];
if ($user_id != $_SESSION['user_id']) {
    ajaxError("Invalid credentials passed");
    return;
}
$user_perid = $_SESSION['user_perid'];
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
INSERT INTO perinfo(last_name,first_name,middle_name,suffix,legalName,email_addr,phone,badge_name,address,addr_2,city,state,zip,country,contact_ok,share_reg_ok,open_notes,banned,active,creation_date,updatedBy)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'N','Y',now(),?);
EOS;
$existingQ = <<<EOS
SELECT last_name, first_name, middle_name, suffix, legalName, email_addr, phone, badge_name, address, addr_2, city, state, zip, country, open_notes, contact_ok, share_reg_ok, change_notes
FROM perinfo
WHERE id = ?;
EOS;
$updPerinfoSQL = <<<EOS
UPDATE perinfo SET
    last_name=?,first_name=?,middle_name=?,suffix=?,legalName=?,email_addr=?,phone=?,badge_name=?,address=?,addr_2=?,city=?,state=?,zip=?,country=?,
    open_notes=?,banned='N',update_date=NOW(),active='Y',contact_ok=?,share_reg_ok=?,change_notes=?,updatedBy=?
WHERE id = ?;
EOS;
$insRegSQL = <<<EOS
INSERT INTO reg(conid,perid,price,paid,status,create_user,create_trans,memId,create_date)
VALUES (?,?,?,?,?,?,?,?,now());
EOS;
$updRegSQL = <<<EOS
UPDATE reg SET price=?,paid=?,status = ?, memId=?,change_date=now()
WHERE id = ?;
EOS;
$delRegSQL = <<<EOS
DELETE FROM reg
WHERE id = ?;
EOS;
$insHistory = <<<EOS
INSERT INTO regActions(userid, tid, regid, action, notes)
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
    $legalName = $cartrow['legalName'];

    if ($legalName == '') {
        $legalName = trim($cartrow['first_name']  . ($cartrow['middle_name'] == '' ? ' ' : ' ' . $cartrow['middle_name'] . ' ' ) .
            $cartrow['last_name'] . ' ' . $cartrow['suffix']);
    }

    // remove l-r from phone
    $phone = trim($cartrow['phone']);
    if ($phone != null && $phone != '') {
        $phone = preg_replace('/' . mb_chr(0x202d) . '/', '',  $phone);
        $cartrow['phone'] = $phone;
    }

    if ($cartrow['perid'] <= 0) {
        // insert this row
        $paramarray = array(
            $cartrow['last_name'],$cartrow['first_name'],$cartrow['middle_name'],$cartrow['suffix'],$legalName,$cartrow['email_addr'],$cartrow['phone'],$cartrow['badge_name'],
            $cartrow['address_1'],$cartrow['address_2'],$cartrow['city'],$cartrow['state'],$cartrow['postal_code'],$cartrow['country'],
            $cartrow['contact_ok'],$cartrow['share_reg_ok'],$open_notes,$_SESSION['user_id']
        );
        $typestr = 'sssssssssssssssssi';
        $new_perid = dbSafeInsert($insPerinfoSQL, $typestr, $paramarray);
        if ($new_perid === false) {
            $error_message .= "Insert of person $row failed<BR/>";
        } else {
            $updated_perinfo[] = array('rowpos' => $row, 'perid' => $new_perid);
            $cart_perinfo_map[$new_perid] = $row;
            $update_permap[$cartrow['perid']] = $new_perid;
            $cart_perinfo[$row]['perid'] = $new_perid;
            $per_ins++;
        }
    } else {
        // update the row
        // first build the change log
        $existingR = dbSafeQuery($existingQ, 'i', array($cartrow['perid']));
        if ($existingR === false || $existingR->num_rows != 1) {
            $response['status'] = 'error';
            $response['error'] = "Unable to update cart row: " . $cartrow['perid'] . '/' . $cartrow['last_name'] . ', ' . $cartrow['first_name'];
            ajaxSuccess($response);
            return;
        }
        $existingP = $existingR->fetch_assoc();
        $new_change_notes = $existingP['change_notes'];
        if ($new_change_notes === null)
            $new_change_notes = '';
        // build additional items on new change notes
        $changes = '';
        foreach ($existingP as $field => $value) {
            if ($field == 'perid' || $field == 'change_notes')
                continue;

            if ($field == 'zip')
                $cartfield = 'postal_code';
            else if ($field == 'address')
                $cartfield = 'address_1';
            else if ($field == 'addr_2')
                $cartfield = 'address_2';
            else
                $cartfield = $field;

            if ($value != $cartrow[$cartfield]) {
                $changes .= $field . ' updated "' . $value . '" => "' . $cartrow[$cartfield] . '"' . "\n";
            }
        }
        if ($changes != '')
            $new_change_notes = "\ncontroll/registration Updated " . date(DATE_RFC2822) . " by $user_perid:\n$changes\n" . $new_change_notes;

        $paramarray = array(
            $cartrow['last_name'],$cartrow['first_name'],$cartrow['middle_name'],$cartrow['suffix'],$legalName,$cartrow['email_addr'],$cartrow['phone'],$cartrow['badge_name'],
            $cartrow['address_1'],$cartrow['address_2'],$cartrow['city'],$cartrow['state'],$cartrow['postal_code'],$cartrow['country'],$open_notes,
            $cartrow['contact_ok'],$cartrow['share_reg_ok'],$new_change_notes,$user_id,
            $cartrow['perid']
        );
        $typestr = 'ssssssssssssssssssii';
        $per_upd += dbSafeCmd($updPerinfoSQL, $typestr, $paramarray);
    }
}

// create the controlling transaction, in case the master perinfo needed insertion
$master_perid = $cart_perinfo[0]['perid'];
$tran_type = 'regctl-reg/' . $user_perid;
$insTransactionSQL = <<<EOS
INSERT INTO transaction(conid,perid,userid,price,paid,type,create_date)
VALUES (?,?,?,?,?,?,now());
EOS;
// now insert the master transaction
$paramarray = array($conid, $master_perid, $user_perid, 0, 0, $tran_type);
$typestr = 'iiisss';
$master_transid = dbSafeInsert($insTransactionSQL, $typestr, $paramarray);
if ($master_transid === false) {
    ajaxError('Unable to create master transaction');
    return;
}
// now insert/update all reg records and compute the transaction price and paid fields
for ($row = 0; $row < sizeof($cart_membership); $row++) {
    $cartrow = $cart_membership[$row];
    if (!array_key_exists('todelete', $cartrow)) {
        if ($cartrow['price'] == '')
            $cartrow['price'] = 0;
        $total_price += $cartrow['price'];

        if ($cartrow['paid'] == '')
            $cartrow['paid'] = 0;
        $total_paid += $cartrow['paid'];
    }
    if (!array_key_exists('regid', $cartrow) || $cartrow['regid'] <= 0) {
        // insert the membership
        if ($cartrow['perid'] <= 0) {
            $cartrow['perid'] = $update_permap[$cartrow['perid']];
        }
        $paramarray = array($cartrow['conid'], $cartrow['perid'], $cartrow['price'],
                            $cartrow['price'] > $cartrow['paid'] ? 'unpaid' : 'paid',
                            $cartrow['paid'], $user_perid, $master_transid, $cartrow['memId']);
        $typestr = 'iiddsiii';
        $new_regid = dbSafeInsert($insRegSQL, $typestr, $paramarray);
        if ($new_regid === false) {
            $error_message .= "Insert of membership $row failed<BR/>";
        }
        $updated_membership[] = array('rowpos' => $row, 'perid' => $cartrow['perid'], 'create_trans' => $master_perid, 'id' => $new_regid);
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
            $paramarray = array($cartrow['price'], $cartrow['paid'],
                                $cartrow['price'] > $cartrow['paid'] ? 'unpaid' : 'paid',
                                $cartrow['memId'], $cartrow['regid']);
            $typestr = 'ddsii';
            $reg_upd += dbSafeCmd($updRegSQL, $typestr, $paramarray);
        }
    }
    if (!array_key_exists('todelete', $cartrow)) {
        // now if there is a new note for this row, add it now
        if (array_key_exists('new_reg_note', $cartrow)) {
            $paramarray = array($user_perid, $master_transid, $cartrow['regid'], 'notes', $cartrow['new_reg_note']);
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
