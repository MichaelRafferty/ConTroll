<?php
// library AJAX Processor: pos_updateCartElements.php
// ConTroll Registration System
// Author: Syd Weinstein
// Store the cart into the system using add/update/delete and create appropriate transaction records
// Used both by mail in registration (controll/registration.php) and atcon (atcon/regpos.php)

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
$error_message = '';

$per_ins = 0;
$per_upd = 0;
$reg_ins = 0;
$reg_upd = 0;
$reg_del = 0;
$total_price = 0;
$total_paid = 0;

// loop over the perinfo array and add/update/delete the perinfo entries, and then the memberships under those perinfo entries

$insPerinfoSQL = <<<EOS
INSERT INTO perinfo(last_name,first_name,middle_name,suffix,legalName,pronouns,email_addr,phone,badge_name,address,addr_2,city,state,zip,country,
                    open_notes,banned,active,contact_ok,creation_date,updatedBy)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'N','Y','Y',now(),?);
EOS;
$insPDt = 'ssssssssssssssssi';

$updPerinfoSQL = <<<EOS
UPDATE perinfo SET
    last_name=?,first_name=?,middle_name=?,suffix=?,legalName=?,pronouns=?,email_addr=?,phone=?,badge_name=?,address=?,addr_2=?,city=?,state=?,zip=?,country=?,
    open_notes=?,banned='N',update_date=NOW(),active='Y',updatedBy=?
WHERE id = ?;
EOS;
$updPDt = 'ssssssssssssssssii';

$insRegSQL = <<<EOS
INSERT INTO reg(conid,perid,price,couponDiscount,paid,create_user,create_trans,memId,coupon,create_date,status)
VALUES (?,?,?,?,?,?,?,?,?,now(),?);
EOS;
$insRDt = 'iidddiiiis';

$updRegSQL = <<<EOS
UPDATE reg SET price=?,couponDiscount=?,paid=?, memId=?,coupon=?,updatedBy=?,change_date=now()
WHERE id = ?;
EOS;
$updRDt = 'dddiiii';

$delRegSQL = <<<EOS
DELETE FROM reg
WHERE id = ?;
EOS;
$delRDt = 'i';

$insHistory = <<<EOS
INSERT INTO regActions(userid, tid, regid, action, notes)
VALUES (?, ?, ?, ?, ?);
EOS;
$insHDt = 'iiiss';

// create the controlling transaction, in case the master perinfo needed insertion
$master_perid = $cart_perinfo[0]['perid'];
$tran_type = 'regctl-reg/' . $user_perid;
$insTransactionSQL = <<<EOS
INSERT INTO transaction(conid,perid,userid,price,paid,type,create_date)
VALUES (?,?,?,?,?,?,now());
EOS;
// now insert the master transaction
$paramarray = array($conid, $master_perid, $user_perid, 0, 0, $tran_type);
$typestr = 'iiidds';
$master_transid = dbSafeInsert($insTransactionSQL, $typestr, $paramarray);
if ($master_transid === false) {
    ajaxError('Unable to create master transaction');
    return;
}

