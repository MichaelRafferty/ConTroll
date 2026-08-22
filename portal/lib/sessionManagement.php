<?php
// refreshSession:  using the session variables, get the session to refresh via index.php

function refreshSession() {
    $type = getSessionVar('tokenType');
    echo "Refreshing $type\n";

    switch($type) {
        case 'token':
            //echo "working on login via email token<br/>\n";
            // send a refresh link email
            $message = sendEmailToken(getSessionVar('email'), true);
            if ($message) {
?>
        <div class="row">
            <div class="col-sm-12 bg-danger text-white"><?php echo $message; ?></div>
        </div>
<?php
            } else {
?>
        <div class='row'>
            <div class='col-sm-12 bg-success text-white'>Your session has expired, a refresh session email has been sent.
                Please check your inbox and click on the refresh link.
            </div>
        </div>
<?php
            }
            exit();

        case 'oauth2':
            $provider = getSessionVar('oauth2');
            $redirect = getConfValue('portal', 'portalsite') . "?oauth2=$provider&refresh";
?>
            <div class="row">
                <div class='col-sm-12 bg-success text-white'>
                    Your session has expired, you are being redirected to $provider to refresh your session.
                </div>
            </div>
            <script type='text/javascript'>
        window.location = "<?php echo $redirect; ?>";
    </script>
<?php
            exit();

        case 'passkey':
            $redirect = getConfValue('portal', 'portalsite') . "?passkey&refresh";
?>
            <div class="row">
                <div class='col-sm-12 bg-success text-white'>
                    Your session has expired, you are being redirected to refresh your passkey based session.
                </div>
            </div>
            <script type='text/javascript'>
                window.location = "<?php echo $redirect; ?>";
            </script>
            <?php
            exit();

    }
    echo "Unknown refresh request\n";
    exit();
}

