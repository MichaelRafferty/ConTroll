<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/email.php";
require_once(__DIR__ . "/../../../lib/email__load_methods.php");

$check_auth = google_init("ajax");
$user_email = $check_auth['email'];
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm, "status" => 'error');

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}
load_email_procs();

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

if($_POST['action'] == "test") {
    if($_POST['email']) { $email = $_POST['email']; }
} else if($_POST['action']=="full") {
    $test=false;
}

$response['test'] = $test;

$con = get_conf("con");
$reg = get_conf("reg");
$emailconf = get_conf("email");
$conid=$con['id'];
$conname = $con['conname'];
$code='';
$email_type = $_POST['type'];

switch ($email_type) {
case 'reminder':
    $emailQ = <<<EOQ
SELECT DISTINCT P.email_addr AS email
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memList M ON (R.memId = M.id)
WHERE R.conid=? AND R.paid=R.price AND P.email_addr LIKE '%@%' AND P.contact_ok='Y' AND M.label != 'rollover-cancel'
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

    // create the coupon now
    // get the user id for createdby
    $usergetQ = <<<EOS
SELECT id
FROM user
WHERE email = ?;
EOS;
    $userid = null;
    $usergetR = dbSafeQuery($usergetQ, 's', array($user_email));
    if ($usergetR !== false) {
        $userL = fetch_safe_assoc($usergetR);
        if ($userL) {
            $userid = $userL['id'];
        }
    }
    // create the coupon for this comeback email
    $couponCreate = <<<EOS
INSERT INTO coupon(conid, oneuse, code, name, startdate, enddate, coupontype, discount, createby)
VALUES (?, 1, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), '%mem', 10.00, ?);
EOS;
    $expires = date_add(date_create(), DateInterval::createFromDateString('30 day'));
    $code='ComeBack' . date_format(date_create(), 'Md');
    $name='Come Back 10% Off Exp ' . date_format($expires, 'M d');
    $couponTypestr = 'issi';
    $couponParamArray = array($conid, $code, $name, $userid);
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
    WHERE p.email_addr LIKE '%@%' AND p.contact_ok='Y' AND r1.id IS NULL AND r2.id IS NULL
    GROUP BY p.email_addr
)
SELECT ?, uuid_v4s(), people.perid, ?, ?
FROM people;
EOS;
    $couponTypestr = 'iiiis';
    $note = 'Autogen: ' . $code;
    $couponParamArray = array($priorcon, $priorcon2, $couponid, $note, $userid);
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
    WHERE p.email_addr LIKE '%@%' AND p.contact_ok='Y' AND r1.id IS NULL AND r2.id IS NULL
    GROUP BY p.email_addr
)
SELECT e.email, e.perid, p.first_name, p.last_name, k.guid
FROM people e
JOIN perinfo p ON (e.perid = p.id)
JOIN couponKeys k ON (e.perid = k.perid AND k.couponId = ?)
ORDER BY e.email;
EOQ;
    $typestr = 'iii';
    $paramarray = array($priorcon, $priorcon2, $couponid);
    $email_text = ComeBackCouponEmail_TEXT($reg['test'], date_format($expires, 'M d, Y'));
    $email_html = ComeBackCouponEmail_HTML($reg['test'], date_format($expires, 'M d, Y'));
    $email_subject = "We miss you! Please come back to $conname";
    break;

case 'survey':
    $emailQ = <<<EOQ
SELECT Distinct P.email_addr AS email
FROM reg R 
JOIN atcon_history H ON (R.id=H.regid)
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

if($test) {
    $email_array[] = array('email' => $email, 'first_name' => 'First', 'last_name' => 'Last', 'guid' => com_create_guid());
} else {
    while($addr = fetch_safe_assoc($emailR)) {
       $email_array[] = $addr;
    }
}

if (array_key_exists('batchsize', $emailconf)) {
    $batchsize = $emailconf['batchsize'];
} else {
    $batchsize= 10;
}

if (array_key_exists('delay', $emailconf)) {
    $delay = $emailconf['delay'];
} else {
    $delay= 1;
}

if ($batchsize == 0  || $delay == 0)
    $batchsize = 999999;

// bunch in groups of 10 to avoid throttle cutoff
$i = 0;
foreach ($email_array as $email) {
    $i++;
    $sendtext = $email_text;
    $sendhtml = $email_html;
    if (array_key_exists('first_name', $email)) {
        $sendtext = str_replace('#FirstName#', $email['first_name'], $sendtext);
        $sendhtml = str_replace('#FirstName#', $email['first_name'], $sendhtml);
    }
    if (array_key_exists('last_name', $email)) {
       $sendtext = str_replace('#LastName#', $email['last_name'], $sendtext);
       $sendhtml = str_replace('#LastName#', $email['last_name'], $sendhtml);
    }
    if (array_key_exists('guid', $email)) {
        $cc = 'offer=' .base64_encode_url( $code . '~!~' . $email['guid']);
        $sendtext = str_replace('#CouponCode#', $cc , $sendtext);
        $sendhtml = str_replace('#CouponCode#', $cc, $sendhtml);
    }
    $return_arr = send_email($con['regadminemail'], trim($email['email']), /* cc */ null, $con['label'] . ": $email_subject",  $sendtext, $sendhtml);

    if ($return_arr['status'] == 'success') {
        $data_array[] = array($email, "success");
        web_error_log("sent $email_type email to " . $email['email']);
    } else {
        $data_array[] = array($email, $return_arr['email_error']);
        $success = 'error';
        web_error_log("failed $email_type email to " . $email['email']);
    }

    if ($i > $batchsize) {
	    $i = 0;
	    sleep($delay);
    }
}

$response['status'] = 'success';
$response['error'] = $data_array;
$response['email_array'] = $email_array;

ajaxSuccess($response);
?>
