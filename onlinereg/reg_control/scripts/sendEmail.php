<?php
global $ini;
if (!$ini)
    $ini = parse_ini_file(__DIR__ . "/../../../config/reg_conf.ini", true);
if ($ini['reg']['https'] <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}

require_once "../lib/base.php";
require_once "../lib/ajax_functions.php";
require_once "../../../aws.phar";
require_once "../lib/email.php";
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

$check_auth = google_init("ajax");
$perm = "admin";

$response = array("post" => $_POST, "get" => $_GET, "perm"=>$perm);

if($check_auth == false || !checkAuth($check_auth['sub'], $perm)) {
    $response['error'] = "Authentication Failed";
    ajaxSuccess($response);
    exit();
}

$test = true;
$email = "mike@bsfs.org";

if(!$_POST || !$_POST['action']) {
    $response['error'] = "missing trigger";
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
$conid=$con['id'];

$amazonCred = get_conf('email');
$awsClient = SesClient::factory(array(
    'key'=>$amazonCred['aws_access_key_id'],
    'secret'=>$amazonCred['aws_secret_access_key'],
    'region'=>'us-east-1',
    'version'=>'2010-12-01'
));


$emailQ = "SELECT DISTINCT P.email_addr as email FROM reg as R JOIN perinfo as P on P.id=R.perid where R.conid=$conid and R.paid=R.price and P.email_addr like '%@%' and P.contact_ok='Y'";
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


foreach ($email_array as $email) {
  $email_msg = "";
  try {
    $email_msg = $awsClient->sendEmail(array(
        'Source' => 'regadmin@bsfs.org',
      'Destination' => array(
        'ToAddresses' => array($email)
      ),
      'Message' => array(
        'Subject' => array(
          'Data' => $con['label']. " Welcome Email"
        ),
        'Body' => array(
          'Text' => array(
            'Data' => preConEmail_last_TEXT($reg['test'])
          ),
          'Html' => array(
            'Data' => preConEmail_last_HTML($reg['test'])
          )
        )
      )
    ));
    $email_error = "none";
    $success = "success";
    array_push($data_array, array($email, "success"));
  } catch (AwsException $e) {
    $email_error = $e->getCode();
    $success="error";
    array_push($data_array, array($email, $e->getMessage()));
  }

sleep(10);
}

$response['status'] = $success;
$response['error'] = $data_array;
$response['email_array'] = $email_array;

ajaxSuccess($response);
?>
