<?php
// Registration  Portal - index.php - Login/re-login page for the membership portal
require_once("lib/base.php");
require_once("lib/getLoginMatch.php");
require_once("lib/loginItems.php");
require_once("lib/sessionManagement.php");
require_once('../lib/portalForms.php');
require_once("../lib/profile.php");
require_once("../lib/policies.php");
require_once("../lib/googleOauth2.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$condata = get_con();

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = $debug['portal'];
$config_vars['uri'] = $portal_conf['portalsite'];
$config_vars['required'] = getConfValue('reg', 'required', 'addr');
$loginId = null;
$loginType = null;
$purpose = "From here you can create and manage your membership account.";
$why = "continue to the Portal.";

// first lets check the authentication stuff. but only if not loging out
    // in session or not, is it a logout? (force clear session method, as well as logout)
    if (isset($_REQUEST['logout'])) {
        clearSession();
        header('location:' . $portal_conf['portalsite']);
        exit();
    }
    // oauth= indicates an authentication request from the ConTroll Oauth2 server via redirect
    if (isset($_REQUEST['oauth'])) {
        // decrypt the request
        $request = decryptCipher($_GET['oauth'], true);
        if ($request == null) {
            web_error_log("Invalid oauth server request received");
            if (isSessionVar('id')) {
                // there is a valid portal session, send them to the portal with an error message to display
                header('location:portal.php?type=e&messageFwd=' .
                       urlencode('Invalid authentication request received from the Oauth2 Server, please seek assistance.'));
                exit();
            } else {
                // no valid session found, draw the login page
                draw_indexPageTop($condata, $purpose);
                draw_login($config_vars,
                           'Invalid authentication request received from the Oauth2 Server, please seek assistance.', 'bg-danger text-white',
                           $why);
                exit();
            }
        }

        // we have a decrypted valid request, put it in the session, so when we come back from the oauth2 server or email validation we can deal with it
        setSessionVar('oauth', $request);
        $purpose = '<strong>' . $request['app'] .
            " has requested that you validate yourself.  Please log into the Portal to perform that validation.</strong>";
        $why = "perform the authentication for " . $request['app'];
        if (isSessionVar('id')) {
            chooseAccountFromEmail(getSessionVar('email'), null, null, null, 'logged-in');
        }
    }

    $refresh = isset($_REQUEST['refresh']) && isSessionVar('id');

    // oauth2= indicates a new account login via oAUTH2 or the selected account is re-verifying, clear the old information,
    //  unless the GET variable of 'refresh' is found
    if (isset($_REQUEST['oauth2'])) {
        if ($refresh) {
            // just refresh the token
            setSessionVar('sessionEmail', getSessionVar('email'));
            clearSession('oauth2');
        } else {
            // no update of token, force it to be a logout, but keep oauth variable if set
            $oauth = getSessionVar('oauth');
            clearSession();
            if ($oauth != null) setSessionVar('oauth', $oauth);
        }

        if (!isSessionVar('oauth2pass')) {
            setSessionVar('oauth2', $_REQUEST['oauth2']);
            setSessionVar('oauth2pass', 'setup');
        }
    }

    // are we in an OAUTH2 session, and if so, is it yet complete or needs the next exchange?
    $oauth2pass = getSessionVar('oauth2pass');
    if ($oauth2pass != null && $oauth2pass != 'token') {
        // is this session validation taking too long?
        $oauth2timeout = getSessionVar('oauth2timeout');
        if ($oauth2timeout == null) {  // no timeout set one
            $oauth2timeout = time() + 5 * 60;
            setSessionVar('oauth2timeout', $oauth2timeout);
        }
        if (time() > $oauth2timeout) {
            clearSession('oauth2'); // end the validation loop
            header('location:' . $portal_conf['portalsite']);
            draw_indexPageTop($condata, $purpose);
            draw_login($config_vars,
                       'Login Authentication took too long, please try again.', 'bg-danger text-white',
                       $why);
            exit();
        }
        else {
            // ok, we are in the process of an oauth2 sequence, continue it until returns the token
            $redirectURI = $portal_conf['redirect_base'];
            if ($redirectURI == '')
                $redirectURI = null;
            $oauthParams = null;
            switch (getSessionVar('oauth2')) {
                case 'google':
                    $oauthParams = googleAuth($redirectURI);
                    if (isset($oauthParams['error'])) {
                        web_error_log($oauthParams['error']);
                        clearSession('oauth2');
                        draw_indexPageTop($condata, $purpose);
                        draw_login($config_vars, $oauthParams['error'], 'bg-danger text-white', $why);
                        exit();
                    }

            }

            if ($oauthParams == null) {
                // an error occured with login by google
                draw_indexPageTop($condata, $purpose);
                draw_login($config_vars,
                           'An error occured with the login with ' . getSessionVar('oauth2'), 'bg-danger text-white',
                           $why);
                clearSession('oauth2');
                exit();
            }
            if (!isset($oauthParams['email'])) {
                web_error_log('no oauth2 email found');
                draw_indexPageTop($condata, $purpose);
                draw_login($config_vars,
                           getSessionVar('oauth2') . " did not return an email address.", 'bg-warning',
                           $why);
                clearSession('oauth2');
                exit();
            }

            $email = strtolower($oauthParams['email']);
            // if this is a refresh, check that it returned the same email address
            $oldemail = strtolower(getSessionVar('sessionEmail'));
            if ($oldemail != null && $oldemail != $email) {
                // this is a change in email address, treat this as a new login.
                // first save off the oauth2 session variables
                $oauth2 = getSessionVar('oauth2');
                $oauth2pass = getSessionVar('oauth2pass');
                $oauth2state = getSessionVar('oauth2state');
                // now clear the session to log the old session out
                // save the old oauth authentication requestparameter to restore it here
                $oauth = getSessionVar('oauth');
                clearSession();
                $oldemail = null;
                // now restore those
                if ($oauth != null) setSessionVar('oauth', $oauth);
                if ($oauth2 != null) setSessionVar('oauth2', $oauth2);
                if ($oauth2pass != null) setSessionVar('oauth2pass', $oauth2pass);
                if ($oauth2state != null) setSessionVar('oauth2state', $oauth2state);
            }

            clearSession("oauth2timeout");  // reset the timeout
            setSessionVar('email', $email);
            setSessionVar('displayName', $oauthParams['displayName']);
            setSessionVar('firstName', $oauthParams['firstName']);
            setSessionVar('lastName', $oauthParams['lastName']);
            setSessionVar('avatarURL', $oauthParams['avatarURL']);
            setSessionVar('subscriberId', $oauthParams['subscriberId']);
            setSessionVar('tokenType', 'oauth2');
            updateSubscriberId(getSessionVar('oauth2'), $email, $oauthParams['subscriberId']);

            if (array_key_exists('oauthhrs', $portal_conf)) {
                $hrs = $portal_conf['oauthhrs'];
            }
            else {
                $hrs = 8;
            }
            if ($hrs == null || !is_numeric($hrs) || $hrs < 1) $hrs = 8;
            setSessionVar('tokenExpiration', time() + ($hrs * 3600));

            if ($oldemail != null) {
                // this is a refresh, don't choose the account again, just return to the home page of the portal or return the authentication response,
                // don't disturb any other session variables
                validationComplete(getSessionVar('id'), getSessionVar('idType'), getSessionVar('email'), getSessionVar('idSource'), getSessionVar('multiple'));
            }

            draw_indexPageTop($condata, $purpose);
            // not a refresh, choose the account from the email
            $account = chooseAccountFromEmail($email, null, null, null, getSessionVar('oauth2'));
            if ($account == null || !is_numeric($account)) {
                if ($account == null) {
                    $account = "Error looking up data for $email";
                }
                clearSession('oauth2');
                draw_login($config_vars, $account, 'bg-danger text-white', $why);
            }
            exit();
        }
    }

if (isSessionVar('id')) {
    // In a session, just set the id and type
    $loginType = getSessionVar('idType');
    $loginId = getSessionVar('id');
    if (isset($_GET['vid'])) {
        // we are logged in and took a vid link, if it decodes, log out and reload the page to reprocess the link
        $match = decryptCipher($_GET['vid'], true);
        if ($match != null) { // vid decodes, log us out
            $oldEmail = strtolower(getSessionVar('email'));
            if (array_key_exists('id', $match)) {
                $email = strtolower($match['email_addr']);
            } else {
                $email = strtolower($match['email']);
            }
            if ($email != $oldEmail && isSessionVar('oauth') == false) { // treat this as a logout and try it again
                $oauth = getSessionVar('oauth');
                clearSession();
                setSessionVar('oauth', $oauth);
            } else {
                if (array_key_exists('id', $match) && $loginId != $match['id']) {
                    // this is a switch account request
                    if (array_key_exists('banned', $match) && $match['banned'] != 'N') {
                        header('location:portal.php?type=e&messageFwd=' .
                               urlencode("There is an issue with that account, please contact registration at " .
                                         $con['regadminemail'] . ' for assistance.'));
                        exit();
                    }
                    if (array_key_exists('issue', $match) && $match['issue'] != 'N') {
                        header('location:portal.php?type=e&messageFwd=' .
                               urlencode('There is an issue with that account, please contact registration at ' .
                                         $con['regadminemail'] . ' for assistance.'));
                        exit();
                    }
                }
                if (isSessionVar('oauth') == false) {
                    $refresh = true;
                    if (array_key_exists('emailhrs', $portal_conf)) {
                        $hrs = $portal_conf['emailhrs'];
                    }
                    else {
                        $hrs = 24;
                    }
                    if (array_key_exists('multiple', $match)) {
                        setSessionVar('multiple', $match['multiple']);
                    }
                    if ($hrs == null || !is_numeric($hrs) || $hrs < 1) $hrs = 24;
                    setSessionVar('tokenExpiration', time() + ($hrs * 3600));
                }
                //  if no id in match, it's re-using a login token for the same account currently logged in, as the email match would have
                //      handled logging them out)
                $id = null;
                $tablename = null;
                if (array_key_exists('id', $match)) {
                    $id = $match['id'];
                    $tablename = $match['tablename'];
                }
                validationComplete($id, $tablename, $email, getSessionVar('idSource'), getSessionVar('multiple'));
                exit();
            }
        }
    }
}

draw_indexPageTop($condata, $purpose);

if (isset($_GET['vid'])) {
    // handle link login
    $match = decryptCipher($_GET['vid'], true);
    if ($match == null) {   // invalid vid link
        draw_login($config_vars,
                   "The link is invalid, please request a new link", 'bg-danger text-white',
                   $why);
        exit();
    }
    $linkid = $match['lid'];
    if (array_key_exists('id', $match)) {
        $email = strtolower($match['email_addr']);
        $id = $match['id'];
    }
    else {
        $email = strtolower($match['email']);
        $id = null;
    }
    if (array_key_exists('validationType', $match)) {
        $validationType = $match['validationType'];
        if ($match['validationType'] != 'token' && $match['validationType'] != 'switched') {
            if ($match['validationType'] != getSessionVar('oauth2') || $email != getSessionVar('email')) {
                draw_login($config_vars, "The link is invalid", 'bg-danger text-white',
                           $why);
                exit();
            }
        }
    }
    else {
        $validationType = 'token';
    }
    $timediff = time() - $match['ts'];
    web_error_log('login @ ' . time() . ' with ts ' . $match['ts'] . " for $email/$id via $validationType");
    if ($timediff > (4 * 3600)) {
        draw_login($config_vars,
                   "The link has expired, please request a new link",  'bg-danger text-white',
                   $why);
        exit();
    }

    if ($validationType == 'token' || $validationType == 'switched') {
        if ($validationType == 'token') {
            // check if the link has been used
            $linkQ = <<<EOS
SELECT id, LOWER(email) AS email, useCnt
FROM portalTokenLinks
WHERE id = ? AND action = 'login'
ORDER BY createdTS DESC;
EOS;
            $linkR = dbSafeQuery($linkQ, 's', array ($linkid));
            if ($linkR == false || $linkR->num_rows != 1) {
                draw_login($config_vars,
                    "The link is invalid, please request a new link", 'bg-danger text-white',
                    $why);
                exit();
            }
            $linkL = $linkR->fetch_assoc();
            if ($linkL['email'] != $email) {
                // mismatch, check to see if it's one of the perinfo identity emails
                $piQ = <<<EOS
SELECT i.perid, i.email_addr AS iEmail, p.email_addr AS pEmail
FROM perinfoIdentities i
JOIN perinfo p ON i.perid = p.id
WHERE i.email_addr = ? AND p.email_addr = ?;
EOS;
                $piR = dbSafeQuery($piQ, 'ss', array ($linkL['email'], $email));
                if ($piR === false || $piR->num_rows == 0) {
                    draw_login($config_vars,
                        "The link is invalid, please request a new link", 'bg-danger text-white', $why);
                    exit();
                }

                $possibleIDs = [];
                while ($pid = $piR->fetch_assoc()) {
                    $possibleIDs[] = $pid;
                }
                $piR->free();
            }

            if ($linkL['useCnt'] > 100) {
                draw_login($config_vars,
                    "The link has already been used, please request a new link", 'bg-danger text-white',
                    $why);
                exit();
            }

            // mark link as used
            $updQ = <<<EOS
        UPDATE portalTokenLinks
        SET useCnt = useCnt + 1, useIP = ?, useTS = NOW()
        WHERE id = ?;
        EOS;
            $updcnt = dbSafeCmd($updQ, 'si', array ($_SERVER['REMOTE_ADDR'], $linkid));
            if ($updcnt != 1) {
                web_error_log("Error updating link $linkid as used");
            }
        }

        // set expiration for email
        if (array_key_exists('emailhrs', $portal_conf)) {
            $hrs = $portal_conf['emailhrs'];
        }
        else {
            $hrs = 24;
        }
        $tokenType = 'token';
    } else {
        if (array_key_exists('oauthhrs', $portal_conf)) {
            $hrs = $portal_conf['emailhrs'];
        }
        else {
            $hrs = 8;
        }
        $tokenType = getSessionVar('oauth2');
    }
    if ($hrs == null || !is_numeric($hrs) || $hrs < 1) $hrs = 24;
    setSessionVar('tokenExpiration', time() + ($hrs * 3600));
    setSessionVar('email', $email);
    setSessionVar('tokenType', $tokenType);

    // now choose the account from the email
    $account = chooseAccountFromEmail($email, $id, $linkid, $match, $validationType);
    if ($account == null || !is_numeric($account)) {
        if ($account == null) {
            $account = "Error looking up data for $email";
        }
        $oauth = getSessionVar('oauth');
        clearSession(); // force a logout
        if ($oauth != null) setSessionVar('oauth', $oauth);
        draw_login($config_vars, $account, 'bg-danger text-white', $why);
    }
    exit();
} else if ($loginId != null && isSessionVar('multiple') && isset($_REQUEST['switch']) && $_REQUEST['switch'] == 'account') {
    $account = chooseAccountFromEmail(getSessionVar('multiple'), null,null, null, 'switch');
    if ($account == null || !is_numeric($account)) {
        if ($account == null) {
            $account = "Error looking up data for $email";
        }
        $oauth = getSessionVar('oauth');
        clearSession(); // force a logout
        if ($oauth != null) setSessionVar('oauth', $oauth);
        outputCustomText('main/notloggedin');
        draw_login($config_vars, $account, 'bg-danger text-white', $why);
    }
    exit();
} else if ($loginId == null) {
    outputCustomText('main/notloggedin');
    draw_login($config_vars, null, null, $why);
    exit();
}
?>
    <script type='text/javascript'>
        window.location = "<?php echo $portal_conf['portalsite'] . '/portal.php' ?>";
    </script>
<?php
function draw_indexPageTop($condata, $purpose) {
    $con = get_conf('con');
    $conid = $con['id'];
    $portal_conf = get_conf('portal');
    $ini = get_conf('reg');

    index_page_init($condata['label'] . ' Membership Portal');
    echo <<<EOS
<body id="membershipPortalBody">
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 p-0">
EOS;
    if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') {
        if (array_key_exists('logoalt', $ini)) {
            $altstring = $ini['logoalt'];
        } else {
            $altstring = 'Logo';
        }
        echo '<img class="img-fluid" src="images/' . $ini['logoimage'] . '"' . " alt='$altstring'/>\n";
    }
    if (array_key_exists('logotext', $ini) && $ini['logotext'] != '') {
        echo $ini['logotext'];
    }
    echo <<<EOS
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 mt-2">
            <h1>Membership Portal</h1>
        </div>
    </div>
EOS;

    if (getConfValue('portal', 'open') != 1) { ?>
        <p class='text-primary'>The membership portal is currently closed. Please check the website to determine when it will open or try again
            tomorrow.</p>
        <?php
        exit;
    }
    if (getConfValue('portal','suspended') == 1) { ?>
<p class="text-primary">
<?php
        echo $con['conname'] ." has temporarily suspended the registration portal " . getConfValue('portal','suspendreason');
?>
</p>
    <?php
        exit;
    }

    $label = $con['label'];
    echo <<<EOS
    <div class="row p-1">
        <div class="col-sm-auto">
            Welcome to the $label Membership Portal.
        </div>
    </div>
    <div class="row p-1">
        <div class="col-sm-12">
            $purpose
        </div>
    </div>
EOS;
    if (getConfValue('portal', 'test') == 1) {
        echo <<<EOS
        <div class="row">
            <div class="col-sm-12">
                <h2 class='warn'>This Page is for test purposes only</h2>
            </div>
        </div>
EOS;
    }
    outputCustomText('main/top');
}