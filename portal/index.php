<?php
// Registration  Portal - index.php - Login/re-login page for the membership portal
require_once("lib/base.php");
require_once("lib/getLoginMatch.php");
require_once("lib/loginItems.php");
require_once("lib/portalForms.php");
require_once("lib/sessionManagement.php");
require_once("../lib/cipher.php");
require_once("../lib/policies.php");
require_once("../lib/googleOauth2.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$condata = get_con();

// encrypt/decrypt stuff (maybe needed?)
$cipherInfo = getLoginCipher();

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = $debug['portal'];
$config_vars['uri'] = $portal_conf['portalsite'];
$config_vars['required'] = $ini['required'];
$loginId = null;
$loginType = null;

// first lets check the Oauth2 stuff. but only if not loging out
    // in session or not, is it a logout? (force clear session method, as well as logout)
    if (isset($_REQUEST['logout'])) {
        clearSession();
        header('location:' . $portal_conf['portalsite']);
        exit();
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
            // no update of token, force it to be a logout
            clearSession();
        }

        if (!isSessionVar('oauth2pass')) {
            setSessionVar('oauth2', $_REQUEST['oauth2']);
            setSessionVar('oauth2pass', 'setup');
        }
    }

    // are we in an oAUTH2 session, and if so, is it yet complete or needs the next exchange?
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
            draw_login($config_vars, 'Login Authentication took too long, please try again.', 'bg-danger text-white');
            exit();
        }
        else {
            // ok, we are in the process of an oauth2 sequence, continue it until returns the token
            $redirectURI = $portal_conf['redirect_base'];
            if ($redirectURI == '')
                $redirectURI = null;
            switch (getSessionVar('oauth2')) {
                case 'google':
                    $oauthParams = googleAuth($redirectURI);
                    if (isset($oauthParams['error'])) {
                        web_error_log($oauthParams['error']);
                        clearSession('oauth2');
                        draw_login($config_vars, $oauthParams['error'], 'bg-danger text-white');
                        exit();
                    }

            }

            if ($oauthParams == null) {
                // an error occured with login by googlr
                draw_login($config_vars, 'An error occured with the login with ' . getSessionVar('oauth2'), 'bg-danger text-white');
                clearSession('oauth2');
                exit();
            }
            if (!isset($oauthParams['email'])) {
                web_error_log('no oauth2 email found');
                draw_login($config_vars, getSessionVar('oauth2') . " did not return an email address.", 'bg-warning');
                clearSession('oauth2');
                exit();
            }

            $email = $oauthParams['email'];
            // if this is a refresh, check that it returned the same email address
            $oldemail = getSessionVar('sessionEmail');
            if ($oldemail != null && $oldemail != $email) {
                // this is a change in email address, treat this as a new login.
                // first save off the oauth session variables
                $oauth2 = getSessionVar('oauth2');
                $oauth2pass = getSessionVar('oauth2pass');
                $oauth2state = getSessionVar('oauth2state');
                // now clear the session to log the old session out
                clearSession();
                $oldemail = null;
                // now restore those
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
                // this is a refresh, don't choose the account again, just return to the home page of the portal, don't disturb any other session variables
                header('location:' . $portal_conf['portalsite'] . '/portal.php');
            }

            draw_indexPageTop($condata);
            // not a refresh, choose the account from the email
            $account = chooseAccountFromEmail($email, null, null, null, $cipherInfo, getSessionVar('oauth2'));
            if ($account == null || !is_numeric($account)) {
                if ($account == null) {
                    $account = "Error looking up data for $email";
                }
                clearSession('oauth2');;
                draw_login($config_vars, $account, 'bg-danger text-white');
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
        $match = openssl_decrypt($_GET['vid'], $cipherInfo['cipher'], $cipherInfo['key'], 0, $cipherInfo['iv']);
        $match = json_decode($match, true);
        if ($match != null) { // vid decodes, log us out
            $oldEmail = getSessionVar('email');
            if (array_key_exists('id', $match)) {
                $email = $match['email_addr'];
            } else {
                $email = $match['email'];
            }
            if ($email != $oldEmail) { // treat this as a logout and try it again
                clearSession();
            } else {
                if ($loginId != $match['id']) {
                    // this is a switch account request
                    unsetSessionVar('transId');    // just in case it is hanging around, clear this
                    unsetSessionVar('totalDue');   // just in case it is hanging around, clear this
                    setSessionVar('id', $match['id']);
                    setSessionVar('idType', $match['tablename']);
                }
                $refresh = true;
                if (array_key_exists('emailhrs', $portal_conf)) {
                    $hrs = $portal_conf['emailhrs'];
                } else {
                    $hrs = 24;
                }
                if (array_key_exists('multiple', $match)) {
                    setSessionVar('multiple', $match['multiple']);
                }
                if ($hrs == null || !is_numeric($hrs) || $hrs < 1) $hrs = 24;
                setSessionVar('tokenExpiration', time() + ($hrs * 3600));
                header('location:' . $portal_conf['portalsite'] . '/portal.php');
                exit();
            }
        }
    }
}

