<?php
/*  email_symfony.php - library of functions to use the Symfony Email Services (can do lots of transports
 uses config variables:
 [email]
    type="symfony"
    host=hostname[:port][?verify_peer=0][?local_domain=fqdn]
    transport=transport-type
    username=user
    password=pass

    transport="smtp" | "sendmail" | "ses+smtp" | "ses+https"
    for ses+https, user is access_key, pass is access_secret

*/


require_once (__DIR__ . "/db_functions.php");
require_once (__DIR__ . "/../Composer/vendor/autoload.php");

global $transport, $mailer, $emailconf;
$transport = null;
$mailer = null;
$emailconf = get_conf('email');

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

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

function send_email($from, $to, $cc, $subject, $textbody, $htmlbody) {
    global $transport, $mailer, $emailconf;
    $return_arr = array();

    if ($transport == null) {
        // Create a Transport object
        try {
            $dsn = $emailconf['transport'] . '://';
            if (array_key_exists('username', $emailconf)) {
                $dsn .= $emailconf['username'];
                if (array_key_exists('password', $emailconf)) {
                    $dsn .= ':' . urlencode($emailconf['password']);
                }
                $dsn .= '@';
            }

            $dsn .= $emailconf['host'];
            $transport = Transport::fromDsn($dsn);
            web_error_log("dsn = '$dsn'");
        }
        catch (TransportExceptionInterface $e) {
            $return_arr['status'] = "error";
            $return_arr['error_code'] = $e->getCode();
            $return_arr['email_error'] = $e->getMessage();
            web_error_log("symfony send_email transport create error:");
            var_error_log($return_arr);
            return $return_arr;
        }
    }

    if ($mailer == null) {
        // Create a Mailer object
        try {
            $mailer = new Mailer($transport);
        }
        catch (TransportExceptionInterface $e) {
            $return_arr['status'] = "error";
            $return_arr['error_code'] = $e->getCode();
            $return_arr['email_error'] = $e->getMessage();
            web_error_log("symfony send_email mailer create error:");
            var_error_log($return_arr);
            return $return_arr;
        }
    }

    // now send the email
    try {
        $email = (new Email());
        // from
        $email->from($from);
        // to (single or array)
        if (is_array($to)) {
            $first = true;
            foreach ($to as $next) {
                if ($first) {
                    $email->to($next);
                    $first=false;
                } else {
                    $email->addTo($next);
                }
            }
        } else {
            $email->to($to);
        }

        // cc (single or array)
        if ($cc !== null) {
            if (is_array($cc)) {
                $first = true;
                foreach ($cc as $next) {
                    if ($first) {
                        $email->cc($next);
                        $first=false;
                    } else {
                        $email->addCc($next);
                    }
                }
            } else {
                $email->cc($cc);
            }
        }
        // subject
        $email->subject($subject);
        // text body
        $email->text($textbody);
        // html body
        $email->html($htmlbody);
        // now send it
        $mailer->send($email);
    }
    catch (TransportExceptionInterface $e) {
        $return_arr['status'] = "error";
        $return_arr['error_code'] = $e->getCode();
        $return_arr['email_error'] = $e->getMessage();
        web_error_log("symfony send_email send error:");
        var_error_log($return_arr);
        return $return_arr;
    }
    $return_arr['status'] = "success";
    return $return_arr;
}
?>
