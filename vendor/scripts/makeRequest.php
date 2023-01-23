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


session_start();
db_connect();

$vendor = $_SESSION['id'];
$vendor_ini = get_conf('vendor');
$response = array("id"=>$vendor, "post" => $_POST, "get" => $_GET);


$amazonCred = get_conf('email');
$awsClient = SesClient::factory(array(
    'key'=>$amazonCred['aws_access_key_id'],
    'secret'=>$amazonCred['aws_secret_access_key'],
    'region'=>'us-east-1',
    'version'=>'2010-12-01'
));

if(!isset($vendor) || !isset($_POST) || !isset($_POST['access'])) {
    $response['status'] = 'error';
    $response['message'] = 'No Data Provided';
    ajaxSuccess($response);
    exit();
}

switch($_POST['access']) {
    
    case "artshow":
        $updateq = "UPDATE vendors SET request_artshow=true WHERE id=$vendor";
        dbQuery($updateq);
        $vendorInfoQ = "SELECT email FROM vendors where id=$vendor;";
        $vendorInfo = fetch_safe_assoc(dbQuery($vendorInfoQ));
        $response['dest'] = $vendor_ini['artshow'];

        
        try {
            $email = $awsClient->sendEmail(array(
                'Source' => 'regadmin@balticon.org',
                'Destination' => array(
                    'ToAddresses' => array($vendor_ini['alley'], $vendor_ini['dealer'])
                ),
                'Message' => array(
                    'Subject' => array(
                        'Data' => 'New Vendor Request'
                    ),
                    'Body' => array(
                        'Text' => array(
                            'Data' => request($vendor)
                        )
                    )
                )));
            $response['status'] = 'successs';
        } catch (AwsException $e) {
            $email = $e.getAwsErrorType();
            $email_error = $e.getAwsErrorCode();
            $response['status'] = 'error';
            $response['message'] = "There has been a problem sending the notification email.  Please email the lead for your area to requst access.";
        }

        ajaxSuccess($response);
        break;
    default:
        $response['status'] = 'error';
        $response['message'] = 'Bad Access Request';
        ajaxSuccess($response);
        exit();
}

ajaxSuccess($response);
?>
