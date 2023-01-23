<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

//require_once "lib/base.php";
require_once "../lib/ajax_functions.php";
require_once "../lib/db_functions.php";
require_once "../lib/email.php";
require_once "../../../aws-api/aws-autoloader.php";

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

db_connect();
global $con;

$con = get_con();
$conid = $con['id'];
$ini = get_conf('artshow');

$login = "";
if(($_SERVER['REQUEST_METHOD'] == "GET") and isset($_GET['login'])) { 
    $login = strtolower(sql_safe($_GET['login']));
}
else if(($_SERVER['REQUEST_METHOD'] == "POST") and isset($_POST['login'])) { 
    $login = strtolower(sql_safe($_POST['login'])); 
} 

if($login == "") {
    ajaxError('No Email Provided');
    exit();
}

$response = array('email' => $login);

$str = str_shuffle(
'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-{}|_'
.
'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%^&*()-{}|_'
);

$infoQ = "SELECT id, email, need_new FROM vendors WHERE email = '$login';";
$infoR = dbQuery($infoQ);

$len = rand(10,16);
$start = rand(0,strlen($str)-$len);
$newpasswd = substr($str, $start, $len);
$hash = password_hash($newpasswd, PASSWORD_DEFAULT);


// build email here

if(($infoR->num_rows == 0) or ($infoR->num_rows > 1)){
    $response['status'] = 'error';
    $response['message'] = "No user found with that email";
    ajaxSuccess($response);
    exit();
}

$info = fetch_safe_assoc($infoR);
if($info['need_new']) {
    $response['status'] = 'error';
    $response['message'] = 'A password reset email has previously been sent.  If you are still having problems loging into your account please contact regadmin@bsfs.org for assistance.';
    ajaxSuccess($response);
    exit();
}

$updateQ = "UPDATE vendors SET need_new=1, password='$hash' where email='$login';";

$amazonCred = get_conf('email');
$awsClient = SesClient::factory(array(
    'key'=>$amazonCred['aws_access_key_id'],
    'secret'=>$amazonCred['aws_secret_access_key'],
    'region'=>'us-east-1',
    'version'=>'2010-12-01'
));

$email = "no send attempt or a failure";

try {
    $email = $awsClient->sendEmail(array(
      'Source' => 'regadmin@balticon.org',
      'Destination' => array(
        'ToAddresses' => array($login)
      ),
      'Message' => array(
        'Subject' => array(
          'Data' => "Password Reset"
        ),
        'Body' => array(
          'Text' => array(
            'Data' => vendorReset($newpasswd, 'vendor')
          ) // HTML
        )
      )// ReplyToAddresses or ReturnPath
    ));
    $email_error = "none";
    $success = "success";
    $data = "success";
} catch (AwsException $e) {
    $email = $e.getAwsErrorType();
    $email_error = $e.getAwsErrorCode();
    $success="error";
    $data=$e.__toString();
}
dbQuery($updateQ); // when ready to go live

$response['message'] = "Password Reset Email Sent, please ensure you check your spam folder";

ajaxSuccess($response);
exit();