draw_indexPageTop($condata);

if (isset($_GET['vid'])) {
    // handle link login
    $match = openssl_decrypt($_GET['vid'], $cipherInfo['cipher'], $cipherInfo['key'], 0, $cipherInfo['iv']);
    $match = json_decode($match, true);
    if ($match == null) {   // invalid vid link
        draw_login($config_vars, "<div class='bg-danger text-white'>The link is invalid, please request a new link</div>");
        exit();
    }
    $linkid = $match['lid'];
    if (array_key_exists('id', $match)) {
        $email = $match['email_addr'];
        $id = $match['id'];
    }
    else {
        $email = $match['email'];
        $id = null;
    }
    if (array_key_exists('validationType', $match)) {
        $validationType = $match['validationType'];
        if ($match['validationType'] != 'token') {
            if ($match['validationType'] != getSessionVar('oauth2') || $email != getSessionVar('email')) {
                draw_login($config_vars, "<div class='bg-danger text-white'>The link is invalid</div>");
                exit();
            }
        }
    }
    else {
        $validationType = 'token';
    }
    $timediff = time() - $match['ts'];
    web_error_log('login @ ' . time() . ' with ts ' . $match['ts'] . " for $email/$id via $validationType");
    if ($timediff > (1 * 3600)) {
        draw_login($config_vars, "<div class='bg-danger text-white'>The link has expired, please request a new link</div>");
        exit();
    }

    if ($validationType == 'token') {
        // check if the link has been used
        $linkQ = <<<EOS
        SELECT id, email, useCnt
        FROM portalTokenLinks
        WHERE id = ? AND action = 'login'
        ORDER BY createdTS DESC;
        EOS;
        $linkR = dbSafeQuery($linkQ, 's', array ($linkid));
        if ($linkR == false || $linkR->num_rows != 1) {
            draw_login($config_vars, "<div class='bg-danger text-white'>The link is invalid, please request a new link</div>");
            exit();
        }
        $linkL = $linkR->fetch_assoc();
        if ($linkL['email'] != $email) {
            draw_login($config_vars, "<div class='bg-danger text-white'>The link is invalid, please request a new link</div>");
            exit();
        }

        if (($linkL['useCnt'] > 0 && $id == null) || ($linkL['useCnt'] > 1 && $id != null)) {
            draw_login($config_vars, "<div class='bg-danger text-white'>The link has already been used, please request a new link</div>");
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
    $account = chooseAccountFromEmail($email, $id, $linkid, $match, $cipherInfo, 'token');
    if ($account == null || !is_numeric($account)) {
        if ($account == null) {
            $account = "Error looking up data for $email";
        }
        clearSession(); // force a logout
        draw_login($config_vars, $account, 'bg-danger text-white');
    }
    exit();
} else if ($loginId != null && isSessionVar('multiple') && isset($_REQUEST['switch']) && $_REQUEST['switch'] == 'account') {
    $account = chooseAccountFromEmail(getSessionVar('multiple'), null,null, null, $cipherInfo, 'token');
    if ($account == null || !is_numeric($account)) {
        if ($account == null) {
            $account = "Error looking up data for $email";
        }
        clearSession(); // force a logout
        outputCustomText('main/notloggedin');
        draw_login($config_vars, $account, 'bg-danger text-white');
    }
    exit();
} else if ($loginId == null) {
    outputCustomText('main/notloggedin');
    draw_login($config_vars);
    exit();
}
?>
    <script type='text/javascript'>
        window.location = "<?php echo $portal_conf['portalsite'] . '/portal.php' ?>";
    </script>
<?php
function draw_indexPageTop($condata) {
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

    if ($portal_conf['open'] == 0) { ?>
        <p class='text-primary'>The membership portal is currently closed. Please check the website to determine when it will open or try again
            tomorrow.</p>
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
            From here you can create and manage your membership account.
        </div>
    </div>
EOS;
    if ($portal_conf['test'] == 1) {
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
