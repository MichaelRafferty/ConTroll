<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "../lib/ajax_functions.php";
require_once "../lib/db_functions.php";
require_once "../lib/log.php";

db_connect();

require_once "../lib/email.php";
require_once "../../../aws-api/aws-autoloader.php";

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

global $con;

$con = get_con();
$conid = $con['id'];
$ini = get_conf('artshow');

$ccauth = get_conf('cc');
$cclink = get_conf('cc-connect');


$log = get_conf('log');
logInit($log['artshow']);


$amazonCred = get_conf('email');
$awsClient = SesClient::factory(array(
    'key'=>$amazonCred['aws_access_key_id'],
    'secret'=>$amazonCred['aws_secret_access_key'],
    'region'=>'us-east-1',
    'version'=>'2010-12-01'
    ));

$email = "no send attempt or a failure";

session_start();
if(!isset($_SESSION['id'])) { ajaxSuccess(array('status'=>'error', 'message'=>'Session Failure')); exit; }

$venId = $_SESSION['id'];

$response = array("post" => $_POST, "get" => $_GET);

$vendorR = dbQuery("SELECT * from vendors where id=$venId;");
$vendor = fetch_safe_assoc($vendorR);

$body = "Vendor " . $vendor['name'] . " Paid an Invoice for $ " . $_POST['total']
    . " covering virtual vendor space.  The information they included with the payment is below.\n\n";

$total = $_POST['total'];

foreach ($_POST as $key => $value) {
    switch ($key) {
        case 'vendor':
        case 'total':
        case 'ccnum':
        case 'cvv':
        case 'expmo':
        case 'expyr':
            break;
        default:
            $body .= "$key: $value\n";
    };
}

logWrite(array('conid'=>$conid, 'vendor'=>$venId));

//cc payment
$ccsale = array(
    'ssl_merchant_id'=>$ccauth['ssl_merchant_id'],
    'ssl_user_id'=>$ccauth['ssl_user_id'],
    'ssl_pin'=>$ccauth['ssl_pin'],
    'ssl_transaction_type'=>'CCSALE',
    'ssl_test_mode'=>$cclink['test_mode'],
    'ssl_show_form'=>'false',
    'ssl_amount'=>$total,
    'ssl_description'=>"Balticon Vendor Registration",
    'ssl_result_format'=>'ASCII',
    'ssl_avs_address'=>$_POST['street'],
    'ssl_city'=>$_POST['city'],
    'ssl_state'=>$_POST['state'],
    'ssl_avs_zip'=>$_POST['zip'],
    'ssl_country'=>$_POST['country'],
    'ssl_first_name'=>$_POST['fname'],
    'ssl_last_name'=>$_POST['lname'],
    'ssl_email'=>$_POST['email'],
    'ssl_cardholder_ip'=>$_SERVER['REMOTE_ADDR']
);
if(!isset($_POST['ccnum']) || !isset($_POST['cvv']) ||
!isset($_POST['expmo'])|| !isset($_POST['expyr'])) {
    ajaxSuccess(array('status'=>'error','data'=>'missing CC information'));
    exit();
} else {
    $ccsale['ssl_card_number'] = preg_replace('/\s+/', '', $_POST['ccnum']);
    $ccsale['ssl_cvv2cvc2']=$_POST['cvv'];
    $ccsale['ssl_cvv2cvc2_indicator']=1;
    $ccsale['ssl_exp_date']=$_POST['expmo'].$_POST['expyr'];
}


$sale_log_keys = array(
    "ssl_amount",
    "ssl_avs_address",
    "ssl_avs_country",
    "ssl_cardholder_ip"
);

//log request keys
$log_resp = array_intersect_key($ccsale, array_flip($sale_log_keys));
logWrite(array('vendor'=>$venId, 'response'=>$log_resp));


$args = "";
foreach($ccsale as $key => $value) {
    if($args != "") { $args .= "&"; }
    $args.="$key=$value";
}


$url = "https://".$cclink['host'].$cclink['site'];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$resp_array = array();
$response = curl_exec($ch);
$error_string = curl_error($ch);

$response_lines = preg_split("/\n/", $response);
foreach($response_lines as $line) {
   $line_array = preg_split("/=/", $line);
    if($line_array[1]!="") { $resp_array[$line_array[0]]=$line_array[1]; }
}

 $log_keys = array(
    "ssl_result_message",
    "ssl_amount",
    "ssl_txn_id",
    "ssl_txn_time",
    "ssl_approval_code",
    "ssl_status",
    "errorCode",
    "errorName",
    "errorMessage"
    );

  $db_keys = array(
    "ssl_result_message",
    "ssl_amount",
    "ssl_txn_id",
    "ssl_txn_time",
    "ssl_approval_code",
    "ssl_status",
    "ssl_card_number",
    "ssl_description"
    );

  $log_resp = array_intersect_key($resp_array, array_flip($log_keys));
  $db_resp = array_intersect_key($resp_array, array_flip($db_keys));

