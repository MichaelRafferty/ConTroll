<?php

// refreshSession:  using the session variables, get the session to refresh via index.php

function refreshSession() {
    $type = getSessionVar('tokenType');
    echo "Refreshibng $type\n";

    switch($type) {
        case 'token':
            echo "working on login via email token<br/>\n";
            // send a refresh link email
            $message = sendEmailToken(getSessionVar('email'), true);
            if ($message) {
?>
        <div class="row">
            <div class="col-sm-12 bg-danger text-white"><?php echo $message; ?></div>
        </div>
<?php
            }
            echo "sent token<br/>\n";
            exit();

        case 'oauth2':
            echo "working on login via oauth2 token\n";
            exit();
    }
    echo "Unknown refresh request\n";
    exit();
}

function sendEmailToken($email, $refresh = false) {
    $portal_conf = get_conf('portal');
    $conf = get_conf('con');

    // send the email link to refresh the session;
    $requestType = $refresh ? 'session refresh' : 'login';
    $waittime = 5; // minutes
    $ts = timeSinceLastToken('login', $email);
    if ($ts != null && $ts < ($waittime * 60)) {
        $mins = $waittime - floor($ts/60);
        return("There already is an outstanding $requestType request to $email.<br/>" .
            "Please check your spam folder for the request.<br/>You will have to wait $mins minutes before trying again.");
    }

    // encrypt/decrypt stuff
    $cipherParams = getLoginCipher();
    $insQ = <<<EOS
INSERT INTO portalTokenLinks(email, action, source_ip)
VALUES(?, 'login', ?);
EOS;
    $insid = dbSafeInsert($insQ, 'ss', array($email, $_SERVER['REMOTE_ADDR']));
    if ($insid == false) {
        web_error_log('Error inserting tracking ID for email link');
    }

    $parms = array();
    $parms['email'] = $email;       // address to verify via email
    $parms['type'] = 'token-resp';  // verify type
    $parms['ts'] = time();          // when requested for timeout check
    $parms['lid'] = $insid;         // id in portalTokenLinks table

    $id = getSessionVar('id');
    if ($id != null && $refresh) {
        // this is a refresh of an already logged in session
        $parms['id'] = $id;
        $parms['idType'] = getSessionVar('idType');
        $parms['email_addr'] = $email;
        $parms['refresh'] = 1;
    }
    $string = json_encode($parms);  // convert object to json for making a string out of it, which is encrypted in the next line
    $string = urlencode(openssl_encrypt($string, $cipherParams['cipher'], $cipherParams['key'], 0, $cipherParams['iv']));
    $token = $portal_conf['portalsite'] . "/index.php?vid=$string";     // convert to link for emailing
    load_email_procs();
    if ($refresh) {
        $body = 'Here is the session refresh link for the ' . $conf['label'] . ' Membership Portal.' . PHP_EOL . PHP_EOL . $token . PHP_EOL .
            PHP_EOL .
            'Click the link to re-verify your email address' . PHP_EOL;
        $htmlbody = '<p>Here is the refresh link for the ' . $conf['label'] . ' Membership Portal.</p><p><a href="' . $token . '">' .
            'Click this link to re-verify your email address' . '</a></p>' . PHP_EOL;
    } else {
        $body = 'Here is the login link you requested for the ' . $conf['label'] . ' Membership Portal.' . PHP_EOL . PHP_EOL . $token . PHP_EOL . PHP_EOL .
            'Click the link to verify your email address' . PHP_EOL;
        $htmlbody = '<p>Here is the login link you requested for the ' . $conf['label'] . ' Membership Portal.</p><p><a href="' . $token . '">' .
            'Click this link to verify your email address' . '</a></p>' . PHP_EOL;
    }

    $return_arr = send_email($conf['regadminemail'], trim($email), /* cc */ null, $conf['label'] . ' Membership Portal Login Link', $body, $htmlbody);
    if (array_key_exists('error_code', $return_arr)) {
        $error_code = $return_arr['error_code'];
    } else {
        $error_code = null;
    }
    if (array_key_exists('email_error', $return_arr)) {
        return('Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error-code');
    }
    return null;
}

// timeSinceLastToken - how many seconds since the last token send for this reason to this email - to avoid flooding
    function timeSinceLastToken($action, $email) {
        $cQ = <<<EOS
SELECT MIN(TIMESTAMPDIFF(SECOND,createdTS,NOW())) AS TS
FROM portalTokenLinks
WHERE action = ? and email = ? AND useCnt = 0;
EOS;
        $cR = dbSafeQuery($cQ, 'ss', array($action, $email));
        if ($cR === false || $cR->num_rows == 0) {
            return null;
        }

        $seconds = $cR->fetch_row()[0];
        $cR->free();
        return $seconds;
    }