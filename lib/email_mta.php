<?php
//  mta.php - library of functions to use the local MTA via PHP to send emails
// uses config variables:
// [email]
//    type="mta"

// email_send - exposed function send an email
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
//

// TODO: convert this to PHP mailer class, so it can easily do text and html
/*  (example of using php mailer class)
$mail = new PHPMailer();

$mail->IsHTML(true);
$mail->CharSet = 'text/html; charset=UTF-8;';
$mail->IsSMTP();

$mail->WordWrap = 80;
$mail->Host = 'smtp.thehost.com';
$mail->SMTPAuth = false;

$mail->From = $from;
$mail->FromName = $from; // First name, last name
$mail->AddAddress($to, 'First name last name');
#$mail->AddReplyTo("reply@thehost.com", "Reply to address");

$mail->Subject = $subject;
$mail->Body = $htmlMessage;
$mail->AltBody = $textMessage;    # This automatically sets the email to multipart/alternative. This body can be read by mail clients that do not have HTML email capability such as mutt.

if (!$mail->Send()) {
    throw new Exception('Mailer Error: ' . $mail->ErrorInfo);
}
*/

function send_email($from, $to, $cc, $subject, $textbody, $htmlbody) {
    $headers = "From: $from" . "\r\n"
        . "Reply-To: $from" . "\r\n"
        . "X-Mailer: PHP/" . phpversion();
    if (!is_null($cc)) {
        if (is_array($cc)) {
            $cclist = implode(";", $cc);
        } else {
            $cclist = $cc;
        }
        $headers .= "\r\nCc:" . $cclist;
    }

    $return_arr = array();

    // for complaint MTA emails, replace all \n with \r\n, but first to not duplicate the \r\n to \r\r\n make all \r\n to \n.
    $email_result = mail($to, $subject, str_replace("\n", "\r\n", str_replace("\r\n", "\n", $textbody)), $headers);

    if ($email_result) {
        $return_arr['status'] = "success";
    } else {
        $return_arr['status'] = "error";
        $return_arr['email_error'] = "There was a problem sending the email.";
    }

    return $return_arr;
}
?>
