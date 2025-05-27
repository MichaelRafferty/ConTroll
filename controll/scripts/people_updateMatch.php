<?php
global $db_ini;

require_once "../lib/base.php";
require_once('../../lib/log.php');
require_once('../../lib/email__load_methods.php');
require_once('../../lib/policies.php');
require_once('../../lib/interests.php');

$check_auth = google_init("ajax");
$perm = "people";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!(array_key_exists('type', $_POST)) && array_key_exists('newperid', $_POST)) {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
    exit();
}

$type = $_POST['type'];
$newPerid = $_POST['newperid'];
$perid = null;
if ($type == 'e') {
    $perid = $_POST['perid'];
} else if ($type != 'n') {
    $response['error'] = 'Parameter Error';
    ajaxSuccess($response);
}
$updatedBy = $_SESSION['user_perid'];

$con = get_conf('con');
$portal_conf = get_conf('portal');
$conid = $con['id'];

// due to a race condition of two people trying to match the same person, it's necessary to check if they are already matched
$cMatch = <<<EOS
SELECT perid
FROM newperson
WHERE id = ?;
EOS;
$cR = dbSafeQuery($cMatch, 'i', array($newPerid));
if ($cR === false) {
    $response['error'] = 'Error checking if newperson already matched, check logs and seek assistance';
    ajaxSuccess($response);
    return;
}
$matchPerid = $cR->fetch_row()[0];
$cR->free();
if ($matchPerid && $matchPerid > 0) {
    $response['error'] = "New Person $newPerid already matched to $matchPerid.";
    ajaxSuccess($response);
    return;
}

$iP = <<<EOS
INSERT INTO perinfo(last_name, first_name, middle_name, suffix, email_addr, phone, badge_name,
    legalName, pronouns, address, addr_2, city, state, zip, country,
    banned, active, managedBy, managedReason, change_notes, updatedBy)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);
EOS;
$uP = <<<EOS
UPDATE perinfo
SET last_name = ?, first_name = ?, middle_name = ?, suffix = ?, email_addr = ?, phone = ?, badge_name = ?, 
    legalName = ?, pronouns = ?, address = ?, addr_2 = ?, city = ?, state = ?, zip = ?, country = ?,
	banned = ?, active = ?, managedBy = ?, managedReason = ?, change_notes = ?, updatedBy = ?
WHERE id = ?;
EOS;
$typestr = 'sssssssssssssssssissi';

// built insert/update array
$values = [
    $_POST['lastName'] == null ? '' : $_POST['lastName'],
    $_POST['firstName'] == null ? '' : $_POST['firstName'],
    $_POST['middleName'] == null ? '' : $_POST['middleName'],
    $_POST['suffix'] == null ? '' : $_POST['suffix'],
    $_POST['emailAddr'] == null ? '' : $_POST['emailAddr'],
    $_POST['phone'] == null ? '' : $_POST['phone'],
    $_POST['badgeName'] == null ? '' : $_POST['badgeName'],
    $_POST['legalName'] == null ? '' : $_POST['legalName'],
    $_POST['pronouns'] == null ? '' : $_POST['pronouns'],
    $_POST['address'] == null ? '' : $_POST['address'],
    $_POST['addr2'] == null ? '' : $_POST['addr2'],
    $_POST['city'] == null ? '' : $_POST['city'],
    $_POST['state'] == null ? '' : $_POST['state'],
    $_POST['zip'] == null ? '' : $_POST['zip'],
    $_POST['country'] == null ? '' : $_POST['country'],
    $_POST['banned'] == null ? '' : $_POST['banned'],
    $_POST['active'] == null ? '' : $_POST['active']
];
$managedBy = null;
// add mnager and reason
switch ($_POST['managerAction']) {
    case 'ACC':
        $managedBy = $_POST['managerId'];
        if ($managedBy == 'null')
            $managedBy = null;
        $values[] = $managedBy;
        $values[] = 'Assigned PM';
        break;
    case 'REM':
        $values[] = null;
        $values[] = 'Cleared PM';
        break;
    case 'EMAIL':
        $values[] = null;
        $values[] = 'Assoc Req PM';
}
// change notes
    $values[] = "People Match by $updatedBy";
