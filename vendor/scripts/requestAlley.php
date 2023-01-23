<?php
if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    exit();
}

require_once "../lib/ajax_functions.php";
require_once "../lib/db_functions.php";


require_once "../lib/email.php";
require_once "../../../aws-api/aws-autoloader.php";

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;


$response = array("post" => $_POST, "get" => $_GET);
session_start();
db_connect();

global $con;

$vendor = $_SESSION['id'];
$con = get_con();
$conid=$con['id'];

$response['conid'] = $conid;
$info = get_conf('vendor');

$itemCheckQ = "SELECT total, registered, max_per FROM vendor_reg WHERE conid=$conid and type='alley';";

$itemCheckR = dbQuery($itemCheckQ);
$itemCheck = fetch_safe_assoc($itemCheckR);

$requested = sql_safe($_POST['alley_tables']);

if($requested > $itemCheck['max_per']) { $requested = $itemCheck['max_per']; }
if($itemCheck['registered'] + $requested > $itemCheck['total']) {
    $requested = $itemCheck['total'] - $itemCheck['registered'];
}

$response['alley'] = $requested;

$v_update = "UPDATE vendors SET request_artistalley=true WHERE id=$vendor;";
$req_insert = "INSERT IGNORE INTO vendor_show (vendor, conid, type, requested)"
    . " VALUES ($vendor, $conid, 'alley', $requested);";

dbQuery($v_update); dbQuery($req_insert);


$v_query = "SELECT email FROM vendors where id=$vendor;";
$v_email = fetch_safe_assoc(dbQuery($v_query))['email'];

$response['email']=$v_email;


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
      'Source' => 'regadmin@bsfs.org',
      'Destination' => array(
        'ToAddresses' => array($v_email, $info['alley'])
      ),
      'Message' => array(
        'Subject' => array(
          'Data' => "Artist Alley Request"
        ),
        'Body' => array(
          'Text' => array(
            'Data' => request('Artist Alley', $vendor)
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

ajaxSuccess($response);
?>
