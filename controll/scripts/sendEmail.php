<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/email.php";

$check_auth = google_init("ajax");
$user_email = $check_auth['email'];
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm, "status" => 'error');

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('user_id', $_SESSION)) {
    ajaxError('Invalid credentials passed');
    return;
}
$user_perid = $_SESSION['user_perid'];

$test = true;
$email = null;

if (!array_key_exists('action', $_POST)) {
    $response['error'] = "missing trigger";
    ajaxSuccess($response);
    exit();
}

if (!array_key_exists('type', $_POST)) {
    $response['error'] = "missing email type";
    ajaxSuccess($response);
    exit();
}

$con = get_conf("con");
$reg = get_conf("reg");
$emailconf = get_conf("email");
$conid=$con['id'];
$conname = $con['conname'];
$code='';
$email_type = $_POST['type'];

if ($_POST['action'] == 'test' || $reg['test'] == 1) {
    if ($_POST['email']) {
        $email = $_POST['email'];
    }
} else if ($_POST['action'] == 'full') {
    $test = false;
}

if ($email == null || $email == '') {
    $email = $con['regadminemail'];
}

$response['test'] = $test;
$macroSubstitution = false;

switch ($email_type) {
case 'reminder':
    $emailQ = <<<EOQ
SELECT DISTINCT P.email_addr AS email
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memList M ON (R.memId = M.id)
WHERE R.conid=? AND R.paid=R.price AND P.email_addr LIKE '%@%' AND P.contact_ok='Y' AND M.label != 'rollover-cancel' AND M.memCategory != 'cancel'
ORDER BY email;
EOQ;
    $typestr = 'i';
    $paramarray = array($conid);
    $email_text = preConEmail_last_TEXT($reg['test']);
    $email_html = preConEmail_last_HTML($reg['test']);
    $email_subject = "Welcome Email";
    break;

case 'marketing':
    $priorcon = $conid - 1;
    $emailQ = <<<EOQ
SELECT DISTINCT p.email_addr AS email
FROM perinfo p
JOIN reg r ON (r.perid = p.id AND r.conid = ?)
LEFT OUTER JOIN reg r2 ON (r2.perid = p.id and r2.conid = ?)
WHERE p.email_addr LIKE '%@%' AND p.contact_ok='Y' and r2.id IS NULL AND r.price > 0
ORDER BY email;
EOQ;
    $typestr = 'ii';
    $paramarray = array($priorcon, $conid);
    $email_text = MarketingEmail_TEXT($reg['test']);
    $email_html = MarketingEmail_HTML($reg['test']);
    $email_subject = "We miss you! Please come back to $conname";
    break;

case 'comeback':
    $priorcon = $conid - 1;
    $priorcon2 = $conid - 2;
    $expires = date_add(date_create(), DateInterval::createFromDateString('30 day'));
    $code='ComeBack' . date_format(date_create(), 'Md');

    // create the coupon now
    // get the user id for createdby
    $usergetQ = <<<EOS
SELECT id
FROM user
WHERE email = ?;
EOS;
    // create the coupon for this comeback email
    $couponCreate = <<<EOS
INSERT INTO coupon(conid, oneuse, code, name, startdate, enddate, coupontype, discount, createby)
VALUES (?, 1, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), '%mem', 10.00, ?);
EOS;

    $name='Come Back 10% Off Exp ' . date_format($expires, 'M d');
    $couponTypestr = 'issi';
    $couponParamArray = array($conid, $code, $name, $user_perid);
    $couponid = dbSafeInsert($couponCreate, $couponTypestr, $couponParamArray);
    if ($couponid === false) {
        $response['error'] = 'Count not create coupon';
        ajaxSuccess($response);
        exit();
    }

    // now create the coupon keys for this email
    $couponKeysCreate = <<<EOS