// and updated by
    $values[] = $updatedBy;

if ($type == 'n') {
    // insert into perinfo and get new key
    $perid = dbSafeInsert($iP, $typestr, $values);
    if ($perid === false) {
        $response['error'] = 'Error inserting newperson into perinfo table, check logs and seek assistance';
        ajaxSuccess($response);
        return;
    }
    $response['success'] = "Person $perid created from match data edited from $newPerid";
}
if ($type == 'e') {
    // add perid for where clause
    $typestr .= 'i';
    $values[] = $perid;
    $num_upd = dbSafeCmd($uP, $typestr, $values);
    if ($num_upd === false) {
        $response['error'] = 'Error updating perinfo table, check logs and seek assistance';
        ajaxSuccess($response);
        return;
    }
    if ($num_upd == 1) {
        $response['success'] = "Person $perid updated from match data edited from $newPerid";
    } else {
        $response['success'] = "Person $perid matched, all the data matched, nothing was updated in the person info";
    }
}

// main person inserted/updated, now update newperson with the perid
$uN = <<<EOS
UPDATE newperson
SET perid = ?, managedBy = ?, managedByNew = null, updatedBy = ?
WHERE id = ?;
EOS;

$num_upd = dbSafeCmd($uN, 'iiii', array($perid, $managedBy, $updatedBy, $newPerid));
if ($num_upd === false) {
    $response['success'] .= "<br/>Error trying to update the newperson record $newPerid with the new perid $perid";
} else if ($num_upd != 1) {
    $response['success'] .= "<br/>Error newperson record $newPerid not found when updating it with the new perid $perid";
} else {
    $response['success'] .= "<br/>Match newperson record $newPerid updated with perid $perid";
}

// now update the rest of the records for the newperid to perid transition
$rows = dbSafeCmd('UPDATE newperson SET updatedBy = ?, managedBy = ?, managedByNew = null WHERE managedByNew=?;', 'iii',
                  array ($updatedBy, $perid, $newPerid));
// fix people they manage from perinfo to map to their new perid
$rows = dbSafeCmd('UPDATE perinfo SET updatedBy = ?, managedBy = ?, managedByNew = null WHERE managedByNew=?;', 'iii',
                  array ($updatedBy, $perid, $newPerid));
// update referenced tables reg, transaction, exhibiors, memberInterests, memberPolciies and payorPlans to now point to the perid
$rows = dbSafeCmd('UPDATE reg SET perid=? WHERE newperid=?;', 'ii', array ($perid, $newPerid));
$rows = dbSafeCmd('UPDATE transaction SET perid=? WHERE newperid=?;', 'ii', array ($perid, $newPerid));
$rows = dbSafeCmd('UPDATE exhibitors SET perid=? WHERE newperid=?;', 'ii', array ($perid, $newPerid));
$rows = dbSafeCmd('UPDATE payorPlans SET perid=? WHERE newperid=?;', 'ii', array ($perid, $newPerid));

// now update / insert the policies
$resultValues = [];
foreach ($_POST as $key => $value) {
    if (!str_starts_with($key, 'p_'))
        continue;
    $policy = mb_substr($key, 2); // take off the p_
    $resultValues[$policy] = $value;
}
// now merge them
$msg = mergePolicies($conid, $perid, 'n', $newPerid, $updatedBy, $resultValues);
if ($msg != '') {
    $response['error'] = $msg;
}

$msg = mergeInterests($conid, $perid, 'n', $newPerid, $updatedBy);
if ($msg != '') {
    $response['error'] = $msg;
}

