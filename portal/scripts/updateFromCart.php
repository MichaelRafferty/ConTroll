<?php
require_once('../lib/base.php');
require_once('../../lib/log.php');
require_once('../../lib/policies.php');
require_once('../../lib/interests.php');

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
$response['logmessage'] = '';
$response['message'] = '';

if (!(array_key_exists('person', $_POST) && array_key_exists('cart', $_POST) && array_key_exists('action', $_POST))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Parameter error - get assistance'));
    exit();
}

if (!(isSessionVar('id') && isSessionVar('idType'))) {
    ajaxSuccess(array('status'=>'error', 'message'=>'Not logged in.'));
    exit();
}

// check for being resolved/baned
$resolveUpdates = isResolvedBanned();
$response['resolveUpdates'] = $resolveUpdates;
if ($resolveUpdates != null && array_key_exists('logout', $resolveUpdates) && $resolveUpdates['logout'] == 1) {
    ajaxSuccess($response);
    return;
}

$loginId = getSessionVar('id');
$loginType = getSessionVar('idType');
$transId = getSessionVar('transid');
$voidTransId = false; // void the transaction if a free membership was marked paid in this item

$action = $_POST['action'];
$newEmail = $_POST['newEmail'];

logInit($log['reg']);
try {
    $person = json_decode($_POST['person'], true, 512, JSON_THROW_ON_ERROR);
    if ($person == null || (!(array_key_exists('fname', $person) || array_key_exists('first_name', $person) ))) {
        logWrite(array('title'> 'Missing field error trap', 'get' => $_GET, 'post' => $_POST, 'session' => $_SESSION));
        $response['status'] = 'error';
        $response['message'] = 'Error: fname and first_name fields are missing from person, please seek assistance';
        ajaxSuccess($response);
        exit();
    }
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
    $existingPerson = true;
} else {
    $personId = -1;
    $personType = 'n';
    $existingPerson = false;
}
$newPerid = null;

// first update the person so we can build a transaction and memberships
$matchId = null;
if ($personId < 0) {
    if (array_key_exists('fname', $person)) {
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
		REGEXP_REPLACE(TRIM(LOWER(IFNULL(p.pronouns, ''))), '  *', ' ')
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
        $value_arr = array (
            trim($person['fname']),
            trim($person['mname']),
            trim($person['lname']),
            trim($person['suffix']),
            trim($newEmail),
            trim($person['phone']),
            trim($person['badgename']),
            trim($person['legalname']),
            trim($person['pronouns']),
            trim($person['addr']),
            trim($person['addr2']),
            trim($person['city']),
            trim($person['state']),
            trim($person['zip']),
            trim($person['country']),
        );
        $res = dbSafeQuery($exactMsql, 'sssssssssssssss', $value_arr);
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
                $upd = dbSafeCmd($updPQ, 'ii', array ($loginId, $matchId));
                logWrite(array ('con' => $con['name'], 'trans' => $transId, 'action' => 'Exact Match for management', 'person' => $person, 'managedBy' => $loginId));
            }
        }

        if ($matchId == null) {
            // no match found
            // insert into newPerson
            $iQ = <<<EOS
INSERT INTO newperson (transid, last_name, middle_name, first_name, suffix, email_addr, phone, badge_name, legalName, pronouns, 
                       address, addr_2, city, state, zip,
                       country, managedBy, managedByNew, managedReason, updatedBy, lastVerified)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'creation',?,NOW());
EOS;
            $typeStr = 'isssssssssssssssiii';
            $valArray = array (
                $transId,
                trim($person['lname']),
                trim($person['mname']),
                trim($person['fname']),
                trim($person['suffix']),
                trim($newEmail),
                trim($person['phone']),
                trim($person['badgename']),
                trim($person['legalname']),
                trim($person['pronouns']),
                trim($person['addr']),
                trim($person['addr2']),
                trim($person['city']),
                trim($person['state']),
                trim($person['zip']),
                trim($person['country']),
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
            $response['logmessage'] .= "New person with Temporary ID $personId added" . PHP_EOL;
            $newPerid = $personId;
            $personType = 'n';
            logWrite(array ('con' => $con['name'], 'trans' => $transId, 'action' => 'New managed person created', 'person' => $person, 'managedBy' => $loginId));
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error no person information passed. Seek assistance';
        ajaxError($response);
        exit();
    }
}

