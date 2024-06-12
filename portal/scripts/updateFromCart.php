<?php
require_once('../lib/base.php');
require_once('../../lib/log.php');

// use common global Ajax return functions
global $returnAjaxErrors, $return500errors;
$returnAjaxErrors = true;
$return500errors = true;

$response = array('post' => $_POST, 'get' => $_GET);

$con = get_con();
$conid=$con['id'];
$conf = get_conf('con');
$portal_conf = get_conf('portal');

$response['conid'] = $conid;

if (!(array_key_exists('person', $_POST) && array_key_exists('cart', $_POST) && array_key_exists('action', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(array_key_exists('id', $_SESSION) && array_key_exists('idType', $_SESSION))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$loginId = $_SESSION['id'];
$loginType = $_SESSION['idType'];

$action = $_POST['action'];
try {
    $person = json_decode($_POST['person'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['status'] = 'error';
    $response['message'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}
try {
    $cart = json_decode($_POST['cart'], true, 512, JSON_THROW_ON_ERROR);
}
catch (Exception $e) {
    $msg = 'Caught exception on json_decode: ' . $e->getMessage() . PHP_EOL . 'JSON error: ' . json_last_error_msg() . PHP_EOL;
    $response['status'] = 'error';
    $response['message'] = $msg;
    error_log($msg);
    ajaxSuccess($response);
    exit();
}

if (array_key_exists('personType', $person)) {
    $personType = $person['personType'];
    $personId = $person['id'];
} else {
    $personId = -1;
    $personType = 'n';
}
$newPerid = null;

// first update the person so we can build a transaction and memberships
if ($personId < 0) {
    // insert into newPerson
    $iq = <<<EOS
insert into newperson (last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip,
                       country, share_reg_ok, contact_ok, managedBy, managedByNew, updatedBy, lastVerified)
values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW());
EOS;
    $typeStr = 'ssssssssssssssssiii';
    $valArray = array(
        trim($person['last_name']),
        trim($person['middle_name']),
        trim($person['first_name']),
        trim($person['suffix']),
        trim($person['email_addr']),
        trim($person['phone']),
        trim($person['badge_name']),
        trim($person['legalName']),
        trim($person['address']),
        trim($person['addr_2']),
        trim($person['city']),
        trim($person['state']),
        trim($person['zip']),
        trim($person['country']),
        $person['share_reg_ok'],
        $person['contact_ok'],
        $loginType == 'p' ? $loginId : null,
        $loginType == 'n' ? $loginId : null,
        $loginId
    );
    $personId = dbSafeInsert($iq, $typeStr, $valArray);
    if ($personId === false || $personId < 0) {
        $response['status'] = 'error';
        $response['message'] = 'Error inserting the new person into the database. Seek assistance';
        ajaxSuccess($response);
    }
    $response['newPersonId'] = $personId;
    $response['message'] = "New person with Temporary ID $personId added";
    $newPerId = $personId;
} else {
    // update the record
    if ($personType == 'p') {
        $updPersonQ = <<<EOS
UPDATE perinfo
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, email_addr = ?, phone = ?, badge_name = ?, legalName = ?, address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, 
share_reg_ok = ?, contact_ok = ?, updatedBy = ?, lastVerified = NOW()
WHERE id = ?;
EOS;
    } else {
        $updPersonQ = <<<EOS
UPDATE newperson
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, email_addr = ?, phone = ?, badge_name = ?, legalName = ?, address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, 
share_reg_ok = ?, contact_ok = ?, updatedBy = ?, lastVerified = NOW()
WHERE id = ?;
EOS;
    }
    $fields = ['last_name', 'middle_name', 'first_name', 'suffix', 'email_addr', 'phone', 'badge_name', 'legalName', 'address', 'addr_2', 'city', 'state', 'zip', 'country'];
    foreach ($fields as $field) {
        if ($person[$field] == null)
            $person[$field] = '';
    }
    $value_arr = array(
        trim($person['last_name']),
        trim($person['middle_name']),
        trim($person['first_name']),
        trim($person['suffix']),
        trim($person['email_addr']),
        trim($person['phone']),
        trim($person['badge_name']),
        trim($person['legalName']),
        trim($person['address']),
        trim($person['addr_2']),
        trim($person['city']),
        trim($person['state']),
        trim($person['zip']),
        trim($person['country']),
        $person['share_reg_ok'],
        $person['contact_ok'],
        $loginId,
        $personId
    );

    $rows_upd = dbSafeCmd($updPersonQ, 'ssssssssssssssssii', $value_arr);
    if ($rows_upd === false) {
        ajaxSuccess(array('status' => 'error', 'message' => 'Error updating person'));
        exit();
    }
    $response['person_rows_upd'] = $rows_upd;
    $response['status'] = 'success';
    $response['message'] = $rows_upd == 0 ? "No changes" : "$rows_upd person updated";
}

$num_del = 0;
$num_ins = 0;
// now for the cart
if (sizeof($cart) > 0) {
    foreach ($cart as $cartRow) {
        if (array_key_exists('toDelete', $cartRow) && $cartRow['toDelete'] == true && $cartRow['status'] == 'unpaid') {
            // first verify it's qualified for deletion
            $cQ = <<<EOS
SELECT id, perid, newperid, status, price, paid, couponDiscount
FROM reg
WHERE id = ?;
EOS;
            $cR = dbSafeQuery($cQ, 'i', array($cartRow['id']));
            if ($cR === false || $cR->num_rows != 1) {
                $response['message'] .= "<br/>Cannot find membership " . $cartRow['id'] . " to delete, continuing with the remaining transactions.";
                continue;
            }
            $item = $cR->fetch_assoc();
            $cR->free();
            if ($item['perid'] != $personId && $item['newperid'] != $personId) {
                $response['message'] .= '<br/>Membership ' . $cartRow['id'] . ' does not belong to you, continuing with the remaining transactions.';
                continue;
            }
            if ($item['price'] == 0 || ($item['couponDiscount'] + $item['paid']) == $item['price']) {
                $response['message'] .= '<br/>Membership ' . $cartRow['id'] . ' is not eligible for deletion, continuing with the remaining transactions.';
                continue;
            }
            $dQ = <<<EOS
DELETE FROM reg
WHERE id = ?;
EOS;
            $num_del += dbSafeCmd($dQ, 'i', array($cartRow['id']));
            continue;
        }
        if ($cartRow['status'] == 'in-cart') {
            // insert the new reg record into the cart
            $iQ = <<<EOS
INSERT INTO reg(conid, perid, newperid, price, create_user, memId, status)
values (?, ?, ?, ?, ?, ?, ?);
EOS;
            $typeStr = 'iiidiis';
            $valArray = array(
                $conid,
                $personType == 'p' ? $personId : null,
                $personType == 'n' ? $personId : null,
                $cartRow['price'],
                $loginId,
                $cartRow['memId'],
                'unpaid'               
            );
            $new_cartid = dbSafeInsert($iQ, $typeStr, $valArray);
            if ($new_cartid === false || $new_cartid < 0) {
                $response['message'] .= "<br/>Error adding membership " . $cartRow['id'] . " contining with the remaining transactions.";
            } else {
                $num_ins++;
            }
        }
    }
    $response['message'] .= "<br/>$num_del Memberships Deleted, $num_ins Memberships Inserted";
}
$response['status'] = 'success';

ajaxSuccess($response);