// now if the managerAction == 'email', build the associate email request...
 if ($_POST['managerAction'] == 'EMAIL') {
     $email = $_POST['email_addr']; // the perinfo entry, to whom we send the associate email
     // get the email address and the full name of the manager
     $mQ = <<<EOS
SELECT email_addr, TRIM(REGEXP_REPLACE(CONCAT_WS(' ', first_name, middle_name, last_name, suffix), '  *', ' ')) AS fullName
FROM perinfo
WHERE id = ?;
EOS;
     $mR = dbSafeQuery($mQ, 'i', array ($managedBy));
     if ($mR === false || $mR->num_rows != 1) {
         $response['success'] .= "<br/>Error: unable to send manage request manager does not exist";
         ajaxSuccess($response);
         return;
     }

     $mL = $mR->fetch_assoc();
     $mR->free();
     $managerEmail = $mL['email_addr'];
     $managerFullName = $mL['fullName'];

     $insQ = <<<EOS
INSERT INTO portalTokenLinks(email, action, source_ip)
VALUES(?, 'attach', ?);
EOS;
     $insid = dbSafeInsert($insQ, 'ss', array ($email, $_SERVER['REMOTE_ADDR']));
     if ($insid === false) {
         web_error_log('Error inserting tracking ID for email link');
     }

     $parms = array ();
     $parms['email'] = $email;             // address to verify via email
     $parms['type'] = 'attach';            // verify type
     $parms['ts'] = time();                // when requested for timeout check
     $parms['lid'] = $insid;               // id in portalTokenLinks table
     $parms['acctId'] = $perid;            // person to attach
     $parms['acctType'] = 'p';             // person to attach
     $parms['loginId'] = $updatedBy;       // who is requesting the attach
     $parms['loginType'] = 'p';            // id in portalTokenLinks table
     $parms['managerEmail'] = $managerEmail;
     $string = json_encode($parms);  // convert object to json for making a string out of it, which is encrypted in the next line
     $string = encryptAttach($string, true);
     $token = $portal_conf['portalsite'] . "/respond.php?action=attach&vid=$string";     // convert to link for emailing

     load_email_procs();
     $loginFullname = $managerFullName;
     $loginEmail = $managerEmail;
     $personFullname = TRIM($_POST['first_name'] . ' ' . $_POST['last_name']);
     $personEmail = $_POST['email_addr'];
     $label = $con['label'];
     $regadminemail = $con['regadminemail'];
     if ($personFullname == '') {
         $greeting = $personEmail;
     }
     else {
         $greeting = "$personFullname ($personEmail)";
     }

     $body = "Dear $greeting," . PHP_EOL . PHP_EOL .
         "$loginFullname requested to manage your $label account." . PHP_EOL . PHP_EOL .
         "If you have any questions about why this request was made, please contact them directly at $loginFullname, $loginEmail)" . PHP_EOL . PHP_EOL .
         "If you are fine with $loginFullname managing your account and with giving them access to your information, please click the link below." . PHP_EOL .
         PHP_EOL .
         $token . PHP_EOL . PHP_EOL .
         'This link expires in 24 hours.' . PHP_EOL . PHP_EOL .
         'Thank you;' . PHP_EOL .
         "$label - Registration" . PHP_EOL .
         "$regadminemail" . PHP_EOL . PHP_EOL;


     $htmlbody = "<p>Dear $greeting,</p>" . PHP_EOL .
         "<p>$loginFullname requested to manage your $label account.</p>" . PHP_EOL .
         "<p>If you have any questions about why this request was made, please contact them directly at $loginFullname, " .
         "<a href='mailto:$loginEmail'>$loginEmail</a>)</p>" . PHP_EOL .
         "<p>If you are fine with $loginFullname managing your account and with giving them access to your information, please click the link below.</p>" . PHP_EOL .
         '<p><a href="' . $token . '">Click this link to approve the management request</a></p>' . PHP_EOL .
         '<p>This link expires in 24 hours.</p>' . PHP_EOL .
         '<p>Thank you;</p>' . PHP_EOL .
         "<p>$label - Registration<br/>" . PHP_EOL .
         "<a href='mailto:$regadminemail'>$regadminemail</a></p>" . PHP_EOL . PHP_EOL;

     $return_arr = send_email($regadminemail, trim($email), /* cc */ null, $label . ' Membership Portal Account Management Request', $body, $htmlbody);
     if (array_key_exists('error_code', $return_arr)) {
         $error_code = $return_arr['error_code'];
     }
     else {
         $error_code = null;
     }
     if ($error_code) {
         $response['success'] .= "<br/>Error: $error_code sending associate email";
     } else {
         $response['success'] .= "<br/>Request to associate (manage) email sent";
     }
}
ajaxSuccess($response);
?>