// newPerid is set above when it inserts the person as a new person, if it's null, it means it exists already
if ($newPerid == null) {
    // update the record
    if ($personType == 'p') {
        $updPersonQ = <<<EOS
UPDATE perinfo
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, phone = ?, badge_name = ?, legalName = ?, pronouns = ?,
    address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, updatedBy = ?, lastVerified = NOW()
WHERE id = ?;
EOS;
    } else {
        $updPersonQ = <<<EOS
UPDATE newperson
SET last_name = ?, middle_name = ?, first_name = ?, suffix = ?, phone = ?, badge_name = ?, legalName = ?, pronouns = ?,
    address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?, updatedBy = ?, lastVerified = NOW()
WHERE id = ?;
EOS;
    }
    // there are two possible items here for passing the data, from the database, which means no changes were passed in,
    // for from the form which means a correction was passed.  If fname exists, it's from the form, handle that.
    // if first_name, its from the database, so do not update the database.
    if (array_key_exists('fname', $person)) {
        $fields = ['lname', 'mname', 'fname', 'suffix', 'phone', 'badgename', 'legalname', 'pronouns', 'addr', 'addr2', 'city',
                   'state', 'zip', 'country'];
        foreach ($fields as $field) {
            if ((!array_key_exists($field, $person)) || $person[$field] == null) {
                if ($field == 'fname') {
                    // log the *** out of this issue, lets see whats going on
                    logWrite(array ('title' > 'Missing field error trap', 'get' => $_GET, 'post' => $_POST, 'session' => $_SESSION,
                                    'response' => $response));
                }
                $person[$field] = '';
            }
        }
        $value_arr = array (
            trim($person['lname']),
            trim($person['mname']),
            trim($person['fname']),
            trim($person['suffix']),
            trim($person['phone']),
            trim($person['badgename']),
            trim($person['legalname']),
            trim($person['pronouns']),
            trim($person['addr']),
            trim($person['addr2']),
            trim($person['city']),
            trim($person['state']),
            trim($person['zip']),
            trim($person['country']),
            $loginId,
            $personId
        );

        $rows_upd = dbSafeCmd($updPersonQ, 'ssssssssssssssii', $value_arr);
        if ($rows_upd === false) {
            ajaxSuccess(array ('status' => 'error', 'message' => 'Error updating person'));
            exit();
        }
        $response['person_rows_upd'] = $rows_upd;
        $response['status'] = 'success';
        $response['logmessage'] .= $rows_upd == 0 ? "No changes" : "$rows_upd person updated" . PHP_EOL;
    } else {
        $response['logmessage'] .= 'No person passed, no update to person information' . PHP_EOL;
    }
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

            $num_del += dbSafeCmd('DELETE FROM reg WHERE id = ?;', 'i', array($cartRow['id']));
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
INSERT INTO reg(conid, perid, newperid, create_trans, complete_trans, price, status, create_user, memId)
values (?, ?, ?, ?, ?, ?, ?, ?, ?);
EOS;
            $typeStr = 'iiiiidsii';
            $valArray = array(
                $cartRow['conid'],
                $personType == 'p' ? $personId : null,
                $personType == 'n' ? $personId : null,
                $transId,
                $cartRow['price'] > 0 ? null : $transId,
                $cartRow['price'],
                $cartRow['price'] > 0 ? 'unpaid' : 'paid',
                $loginId,
                $cartRow['memId']
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
    $response['logmessage'] .= "$num_del Memberships Deleted, $num_ins Memberships Inserted" . PHP_EOL;
    logWrite(array('con'=>$con['name'], 'trans'=>$transId, 'action' => 'cart updated', 'cart' => $cart, 'updatedBy' => $loginId));
}

$newInterests = json_decode($_POST['newInterests'], true);
if ($existingPerson) {
    $existingInterests = json_decode($_POST['oldInterests'], true);
    if ($existingInterests == null)
        $existingInterests = array();
} else {
    $existingInterests = array();
}


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
$interests = getInterests();
foreach ($interests as $interest) {
    $interestName = $interest['interest'];
    $newVal = array_key_exists($interestName, $newInterests) ? 'Y' : 'N';
    if (array_key_exists($interestName, $existingInterests)) {
        // this is an update, there is a record already in the memberInterests table for this interest.
        $existing = $existingInterests[$interestName];
        if (array_key_exists('interested', $existing)) {
            $oldVal = $existing['interested'];
        } else {
            $oldVal = '';
        }
        // only update if changed
        if ($newVal != $oldVal) {
            $upd = 0;
            if ($existing['id'] != null) {
                $upd = dbSafeCmd($updInterest, 'sii', array($newVal, $loginId, $existing['id']));
            }
            if ($upd === false || $upd === 0) {
                $newkey = dbSafeInsert($insInterest, 'iissi', array($personId, $conid, $interestName, $newVal, $loginId));
                if ($newkey !== false && $newkey > 0)
                    $int_upd++;
            } else {
                $int_upd++;
            }
        }
    } else {
        // row doesn't exist in existing interests
        $newkey = dbSafeInsert($insInterest, 'iissi', array($personId, $conid, $interestName, $newVal, $loginId));
        if ($newkey !== false && $newkey > 0)
            $int_upd++;
    }
}
logWrite(array('con'=>$con['name'], 'trans'=>$transId, 'action' => 'Interests added/updated', 'interests' => $existingInterests,
               'person' => array($personType, $personId), 'updatedBy' => $loginId));

$response['int_upd'] = $int_upd;

$response['logmessage'] .= ($int_upd == 0 ? 'No Interests changed' : "$int_upd  Interests updated") . PHP_EOL;

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

$policy_upd = updateMemberPolicies($conid, $personId, $personType, $loginId, $loginType);

if ($response['message'] == '') {
    $response['status'] = 'success';
    $response['message'] = 'All information updated successfully';
} else {
    $response['status'] = 'warn';
}

logInit($log['reg']);
logWrite($response);

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
