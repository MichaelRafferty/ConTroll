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
$log = get_conf('log');
$portal_conf = get_conf('portal');

$response['conid'] = $conid;

if (!(array_key_exists('person', $_POST) && array_key_exists('cart', $_POST) && array_key_exists('action', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');
$transId = getSessionVar('transid');
$voidTransId = false; // void the transaction if a free membership was marked paid in this item

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
try {
    $oldInterests = json_decode($_POST['oldInterests'], true, 512, JSON_THROW_ON_ERROR);
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
    $newInterests = json_decode($_POST['newInterests'], true, 512, JSON_THROW_ON_ERROR);
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

logInit($log['reg']);

// first update the person so we can build a transaction and memberships
$matchId = null;
if ($personId < 0) {
    if ($transId == null) {
        $transId = getNewTransaction($conid, $loginType == 'p' ? $loginId : null, $loginType == 'n' ? $loginId : null);
    }

    // the exact match check for this new person will prevent adding newperson for existing people
    // see if there is an exact match
    $exactMsql = <<<EOF
SELECT id
FROM perinfo p
WHERE
	REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.first_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.middle_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.last_name, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.suffix, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.email_addr, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.phone, ''))), '  *', ' ')
    AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.badge_name, ''))), '  *', ' ')
    AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.legalName, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.address, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.addr_2, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.city, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.state, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.zip, ''))), '  *', ' ')
	AND REGEXP_REPLACE(TRIM(LOWER(IFNULL(?,''))), '  *', ' ') =
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.country, ''))), '  *', ' ');
EOF;
    $value_arr = array(
        trim($person['first_name']),
        trim($person['middle_name']),
        trim($person['last_name']),
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
    );
    $res = dbSafeQuery($exactMsql, 'ssssssssssssss', $value_arr);
    if ($res !== false) {
        if ($res->num_rows > 0) {
            $match = $res->fetch_assoc();
            $matchId = $match['id'];
            $personType = 'p';
            $personId = $matchId;

            // now update the perid to set the managed by flag
            $updPQ = <<<EOS
UPDATE perinfo
SET managedBy = ?, managedReason = 'Exact Match'
WHERE id = ?;
EOS;
            $upd = dbSafeCmd($updPQ, 'ii', array($loginId, $matchId));
            logWrite(array('con'=>$con['name'], 'trans'=>$transId, 'action' => 'Exact Match for management', 'person' => $person, 'managedBy' => $loginId));
        }
    }

    if ($matchId == null) {
        // no match found
        // insert into newPerson
        $iQ = <<<EOS
insert into newperson (transid, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, address, addr_2, city, state, zip,
                       country, share_reg_ok, contact_ok, managedBy, managedByNew, managedReason, updatedBy, lastVerified)
values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'creation',?,NOW());
EOS;
        $typeStr = 'issssssssssssssssiii';
        $valArray = array(
            $transId,
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
        $personId = dbSafeInsert($iQ, $typeStr, $valArray);
        if ($personId === false || $personId < 0) {
            $response['status'] = 'error';
            $response['message'] = 'Error inserting the new person into the database. Seek assistance';
            ajaxSuccess($response);
        }
        $response['newPersonId'] = $personId;
        $response['message'] = "New person with Temporary ID $personId added";
        $newPerid = $personId;
        $personType = 'n';
        logWrite(array('con'=>$con['name'], 'trans'=>$transId, 'action' => 'New managed person created', 'person' => $person, 'managedBy' => $loginId));
    }
}

