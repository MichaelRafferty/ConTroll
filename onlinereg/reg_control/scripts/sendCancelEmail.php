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
$email = "raffem47@yahoo.com";
$tid=63513;

if(!$_POST || !$_POST['action']) {
    $response['error'] = "missing trigger";
    ajaxSuccess($response);
    exit();
}

if($_POST['action'] == "test") {
    if($_POST['tid']) { $tid = $_POST['tid']; }
} else if($_POST['action']=="full") {
    $test=false;
}

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


$emailQ = "select distinct P.email_addr as email, create_trans as tid FROM memList as M JOIN reg as R on R.memId=M.id JOIN perinfo as P on P.id=R.perid JOIN payments as Y on Y.transid=R.create_trans where M.memCategory in ('standard', 'yearahead') and M.conid=$conid order by tid;";
$emailR = dbQuery($emailQ);
$response['numEmails'] = $emailR->num_rows;

$email_array=array();
$data_array=array();

if($test) {
    $emailR = dbQuery("select DISTINCT P.email_addr as email, create_trans as tid FROM reg as R JOIN perinfo as P on P.id=R.perid where create_trans=".sql_safe($tid).";");
    while ($email_value = fetch_safe_assoc($emailR)) {
        array_push($email_array, array('email'=>$email_value['email'], 'tid'=>$email_value['tid']));
    }
} else {
    while($addr = fetch_safe_assoc($emailR)) {
        array_push($email_array, array('email'=>$addr['email'], 'tid'=>$addr['tid']));
    }
}


foreach ($email_array as $email) {
  $email_msg = "";
  try {
    $email_msg = $awsClient->sendEmail(array(
        'Source' => 'regadmin@bsfs.org',
      'Destination' => array(
        'ToAddresses' => array($email['email'])
      ),
      'Message' => array(
        'Subject' => array(
          'Data' => $con['label']. " Membership Cancelation Instructions"
        ),
        'Body' => array(
          'Text' => array(
            'Data' => refundEmail_TEXT($reg['test'], $email['email'], $email['tid'])
          ),
          'Html' => array(
            'Data' => refundEmail_HTML($reg['test'], $email['email'], $email['tid'])
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