function sendEmailToken($email, $refresh = false) {
    $portal_conf = get_conf('portal');

    // send the email link to refresh the session;
    $requestType = $refresh ? 'session refresh' : 'login';
    $waittime = 5; // minutes
    $ts = timeSinceLastToken('login', $email);
    if ($ts != null && $ts < ($waittime * 60)) {
        $mins = $waittime - floor($ts/60);
        return("There already is an outstanding $requestType request to $email.<br/>" .
            "Please check your spam folder for the request.<br/>You will have to wait $mins minutes before trying again.");
    }

    $insQ = <<<EOS
INSERT INTO portalTokenLinks(email, action, source_ip)
VALUES(?, 'login', ?);
EOS;
    $insid = dbSafeInsert($insQ, 'ss', array($email, $_SERVER['REMOTE_ADDR']));
    if ($insid === false) {
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
    $string = encryptCipher($string, true);
    $token = getConfValue('portal', 'portalsite', '') . "/index.php?vid=$string";     // convert to link for emailing
    load_email_procs();
    $label = getConfValue('con', 'label', 'the convention');
    $reply = getConfValue('con', 'regadminemail', null);
    if ($refresh) {
        $body = <<<EOS
You are receiving this email because your session is about to expire for $label's Membership Portal.
When you logged in you used the "Create Account or Login with Email Authentication" button to verify your email address.
Every few hours we need to verify that you are still logged in and using the email address you used to access the portal.
When you click the link below you will be re-verified and your session will be refreshed.
  
This link will expire in one hour.  If you do not refresh your session before it expires, you will need to log in again to start a new session.

Here is the session refresh link for the $label Membership Portal.

$token

Click the refresh link to re-verify your email address and continue with the session.

If you have any problems, or have any questions, please contact $reply.

Thank you for your interest in $label.

This email was sent by ConTroll™, the registration system for $label.
EOS;
        $htmlbody = <<<EOS
<p>You are receiving this email because your session is about to expire for $label's Membership Portal.
When you logged in you used the "Create Account or Login with Email Authentication" button to verify your email address.
Every few hours we need to verify that you are still logged in and using the email address you used to access the portal.
When you click the link below you will be re-verified and your session will be refreshed.</p>
</p>This link will expire in one hour.
If you do not refresh your session before it expires, you will need to log in again to start a new session.</p>
<p><a href="$token">Click this link to refresh your session for the $label Membership Portal.</a></p>
<p>Click the refresh link to re-verify your email address and continue with the session.</p>
<p>If you have any problems, or have any questions, please contact $reply.</p>
<p>Thank you for your interest in $label.</p>
<p>This email was sent by ConTroll™, the registration system for $label.</p>
EOS;
    } else {
        $body = <<<EOS
You are receiving this email because used the "Create Account or Login with Email Authentication" button to login in to $label's Membership Portal.
  
This link will expire in one hour. If you do not use the link to login to the portal, you will need to request a new link.

$token

Click the login link to verify your email address and continue to the $label Membership Portal.

Once logged in, you can use the "Create Passkey" button to make your sign in process simplier and avoid the email token method entirely.
Then in the future you can login using the "Login with Passkey" button and login securely with your Passkey.

If you did not request this login link, please ignore this email as someone else typed in your email address.
This login validation email will expire within the hour if unused.

If you have any problems, or have any questions, please contact $reply.

Thank you for your interest in $label.

This email was sent by ConTroll™, the registration system for $label.
EOS;
        $htmlbody =  <<<EOS
<p>You are receiving this email because used the "Create Account or Login with Email Authentication" button to login in to $label's Membership Portal.</p>
<p>This link will expire in one hour. If you do not use the link to login to the portal, you will need to request a new link.</p>
<p><a href="$token">Click this link to verify your email address and login to the $label Membership Portal.</a></p>
<p>Once logged in, you can use the "Create Passkey" button to make your sign in process simplier and avoid the email token method entirely.
Then in the future you can login using the "Login with Passkey" button and login securely with your Passkey.</p>
<p>If you did not request this login link, please ignore this email as someone else typed in your email address.
This login validation email will expire within the hour if unused.</p>
<p>If you have any problems, or have any questions, please contact $reply.</p>
<p>Thank you for your interest in $label.</p>
<p>This email was sent by ConTroll™, the registration system for $label.</p>
EOS;
    }

    $return_arr = send_email($reply, trim($email), /* cc */ null, $label . ' Membership Portal Login Link', $body, $htmlbody);
    if (array_key_exists('error_code', $return_arr)) {
        $error_code = $return_arr['error_code'];
    } else {
        $error_code = null;
    }
    if (array_key_exists('email_error', $return_arr)) {
        return('Unable to send receipt email, error: ' . $return_arr['email_error'] . ', Code: $error_code');
    }
    return null;
}

// updateSubscriberId - update the subsccriber id in the identites area and if necessary create it
    function updateSubscriberId($provider, $email, $subscriberId) {
        // first check if this entry already exists
        $uQ = <<<EOS
UPDATE perinfoIdentities
SET email_addr = ?
WHERE provider = ? AND subscriberID = ?;
EOS;
       $num_upd = dbSafeCmd($uQ, 'sss', array($email, $provider, $subscriberId));

       $uQ = <<<EOS
UPDATE perinfoIdentities
SET subscriberID = ?
WHERE provider = ? AND email_addr = ? AND subscriberID IS NULL;
EOS;

        $num_upd = dbSafeCmd($uQ, 'sss', array($subscriberId, $provider, $email));
    }

    // updateIdentityUsage - create or update the identity and set it's last used date, subscriber id if needed, and the use count
    function updateIdentityUsage($id, $provider, $email) {
        $iQ = <<<EOS
SELECT *
FROM perinfoIdentities
WHERE perid = ? AND provider = ? AND email_addr = ?;
EOS;
        $iR = dbSafeQuery($iQ, 'iss', array($id, $provider, $email));
        if ($iR == false) {
            web_error_log('Error inserting finding identity for ' . $provider);
            return;
        }

        if ($iR->num_rows == 1) {
            $identity = $iR->fetch_assoc();
            $iR->free();
            $uQ = <<<EOS
UPDATE perinfoIdentities
SET 
    lastUseTs = current_timestamp(), useCount = useCount + 1
WHERE perid = ? AND provider = ? AND email_addr = ?; 
EOS;
            $num_upd = dbSafeCmd($uQ, 'iss', array($id, $provider, $email));
        } else {
            $iR->free();
            // it doesn't exist, create it
            $cQ = <<<EOS
INSERT INTO perinfoIdentities(perid, provider, email_addr, subscriberID, lastUseTS, useCount)
VALUES (?,?,?,?,current_timestamp(),1);
EOS;
            $num_uod = dbSafeInsert($cQ, 'isss', array($id, $provider, $email, getSessionVar('subscriberId')));
        }
    }
