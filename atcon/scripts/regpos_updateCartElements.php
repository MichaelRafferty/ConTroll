<?php

// library AJAX Processor: regpos_updateCartElements.php
// Balticon Registration System
// Author: Syd Weinstein
// Store the cart into the system using add/update/delete and create appropriate transaction records

require_once('../lib/base.php');

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

if (!(check_atcon('cashier', $conid) || check_atcon('data_entry', $conid))) {
    $message_error = 'No permission.';
    RenderErrorAjax($message_error);
    exit();
}

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
