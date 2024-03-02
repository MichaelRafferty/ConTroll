<?php
/*  awsses.php - library of functions to use the Amazon Web Services Simple Email Services
 uses config variables:
 [email]
    type="aws"
    aws_access_key_id=...
    aws_secret_access_key=...
    username="arn:..."
    region = ...
    version= (string of which API version you are using)
*/


// send_email - exposed function send an email
//      $from = sender
//      $to = recepient
//      $cc = array of cc addresses
//      $subject = subject of the email
//      $textbody = text of email message for plain text
//      $htmlbody = email message in HTML format
//
// returns an associative array of status
//      status = success / error
//      email_error = error message (only if status = error)
//      error_code = error code returned by api
//

require_once (__DIR__ . "/db_functions.php");
require_once(__DIR__ . '/../Composer/vendor/autoload.php');
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

function send_email($from, $to, $cc, $subject, $textbody, $htmlbody) {
    $return_arr = array();

    $amazonCred = get_conf('email');
    try {
        $awsClient = SesClient::factory(array(
          'version'=>$amazonCred['version'],
          'region'=>$amazonCred['region'],
          'credentials' => array(
                  'key'=>$amazonCred['aws_access_key_id'],
                  'secret'=>$amazonCred['aws_secret_access_key']
                  )
          ));
    }
    catch (AwsException $e) {
        $return_arr['status'] = "error";
        $return_arr['error_code'] = $e->getCode();
        $return_arr['email_error'] = $e->getMessage();
        return $return_arr;
    }

    $Destination = array();
    if(is_array($to)) { $Destination['ToAddresses'] = $to; }
    else { $Destination['ToAddresses'] = array($to); }

    if (!is_null($cc)) {
        if (is_array($cc)) {
            $Destination['CcAddresses'] = $cc;
        } else {
            $Destination['CcAddresses'] = array($cc);
        }
    }

    $Body = array();
    if (!is_null($textbody)) {
    $Body['Text'] = array(
        'Data' => $textbody
        );
    }

    if (!is_null($htmlbody)) {
        $Body['Html'] = array(
            'Data' => $htmlbody
        );
    }

    try {
        $email_msg = $awsClient->sendEmail(
            array( // email start
            'Source' => $from,
            'Destination' => $Destination,
            'Message' => array( // message start
                'Subject' => array('Data' => $subject),
                'Body' => $Body
             ) // (message end)
            ) //(email end)
            ); // (sendEmail)
    }
    catch (AwsException $e) {
        $return_arr['status'] = "error";
        $return_arr['error_code'] = $e->getCode();
        $return_arr['email_error'] = $e->getMessage();
        return $return_arr;
    }
    $return_arr['status'] = "success";
    return $return_arr;
}
?>