INSERT INTO couponKeys(couponId, guid, perid, notes, createBy) 
WITH people AS (
SELECT  p.email_addr as email, MIN(p.id) AS perid
    FROM perinfo p
    LEFT OUTER JOIN reg r1 ON (r1.perid = p.id and r1.conid = ?)
    LEFT OUTER JOIN reg r2 ON (r2.perid = p.id and r2.conid = ?)
    LEFT OUTER JOIN reg r3 ON (r3.perid = p.id and r3.conid = ?)
    WHERE p.email_addr LIKE '%@%' AND p.contact_ok='Y' AND r1.id IS NULL AND r2.id IS NULL AND r3.id IS NULL
    GROUP BY p.email_addr
)
SELECT ?, uuid_v4s(), people.perid, ?, ?
FROM people;
EOS;
    $couponTypestr = 'iiiiis';
    $note = 'Autogen: ' . $code;
    $couponParamArray = array($conid, $priorcon, $priorcon2, $couponid, $note, $user_perid);
    $num_keys = dbSafeCmd($couponKeysCreate, $couponTypestr, $couponParamArray);
    if ($num_keys === false) {
        $response['error'] = 'Count not create couponKeys';
        ajaxSuccess($response);
        exit();
    }
    $emailQ = <<<EOQ
WITH people AS (
    SELECT p.email_addr as email, MIN(p.id) AS perid
    FROM perinfo p
    LEFT OUTER JOIN reg r1 ON (r1.perid = p.id and r1.conid = ?)
    LEFT OUTER JOIN reg r2 ON (r2.perid = p.id and r2.conid = ?)
        LEFT OUTER JOIN reg r3 ON (r3.perid = p.id and r3.conid = ?)
    WHERE p.email_addr LIKE '%@%' AND p.contact_ok='Y' AND r1.id IS NULL AND r2.id IS NULL AND r3.id IS NULL
    GROUP BY p.email_addr
)
SELECT e.email, e.perid, p.first_name, p.last_name, k.guid
FROM people e
JOIN perinfo p ON (e.perid = p.id)
JOIN couponKeys k ON (e.perid = k.perid AND k.couponId = ?)
ORDER BY e.email;
EOQ;
    $typestr = 'iiii';
    $paramarray = array($conid, $priorcon, $priorcon2, $couponid);
    $email_text = ComeBackCouponEmail_TEXT($reg['test'], date_format($expires, 'M d, Y'));
    $email_html = ComeBackCouponEmail_HTML($reg['test'], date_format($expires, 'M d, Y'));
    $email_subject = "We miss you! Please come back to $conname";
    $macroSubstitution = true;
    break;

case 'survey':
    $emailQ = <<<EOQ
SELECT Distinct P.email_addr AS email
FROM reg R 
JOIN regActions H ON (R.id=H.regid)
JOIN reg R ON (R.id=H.regid)
JOIN transaction T ON (T.id=H.tid)
JOIN memLabel M ON (M.id=R.memId)
JOIN perinfo P ON (R.perid = P.id)
WHERE R.conid=? AND (H.action = 'attach')
AND M.shortname not like '%cancel%' AND M.shortname not like '%Child%' AND M.shortname not like '% In Tow%'
AND P.email_addr != ''
ORDER BY P.email_addr;
EOQ;
    $typestr = 'i';
    $paramarray = array($conid);
    $email_text = surveyEmail_TEXT($reg['test']);
    $email_html = surveyEmail_HTML($reg['test']);
    $email_subject = "Thanks for attending, can you help us improve by answering this 3 question survey";
    break;

default:
    $response['error'] = "invalid email type";
    ajaxSuccess($response);
    exit();
}

$emailR = dbSafeQuery($emailQ, $typestr, $paramarray);
if ($emailR === false) {
    $response['error'] = 'Retrieval of email addresses failed';
    ajaxSuccess($response);
    exit();
}
$response['numEmails'] = $emailR->num_rows;
if ($response['numEmails'] == 0) {
    $response['error'] = 'No emails match query';
    ajaxSuccess($response);
    exit();
}


$email_array=array();
$data_array=array();

if ($test) {
    $email_test[] = array('email' => $email, 'first_name' => 'First', 'last_name' => 'Last', 'guid' => guidv4());
    $response['emailTest'] = $email_test;
}

while($addr =  $emailR->fetch_assoc()) {
   $email_array[] = $addr;
}


$response['emailText'] = $email_text;
$response['emailHTML'] = $email_html;
$response['emailFrom'] = $email;
$response['emailTo'] =  $email_array;
$response['emailCC'] = null;
$response['emailSubject'] = $con['label'] . ": $email_subject";
$response['emailType'] = $email_type;
$response['macroSubstitution'] = $macroSubstitution;

ajaxSuccess($response);
?>
