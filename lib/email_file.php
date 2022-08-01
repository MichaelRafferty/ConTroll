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

function send_email($from, $to, $cc, $subject, $textbody, $htmlbody) {
    $headers = "From: $from" . "\n"
        . "Reply-To: $from" . "\n"
        . "X-Mailer: PHP/" . phpversion() . "\n";
    if (!is_null($cc)) {
        if (is_array($cc)) {
            $cclist = implode(";", $cc);
        } else {
            $cclist = $cc;
        }
        $headers .= "\nCc:" . $cclist;
    }

    $return_arr = array();

    // for complaint MTA emails, replace all \n with \r\n, but first to not duplicate the \r\n to \r\r\n make all \r\n to \n.

    $emailconf = get_conf('email');
    $path = $emailconf['dir'] . '/' . str_replace(';', '', $to) . '_' . time() . '.txt';
    $email = fopen($path, 'w');
    if ($email === false) {
        $return_arr['status'] = "error";
        $return_arr['email_error'] = "There was a problem opening the file $path to save the email.";
    } else {
        fwrite($email, "To: " . $to . "\n");
        fwrite($email, $headers);
        fwrite($email, "Subject: " . $subject . "\n\n");
        fwrite($email, $textbody);
        $return_arr['status'] = "success";
    }

    return $return_arr;
}

?>