if ($newPerid == null) {
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
$updateTransPrice = false;
if (sizeof($cart) > 0) {
    foreach ($cart as $cartRow) {
        if (array_key_exists('toDelete', $cartRow) && $cartRow['toDelete'] == true && $cartRow['status'] == 'unpaid') {
            // first verify it's qualified for deletion
            $cQ = <<<EOS
SELECT id, perid, newperid, status, price, paid, couponDiscount, create_trans
FROM reg
WHERE id = ?
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
UPDATE reg
SET status = 'cancelled'
WHERE id = ?;
EOS;
            $num_del += dbSafeCmd($dQ, 'i', array($cartRow['id']));
            if ($item['create_trans'] == $transId) {
                $updateTransPrice = true;
            }

            continue;
        }
        if ($cartRow['status'] == 'in-cart') {
            if ($transId == null) {
                $tranId = $transId = getNewTransaction($conid, $loginType == 'p' ? $loginId : null, $loginType == 'n' ? $loginId : null);
            }
            // insert the new reg record into the cart
            $iQ = <<<EOS
INSERT INTO reg(conid, perid, newperid, create_trans, complete_trans, price, create_user, memId, status)
values (?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
            $typeStr = 'iiiiidiis';
            $valArray = array(
                $cartRow['conid'],
                $personType == 'p' ? $personId : null,
                $personType == 'n' ? $personId : null,
                $transId,
                $cartRow['price'] > 0 ? null : $transId,
                $cartRow['price'],
                $loginId,
                $cartRow['memId'],
                $cartRow['price'] == 0 ? 'paid' : 'unpaid'
            );
            $new_cartid = dbSafeInsert($iQ, $typeStr, $valArray);
            if ($new_cartid === false || $new_cartid < 0) {
                $response['message'] .= "<br/>Error adding membership " . $cartRow['id'] . " contining with the remaining transactions.";
            } else {
                $num_ins++;
                $updateTransPrice = true;
                if ($cartRow['price'] == 0)
                    $voidTransId = true;
            }
        }
    }
    if ($updateTransPrice) {
        // we changed a reg for this transaction, recompute the price portion of the record
        $uQ = <<<EOS
UPDATE transaction t
JOIN (
    SELECT sum(price) AS total
    FROM reg
    WHERE create_trans = ? AND status IN ('unpaid', 'paid', 'plan', 'upgraded')
    ) s
SET price = s.total
WHERE id = ?;
EOS;
        dbSafeCmd($uQ, 'ii', array($transId, $transId));
    }
    $response['message'] .= "<br/>$num_del Memberships Deleted, $num_ins Memberships Inserted";
    logWrite(array('con'=>$con['name'], 'trans'=>$transId, 'action' => 'cart updated', 'cart' => $cart, 'updatedBy' => $loginId));
}

$newInterests = json_decode($_POST['newInterests'], true);
$existingInterests = json_decode($_POST['oldInterests'], true);

// find the differences in the interests to update the record

if ($personType == 'p') {
    $pfield = 'perid';
} else if ($personType == 'n') {
    $pfield = 'newperid';
}
$updInterest = <<<EOS
UPDATE memberInterests
SET interested = ?, updateBy = ?
WHERE id = ?;
EOS;
$insInterest = <<<EOS
INSERT INTO memberInterests($pfield, conid, interest, interested, updateBy)
VALUES (?, ?, ?, ?, ?);
EOS;

$int_upd = 0;
foreach ($existingInterests as $existing) {
    $newVal = array_key_exists($existing['interest'], $newInterests) ? 'Y' : 'N';
    if (array_key_exists('interested', $existing)) {
        if ($newVal != $existing['interested']) { // only update changes
            $upd = 0;
            if ($existing['id'] != null) {
                $upd = dbSafeCmd($updInterest, 'sii', array($newVal, $loginId, $existing['id']));
            }
            if ($upd === false || $upd === 0) {
                $newkey = dbSafeInsert($insInterest, 'iissi', array($personId, $conid, $existing['interest'], $newVal, $loginId));
                if ($newkey !== false && $newkey > 0)
                    $int_upd++;
            } else {
                $int_upd++;
            }
        }
    } else {
        $newkey = dbSafeInsert($insInterest, 'iissi', array($personId, $conid, $existing['interest'], $newVal, $loginId));
        if ($newkey !== false && $newkey > 0)
            $int_upd++;
    }
}
logWrite(array('con'=>$con['name'], 'trans'=>$transId, 'action' => 'Interests added/updated', 'interests' => $existingInterests, 'person' => array($personType, $personId), 'updatedBy' => $loginId));


$response['int_upd'] = $int_upd;

$response['message'] .= "<br/>" . ($int_upd == 0 ? 'No Interests changed' : "$int_upd  Interests updated");
$response['status'] = 'success';

if ($voidTransId) {
    // check to see if the price in the transaction = the paid for the transaction
    $cQ = <<<EOS
SELECT price, couponDiscount, paid
FROM transaction 
WHERE id = ?;
EOS;
    $cR = dbSafeQuery($cQ, 'i', array($transId));
    if ($cR !== false) {
        if ($cR->num_rows == 1) {
            $cTrans = $cR->fetch_assoc();
            $cR->free();
            if ($cTrans['price'] == $cTrans['paid'] + $cTrans['couponDiscount']) {
                // ok this transaction is 'complete', mark it so
                $uT = <<<EOS
UPDATE transaction
SET complete_date = NOW()
WHERE id = ?;
EOS;
                $num_upd = dbSafeCmd($uT, 'i', array ($transId));
                if ($num_upd == 1)
                    unsetSessionVar('transId');
            }
        }
    }
}

ajaxSuccess($response);

function getNewTransaction($conid, $perid, $newperid) {
    $iQ = <<<EOS
INSERT INTO transaction (conid, perid, newperid, userid, price, couponDiscount, paid, type)
VALUES (?, ?, ?, ?, 0, 0, 0, 'regportal');
EOS;
    $transId = dbSafeInsert($iQ, 'iiii', array($conid, $perid, $newperid, $perid));
    setSessionVar('transId', $transId);
    return $transId;
}
