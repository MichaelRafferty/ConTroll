<?php
global $db_ini;

require_once "../lib/base.php";
require_once "../lib/email.php";
require_once(__DIR__ . "/../../lib/email__load_methods.php");
require_once(__DIR__ . "/../../lib/global.php");

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
$user_id = $_SESSION['user_id'];

load_email_procs();

$test = true;
$email = null;

if (!array_key_exists('data', $_POST)) {
    $response['error'] = "missing data to send";
    ajaxSuccess($response);
    exit();
}

$con = get_conf("con");
$reg = get_conf("reg");
$emailconf = get_conf("email");
$conid=$con['id'];
$conname = $con['conname'];
$code='';

$json = urldecode(base64_decode($_POST['data']));
$data = json_decode($json, true);

if ($data['emailTest'] || $reg['test'] == 1) {
    $email = $data['emailTest'][0]['email'];
}


if ($email == null || $email == '') {
    $email = $con['regadminemail'];
}

if ($data['action'] == 'full' && $reg['test'] == 0)
    $test = false;

$response['test'] = $test;

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

if ($test) {
    $email_array = $data['emailTest'];
} else {
    $email_array = $data['emailTo'];
}
$emailType = $data['emailType'];
$emailText = $data['emailText'];
$emailHTML = $data['emailHTML'];
$emailFrom = $data['emailFrom'];
$emailCC = $data['emailCC'];
$emailSubject = $data['emailSubject'];
$macroSubstitution = $data['macroSubstitution'];

// bunch in groups of 10 to avoid throttle cutoff
$i = 0;
$numsent = 0;
foreach ($email_array as $email) {
    $i++;
    $sendtext = $emailText;
    $sendhtml = $emailHTML;
    if ($macroSubstitution) {
        if (array_key_exists('first_name', $email)) {
            $sendtext = str_replace('#FirstName#', $email['first_name'], $sendtext);
            $sendhtml = str_replace('#FirstName#', $email['first_name'], $sendhtml);
        }
        if (array_key_exists('last_name', $email)) {
            $sendtext = str_replace('#LastName#', $email['last_name'], $sendtext);
            $sendhtml = str_replace('#LastName#', $email['last_name'], $sendhtml);
        }
        if (array_key_exists('guid', $email)) {
            $cc = 'offer=' . base64_encode_url($code . '~!~' . $email['guid']);
            $sendtext = str_replace('#CouponCode#', $cc, $sendtext);
            $sendhtml = str_replace('#CouponCode#', $cc, $sendhtml);
        }
    }
    try {
        $return_arr = send_email($emailFrom, trim($email['email']), $emailCC, $emailSubject, $sendtext, $sendhtml);

        if ($return_arr['status'] == 'success') {
            $data_array[] = array($email, "success");
            web_error_log("sent $emailType email to " . $email['email']);
            $numsent++;
        } else {
            $data_array[] = array($email, $return_arr['email_error']);
            $success = 'error';
            web_error_log("failed $emailType email to " . $email['email']);
        }
    } catch (Exception $e) {
        web_error_log("Email to: " . trim($email['email']) . " failed, threw exception");
    }

    if ($i > $batchsize) {
	    $i = 0;
	    sleep($delay);
    }
}

$response['status'] = 'success';
$response['detail'] = $data_array;
$response['email_array'] = $email_array;
$response['emails_sent'] = $numsent;

ajaxSuccess($response);
?>