// loop over all perinfo records
for ($row = 0; $row < sizeof($cart_perinfo); $row++) {
    $cartrow = $cart_perinfo[$row];
    $cartrow['rowpos'] = $row;
    $cart_perinfo[$row]['rowpos'] = $row;
    if (array_key_exists('open_notes', $cartrow)) {
        $open_notes = $cartrow['open_notes'];
        if ($open_notes == '')
            $open_notes = null;
    } else
        $open_notes = null;

    if ($cartrow['legalName'] == '') {
        $cartrow['legalName'] = trim($cartrow['first_name']  . ($cartrow['middle_name'] == '' ? ' ' : ' ' . $cartrow['middle_name'] . ' ' ) .
            $cartrow['last_name'] . ' ' . $cartrow['suffix']);
    }

    // remove l-r from phone
    $cartrow['phone'] = trim(removeLROveride($cartrow['phone']));

    if ($cartrow['perid'] <= 0) {
        // insert this row
        $paramarray = array(
            $cartrow['last_name'],$cartrow['first_name'],$cartrow['middle_name'],$cartrow['suffix'],$cartrow['legalName'],$cartrow['pronouns'],
            $cartrow['email_addr'],$cartrow['phone'],$cartrow['badge_name'],
            $cartrow['address_1'],$cartrow['address_2'],$cartrow['city'],$cartrow['state'],$cartrow['postal_code'],$cartrow['country'],
            $open_notes,$user_perid
        );

        $new_perid = dbSafeInsert($insPerinfoSQL, $insPDt, $paramarray);
        if ($new_perid === false) {
            $error_message .= "Insert of person $row failed<BR/>";
        } else {
            $cart_perinfo[$row]['perid'] = $new_perid;
            $cartrow['perid'] = $new_perid;
            $per_ins++;
        }
    } else {
        // update the row
        $paramarray = array(
            $cartrow['last_name'],$cartrow['first_name'],$cartrow['middle_name'],$cartrow['suffix'],$cartrow['legalName'],$cartrow['pronouns'],
            $cartrow['email_addr'],$cartrow['phone'],$cartrow['badge_name'],
            $cartrow['address_1'],$cartrow['address_2'],$cartrow['city'],$cartrow['state'],$cartrow['postal_code'],$cartrow['country'],$open_notes,
            $user_perid, $cartrow['perid']
        );
        $per_upd += dbSafeCmd($updPerinfoSQL, $updPDt, $paramarray);
    }

    // Now process the memberships for this person
    $memberships = $cartrow['memberships'];

    for ($mrow = 0; $mrow < sizeof($memberships); $mrow++) {
        $mbr = $memberships[$mrow];
        if (!array_key_exists('coupon', $mbr) || $mbr['coupon'] == '')
            $mbr['coupon'] = null;
        if (!array_key_exists('couponDiscount', $mbr))
            $mbr['couponDiscount'] = 0;

        // if this row persists
        if (!array_key_exists('toDelete', $mbr)) {
            if ($mbr['price'] == '')
                $mbr['price'] = 0;
            $total_price += $mbr['price'];

            if ($mbr['paid'] == '')
                $mbr['paid'] = 0;
            $total_paid += $mbr['paid'];
        }

        if (!array_key_exists('regid', $mbr) || $mbr['regid'] <= 0) {
            // insert the membership, as it's new
            if ($mbr['perid'] <= 0) {
                $mbr['perid'] = $cartrow['perid'];
            }
            $paramarray = array ($mbr['conid'], $mbr['perid'], $mbr['price'],
                                 $mbr['price'] > $mbr['paid'] ? 'unpaid' : 'paid',
                                 $mbr['paid'], $user_perid, $master_transid, $mbr['memId']);
            $new_regid = dbSafeInsert($insRegSQL, $insRDt, $paramarray);
            if ($new_regid === false) {
                $error_message .= "Insert of membership $row failed<BR/>";
            }
            $mbr['regid'] = $new_regid;
            $cart_perinfo[$row]['memberships'][$mrow]['regid'] = $new_regid;
            $reg_ins++;
        }
        else {
            if (array_key_exists('toDelete', $mbr)) {
                // delete membership
                $paramarray = array ($mbr['regid']);
                $reg_del += dbSafeCmd($delRegSQL, $delRDt, $paramarray);
            }
            else {
                // update membership
                $paramarray = array ($mbr['price'], $mbr['couponDiscount'], $mbr['paid'], $mbr['memId'], $mbr['coupon'],
                                     $user_perid, $mbr['regid']);
                $reg_upd += dbSafeCmd($updRegSQL, $updRDt, $paramarray);
            }
        }
        if (!array_key_exists('toDelete', $mbr)) {
            // now if there is a new note for this row, add it now
            if (array_key_exists('new_reg_note', $mbr)) {
                $paramarray = array ($user_perid, $master_transid, $mbr['regid'], 'notes', $mbr['new_reg_note']);
                $new_history = dbSafeInsert($insHistory, $insHDt, $paramarray);
                if ($new_history === false) {
                    $error_message .= 'Unable to add note to membership ' . $mbr['regid'] . '<BR/>';
                }
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
$typestr = 'ddi';
if (dbSafeCmd($updTransactionSQL, $typestr, $paramarray) === false) {
    $error_message .= "Update of master transaction failed";
}

if ($error_message != '') {
    $response['error'] = $error_message;
    ajaxSuccess($response);
}
$response['message'] = "$per_ins members inserted, $per_upd members updated, $reg_ins memberships inserted, $reg_upd memberships updated, $reg_del memberships deleted";
$response['updated_perinfo'] = $cart_perinfo;
$response['master_tid'] = $master_transid;
ajaxSuccess($response);