//log cc processor response
  logWrite(array('vendor'=>$venId, 'response'=>$log_resp));

if(isset($resp_array['errorCode'])) {
    ajaxSuccess(array('status'=>'error','data'=>$resp_array['errorMessage']));
    exit();
  } 

if(isset($resp_array['ssl_result_message']) && // cc approved
    ($resp_array['ssl_result_message']=='APPROVAL' or $resp_array['ssl_result_message']=='APPROVED' )) {
$transid = dbInsert("INSERT INTO transaction (conid, price, paid, notes) VALUES ($conid, " . sql_safe($db_resp['ssl_amount']) . ", " . sql_safe($db_resp['ssl_amount']) . ", 'Virtual Vendor Space Purchase');");
$tableUpdate = "UPDATE vendor_show SET purchased='" . sql_safe($_POST['table_count'])
    . "', price='" . sql_safe($_POST['table_sub']) . "', paid='" 
    . sql_safe($_POST['table_sub']) . "', transid='$transid'"
    . ", virtual_type='" . sql_safe($_POST['virtual']) . "'"
    . " WHERE vendor=$venId and type='virtual' and conid=$conid;";
dbQuery($tableUpdate);
$regUpdate = "UPDATE vendor_reg SET registered=registered + ".sql_safe($_POST['table_count'])." WHERE conid=$conid and type='virtual';";
dbQuery($regUpdate);

  $txn_record = "transid='" . sql_safe($transid) . "', " .
    "type='credit', category='other', ".
    "description='" . sql_safe($db_resp['ssl_description']) .
    "', source='online', amount='" . sql_safe($db_resp['ssl_amount']) .
    "', txn_time='" . sql_safe($db_resp['ssl_txn_time']) .
    "', cc='" . sql_safe($db_resp['ssl_card_number']) .
    "', cc_txn_id='" . sql_safe($db_resp['ssl_txn_id']) .
    "', cc_approval_code='" .  sql_safe($db_resp['ssl_approval_code']) . "'";

  $txnQ = "INSERT INTO payments SET $txn_record;";
  $txnid = dbInsert($txnQ);
 
  $body .= "CC Transaction Approved " . $db_resp['ssl_amount'] . " " . $db_resp['ssl_txn_id'] . " " . $db_resp['ssl_approval_code'] . "\n\n";

$info = get_conf('vendor');

$email_msg = "no send attempt or a failure";
  try {
    $email_msg = $awsClient->sendEmail(array(
      'Source' => 'regadmin@bsfs.org',
      'Destination' => array(
        'ToAddresses' => array($_POST['email'], $info['virtual'])
      ),
      'Message' => array(
        'Subject' => array(
          'Data' => $con['label']. " Online Vendor Purchase"
        ),
        'Body' => array(
          'Text' => array(
            'Data' => $body
          ) // HTML
        )
      )// ReplyToAddresses or ReturnPath
    ));
    $email_error = "none";
    $success = "success";
    $data = "success";
  } catch (AwsException $e) {
    $email_error = $e->getCode();
    $success="error";
    $data=$e->getMessage();
}} else { // cc failed
    if($cclink['test_mode'] && $_POST['cctest'] != 'on') {
        $data='success / no CC test';
        if($reg1 != "") { dbQuery($reg1); }
        if($reg2 != "") { dbQuery($reg2); }
        
        $body .= "No CC Test.\n\n";
  try {
    $email_msg = $awsClient->sendEmail(array(
      'Source' => 'regadmin@bsfs.org',
      'Destination' => array(
        'ToAddresses' => array($_POST['email'], $info['virtual'])
      ),
      'Message' => array(
        'Subject' => array(
          'Data' => $con['label']. " Online Vendor Purchase"
        ),
        'Body' => array(
          'Text' => array(
            'Data' => $body
          ) // HTML
        )
      )// ReplyToAddresses or ReturnPath
    ));
    $email_error = "none";
    $success = "success";
    $data = "success";
  } catch (AwsException $e) {
    $email_error = $e->getCode();
    $success="error";
    $data=$e->getMessage();
}
    } else {
    $success = "error";
    $data = "The credit card request was denied '$error_string'.<br/>
    Please check for mis-typed data or use a different form of payment.";
    }
    logWrite($error_string);
    logWrite($response);
}


ajaxSuccess(array(
  "status"=>$success,
//  "url"=>$url,
  "data"=>$data,
  "trans"=>$transid,
 // "email"=>$email_msg,
  "email_error"=>$email_error
));
?>
