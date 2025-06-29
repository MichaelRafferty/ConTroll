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
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

// send_email - exposed function send an email
//      $from = sender
//      $to = recepient
//      $cc = array of cc addresses
//      $subject = subject of the email
//      $textbody = text of email message for plain text
//      $htmlbody = email message in HTML format
//      $attachments = array of files to attach (each element is an array of [f]ilepath, name, type]
//
// returns an associative array of status
//      status = success / error
//      email_error = error message (only if status = error)
//      error_code = error code returned by api
//

function send_email($from, $to, $cc, $subject, $textbody, $htmlbody, $attachments = NULL) {
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
            //web_error_log("dsn = '$dsn'");
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

    // clean up the to and cc address lists
    $rtn = redirectTestEmails($to, $cc); // returns array of to, cc, subjectPrefix
    $to = $rtn[0];
    $cc = $rtn[1];
    $subject = $rtn[2] . $subject;

    // now send the email
    try {
        $badEmailAddresses = [];
        $toCount = 0;
        $email = (new Email());
        // from
        $email->from($from);
        // to (single or array)
        if (is_array($to)) {
            $first = true;
            foreach ($to as $next) {
                if (!filter_var($next, FILTER_VALIDATE_EMAIL)) {
                    $badEmailAddresses[] = $next;
                    continue;
                }
                if ($first) {
                    $email->to($next);
                    $toCount++;
                    $first=false;
                } else {
                    $email->addTo($next);
                    $toCount++;
                }
            }
        } else {
            if (!filter_var($to, FILTER_VALIDATE_EMAIL))
                $badEmailAddresses[] = $to;
            else {
                $email->to($to);
                $toCount++;
            }
        }

        // cc (single or array)
        if ($cc !== null) {
            if (is_array($cc)) {
                $first = true;
                foreach ($cc as $next) {
                    if (!filter_var($next, FILTER_VALIDATE_EMAIL)) {
                        $badEmailAddresses[] = $next;
                        continue;
                    }
                    if ($first) {
                        $email->cc($next);
                        $first=false;
                    } else {
                        $email->addCc($next);
                    }
                }
            } else {
                if (!filter_var($cc, FILTER_VALIDATE_EMAIL))
                    $badEmailAddresses[] = $cc;
                else {
                    $email->cc($cc);
                }
            }
        }
        if (count($badEmailAddresses) > 0) {
            error_log("symfony send_email: received bad email addresses: " . implode(', ', $badEmailAddresses));
        }
        if ($toCount == 0) {
            $return_arr['status'] = 'error';
            $return_arr['error_code'] = 'invalid-emails';
            $return_arr['email_error'] = "Cannot send email because there was no valid email address, invalid email addresses: " .
                implode(', ', $badEmailAddresses);
            return $return_arr;
        }

        // subject
        $email->subject($subject);
        // text body
        $email->text($textbody);
        // html body
        if ($htmlbody !== null) {
            $email->html($htmlbody);
        }
        // add optional attachments
        if ($attachments !== null) {
            foreach ($attachments AS $file) {
                $email->addPart(new DataPart(new File($file[0]), $file[1], $file[2]));
            }
        }
        // now send it
        $mailer->send($email);

        if (count($badEmailAddresses) > 0) {
            $return_arr['status'] = 'warn';
            $return_arr['error_code'] = 'invalid-emails';
            $return_arr['email_error'] = 'Some email addresses were not used because they were invalid, invalid email addresses: '
                . implode(', ', $badEmailAddresses);
            return $return_arr;
        }
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
