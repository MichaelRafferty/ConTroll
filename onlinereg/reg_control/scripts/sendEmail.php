<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/email.php";
require_once(__DIR__ . "/../../../lib/email__load_methods.php");

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}
load_email_procs();

$test = true;
$email = null;

if(!$_POST || !$_POST['action']) {
    $response['error'] = "missing trigger";
    ajaxSuccess($response);
    exit();
}

if(!$_POST || !$_POST['type']) {
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
$email_type = $_POST['type'];

if ($email_type == 'reminder') {
    $emailQ = <<<EOQ
SELECT DISTINCT P.email_addr AS email
FROM reg R
JOIN perinfo P ON (P.id=R.perid)
JOIN memList M ON (R.memId = M.id)
WHERE R.conid=$conid AND R.paid=R.price AND P.email_addr LIKE '%@%' AND P.contact_ok='Y' AND M.label != 'rollover-cancel'
ORDER BY email;
EOQ;

    $email_text = preConEmail_last_TEXT($reg['test']);
    $email_html = preConEmail_last_HTML($reg['test']);
    $email_subject = "Welcome Email";
} else if ($email_type == 'marketing') {
    $priorcon = $conid - 1;
    $emailQ = <<<EOQ
SELECT DISTINCT p.email_addr AS email
FROM perinfo p
JOIN reg r ON (r.perid = p.id AND r.conid = $priorcon)
LEFT OUTER JOIN reg r2 ON (r2.perid = p.id and r2.conid = $conid)
WHERE p.email_addr LIKE '%@%' AND p.contact_ok='Y' and r2.id IS NULL AND r.price > 0
ORDER BY email;
EOQ;
    $email_text = MarketingEmail_TEXT($reg['test']);
    $email_html = MarketingEmail_HTML($reg['test']);
    $email_subject = "We miss you! Please come back to Philcon";
} else if ($email_type == 'survey') {
    $emailQ = <<<EOQ
SELECT Distinct P.email_addr
FROM atcon A
JOIN atcon_badge B ON (B.atconId=A.id)
JOIN reg R ON (R.id=B.badgeId)
JOIN transaction T ON (T.id=A.transid)
JOIN memLabel M ON (M.id=R.memId)
JOIN perinfo P ON (R.perid = P.id)
WHERE R.conid=$conid AND (B.action = 'attach')
AND M.shortname not like '%cancel%' AND M.shortname not like '%Child%' AND M.shortname not like '% In Tow%'
AND P.email_addr != '' AND R.conid = A.conid
ORDER BY P.email_addr;
EOQ;

    $email_text = surveyEmail_TEXT($reg['test']);
    $email_html = surveyEmail_HTML($reg['test']);
    $email_subject = "Thanks for attending, can you help us improve by answering this 3 question survey";
} else {
    $response['error'] = "invalid email type";
    ajaxSuccess($response);
    exit();
}

$emailR = dbQuery($emailQ);
$response['numEmails'] = $emailR->num_rows;

$email_array=array();
$data_array=array();

if($test) {
    $email_array = array($email);
} else {
    while($addr = fetch_safe_assoc($emailR)) {
       array_push($email_array, $addr['email']);
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
    $return_arr = send_email($con['regadminemail'], trim($email), /* cc */ null, $con['label'] . ": $email_subject",  $email_text, $email_html);

    if ($return_arr['status'] == 'success') {
        array_push($data_array, array($email, "success"));
        web_error_log("sent $email_type email to $email");
    } else {
        array_push($data_array, array($email, $return_arr['email_error']));
        $success = 'error';
        web_error_log("failed $email_type email to $email");
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
