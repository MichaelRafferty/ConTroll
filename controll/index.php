<?php
require_once "lib/base.php";
require_once "lib/sessionAuth.php";
require_once('../lib/googleOauth2.php');

$page = "Home";
$authToken = new authToken('web');
$tokenState = $authToken->checkToken();
if ($tokenState == 'expired') {
    // expired tokens are not to be 'refreshed', but to be deleted and start from scratch, this is the same as a login
    $authToken->deleteToken();
    session_regenerate_id(true);
    $tokenState = 'none';
}

//unset id_token if logging out.
if (isset($_REQUEST['logout'])) {
    $sesPerid = getSessionVar('user_perid');
    if (!$sesPerid)
        $sesPerid = '(no login)';
    $authToken->logSession("ConTroll Admin " . $authToken->getSource() . " logout of perid:$sesPerid, userid:" . $authToken->getUserId()
            . ', token perid:' . $authToken->getPerid() . ", email:" . $authToken->getEmail() . ', name:' . $authToken->getName()
            . ' from ' . $_SERVER['REMOTE_ADDR']);
    $authToken->deleteToken();
    session_regenerate_id(true);
    // refresh the page to take the logout string off the URL
    header('Location: index.php');
    exit();
}

if (array_key_exists('oauth2', $_REQUEST) && $_REQUEST['oauth2'] == 'google') {
    $homeDir = getConfValue('controll', 'internalHome', 'not-a-valid-path');
    if (stripos(__DIR__, $homeDir) !== false && (($_SERVER['SERVER_ADDR'] == '127.0.0.1') || ($_SERVER['SERVER_ADDR'] == '::1'))
            && array_key_exists('id', $_REQUEST)) {
        $id = $_REQUEST['id'];
        // we are internal, force a login for sub $id
        $authToken->buildToken('internal', $id, 'noemail');
        $tokenState = $authToken->checkToken();
    } else {
        // this is a real login with google... start / continue the process
        $oauth = getSessionVar('oauth');
        clearSession('oauth2');
        if ($oauth != null) setSessionVar('oauth', $oauth);
        setSessionVar('oauth2', $_REQUEST['oauth2']);
        setSessionVar('oauth2pass', 'setup');
    }
}

$oauth2pass = getSessionVar('oauth2pass');
if ($oauth2pass != null && $oauth2pass != 'token') {
    // is this session validation taking too long?
    $oauth2timeout = getSessionVar('oauth2timeout');
    if ($oauth2timeout == null) {  // no timeout set one
        $oauth2timeout = time() + 2 * 60;
        setSessionVar('oauth2timeout', $oauth2timeout);
    }
    if (time() > $oauth2timeout) {
        clearSession('oauth2'); // end the validation loop
        web_error_log("Oauth2 Timeout");
        header('location:' . getConfValue('controll', 'controllsite'));
        exit();
    } else {
        // ok, we are in the process of an oauth2 sequence, continue it until returns the token
        $redirectURI = getConfValue('controll', 'redirect_base');
        if ($redirectURI == '')
            $redirectURI = null;
        $oauthParams = null;
        switch (getSessionVar('oauth2')) {
            case 'google':
                $oauthParams = googleAuth($redirectURI);
                if (isset($oauthParams['error'])) {
                    web_error_log("Google oauth2 error: " . $oauthParams['error']);
                    clearSession('oauth2');
                    drawErrorPage('Google Login Issue: ', $oauthParams['error']);
                    exit();
                }
        }
        if ($oauthParams == null) {
            // an error occured with login by google
            $source = getSessionVar('oauth2');
            web_error_log("oauth2 error occurred, no params from $source");
            drawErrorPage("$source login issue: ","An error occured with the login with $source");
            clearSession('oauth2');
            exit();
        }
        if (!isset($oauthParams['email'])) {
            $source = getSessionVar('oauth2');
            web_error_log("no oauth2 email returned from $source");
            drawErrorPage("$source login not found:", "$source did not return a matching email address for this account");
            clearSession('oauth2');
            exit();
        }
    }

    $email = strtolower($oauthParams['email']);
    $source = getSessionVar('oauth2');
    $sub = $oauthParams['subscriberId'];
    // build/refresh a login with that email and put it in the session
    if (isSessionVar('authToken') && $authToken->getSource() == $source && $authToken->getEmail() == $email) {
        $authToken->refreshExpire();
        header('location:' . getConfValue('controll', 'controllsite') . '?autoclose=1');
        exit();
    } else {
        clearSession();
        if ($authToken->buildToken($source, $sub, $email)) {
            header('location:' . getConfValue('controll', 'controllsite'));
            exit();
        }
    }
    web_error_log("failed login, no match");
    exit();
}

if ($tokenState == 'refresh' || array_key_exists('refresh', $_REQUEST)) {
    page_init($page,
            /*css*/ array('css/base.css'),
            /*js*/  array(
                    'jslib/passkey.js',
                    'js/login.js'
            ),
            null);

    // now process the re-authentication
    switch ($authToken->getSource()) {
        case 'internal':
            $homeDir = getConfValue('controll', 'internalHome', 'not-a-valid-path');
            if (stripos(__DIR__, $homeDir) !== false && (($_SERVER['SERVER_ADDR'] == '127.0.0.1') || ($_SERVER['SERVER_ADDR'] == '::1'))) {
                if ($authToken->refreshExpire()) {
                    echo <<<EOS
    <div class="row mt-4">
        <div class="col-sm-12">
            <span class="h4"><b>Internal Type Login Refreshed: window will close in two seconds.</b></span>
        </div>
    </div>
<script type='text/javascript'>
setTimeout(() => { window.close(); }, 2000);
</script>
EOS;
                }
            }
            break;
        case 'passkey':
            $email = $authToken->getEmail();
            echo <<<EOS
<div class="row mt-4">
        <div class="col-sm-12">
            <span class="h4"><b>Your session is going to expire soon, please revalidate your session by logging in with your passkey again.</b></span>
        </div>
    </div>
<script type='text/javascript'>
setTimeout(() => { login.loginWithPasskey("$email"); }, 1000);
</script>
EOS;

            break;
        case 'google':
            echo <<<EOS
<div class="row mt-4">
        <div class="col-sm-12">
            <span class="h4"><b>Your session is going to expire soon, please revalidate your session by logging in with google gain.</b></span>
        </div>
    </div>
<script type='text/javascript'>
setTimeout(() => { login.loginWithGoogle(); }, 2000);
</script>
EOS;
            break;
        default:
            echo <<<EOS
    <div class="row mt-4">
        <div class="col-sm-12">
            <p>Something seems to have gone wrong.  Please click the login button below to re-login.</p>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-auto"><button class='btn btn-sm btn-primary' onclick='window.reload();'>Click to Log in Again</button></div>
    </div>
EOS;
            $authToken->deleteToken();
            session_regenerate_id(true);
            break;
    }
    exit();
}

if (array_key_exists('autoclose', $_REQUEST) && $_REQUEST['autoclose'] == 1 && $authToken->getRefreshCount() > 0) {
    $source = $authToken->getSource();
    page_init($page,
            /*css*/ array ('css/base.css'),
            /*js*/ array (
                    'jslib/passkey.js',
                    'js/login.js'
            ),
            null);
    echo <<<EOS
    <div class="row mt-4">
        <div class="col-sm-12">
            <span class="h4"><b>$source Type Login Refreshed: window will close in two seconds.</b></span>
        </div>
    </div>
<script type='text/javascript'>
setTimeout(() => { window.close(); }, 2000);
</script>
EOS;
    exit();
}

page_init($page,
    /*css*/ array('css/base.css'),
    /*js*/  array(
            'jslib/passkey.js',
            'js/login.js'
        ),
    $authToken);

if ($tokenState == 'none' || $tokenState == 'expired') {
?>
    <div id='main'>
        <div class='container-fluid'>
            <div class='row mt-2 mb-4'>
                <div class="col-sm-auto"><span class="h3"><b>You haven't Logged in:</b></span></div>
            </div>
<?php
    if (array_key_exists('HTTPS', $_SERVER) && (isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'on')) {
        if (getConfValue('controll', 'passkeyRpLevel') != 'd') {
?>
            <div class='row mb-2 align-items-center'>
                <div class='col-sm-auto'>
                    <button class='btn btn-sm btn-primary' id='loginPasskeyBtn' onclick='login.loginWithPasskey();'>
                        <img src='lib/passkey.png' width='25'>Login with Passkey
                    </button>
                </div>
                <div class='col-sm-auto'>
                    Don't have one?<br/>Create a passkey AFTER LOGGING IN with Google.
                </div>
            </div>
<?php
        }
?>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <button class='btn btn-sm btn-primary' onclick='login.loginWithGoogle();'>Login with Google</button>
                </div>
            </div>
        </div>
        <?php
} else {
        $homeDir = getConfValue('controll', 'internalHome', 'not-a-valid-path');
        if (stripos(__DIR__, $homeDir) !== false && (($_SERVER['SERVER_ADDR'] == '127.0.0.1') || ($_SERVER['SERVER_ADDR'] == '::1'))) {
            for ($i = 1; $i < 99; $i++) {
                $internal = getConfValue('controll', 'internalUser' . $i);
                if ($internal == null)
                    break;

                [$ie, $isub,$iid,$iperid] = explode(',', $internal);
?>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <button class='btn btn-sm btn-primary' onclick='login.loginWithGoogle(<?php echo $isub;?>);'>Login as <?php echo "$ie ($iperid)";
                    ?></button>
                </div>
            </div>
<?php
            }
        }
?>
        </div>
<?php
    }
} else {
    // start of logged in section to show the information about you and your token
    $con = get_conf('con');
    $conid = $con['id'];
    if (array_key_exists('oneoff', $con))
        $oneoff = $con['oneoff'];
    else
        $oneoff = 0;
    if ($oneoff == null || $oneoff == '')
        $oneoff = 0;
    # create the user session variable
    $user_email = $authToken->getEmail();
    $user_perid = $authToken->getPerid();
    $user_id = $authToken->getUserId();
    // get the version string, and the current DB patch level
    $versionFile = '../version.txt';
    if (is_readable($versionFile)) {
        $versionText = file_get_contents("../version.txt");
    } else {
        $versionText = "Version information not available\n";
    }
    $patchLevel = dbQuery("SELECT MAX(id) FROM patchLog;")->fetch_row()[0];
    if ($patchLevel === null || $patchLevel === false || $patchLevel < 0) {
        $patchLevel = "unavailable";
    }
    $source = $authToken->getSource();
    $config_vars['email'] = $authToken->getEmail();
    $config_vars['source'] = $authToken->getSource();
    $config_vars['name'] = $authToken->getName();
    ?>
    <script type='text/javascript'>
        var config = <?php echo json_encode($config_vars); ?>;
    </script>
    <div id='main'>
        <div class="container-fluid">
            <div class="row mt-2">
                <div class="col-sm-auto">
                    <span class="h4">You successfully Logged in.</span>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-auto">
                    If you need more access please email the appropriate person with the email and sub value listed below.<br/>
                </div>
            </div>
<?php
    $allowPasskey = getConfValue('vendor', 'passkeyRpLevel', 'd') != 'd' &&
                    array_key_exists('HTTPS', $_SERVER) && (isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'on');
    if ($allowPasskey) {
        $pQ = <<<EOS
SELECT id
FROM passkeys
WHERE source = 'controll' AND userName = ?;
EOS;
        $pR = dbSafeQuery($pQ, 's', array($authToken->getEmail()));
        if ($pR !== false && $pR->num_rows > 0) {
            $keyId = $pR->fetch_row()[0];
            $pR->free();
        } else
            $keyId = -1;
    }
    if ($source == 'google') {
        if ($allowPasskey) {
            if ($keyId > 0) {
?>
  <div class='row mt-4'>
                <div class='col-sm-2'>
                    <button class='btn btn-sm btn-primary' id='newPasskey' onclick='login.deletePasskey(<?php echo $keyId; ?>);'>
                        <img src='lib/passkey.png'>Delete Existing Passkey
                    </button>
                </div>
            </div>
<?php
            } else {
?>
            <div class='row mt-4'>
                <div class='col-sm-2'>
                    <button class='btn btn-sm btn-primary' id='newPasskey' onclick='login.newPasskey();'>
                        <img src='lib/passkey.png'>Add New Passkey
                    </button>
                </div>
            </div>
            <?php
            }
        }
    }
?>
            <div class="row">
                <div class="col-sm-auto mt-4 mb-0">
                    <pre><?php
                            echo "Email: $user_email\n";
                            echo "User id: $user_id\n";
                            echo "User perid: $user_perid\n";
                            echo "Source: $source\n";
                            echo "Sub: " . $authToken->getAuthId() . "\n";
                            echo 'Current Time: ' . date('c') . "\n";
                            echo "Token Expires: " . date('c', $authToken->getExpire()) . "\n";
                            echo "Next Refresh: " . date('c', $authToken->getRefresh()) . "\n";
                            echo "PHP Version: " . phpversion() . "\n";
                            echo "$versionText";
                            echo "Config Update: " . getConfValue('global', 'version', 'unknown') . "\n";
                            echo "Database Patch Level: $patchLevel\n";
                            echo "Conid: $conid\n";
                        ?>
                    </pre>
                </div>
            </div>
<?php
    if ($oneoff == 0 && $authToken->checkAuth("admin")) {
        // check if next year exists, and if not, put up button to create it
        $nyR = dbSafeQuery("SELECT COUNT(*) FROM conlist WHERE id = ?;", 'i', array($conid + 1));
        $nyF = $nyR->fetch_row()[0];
        $nyR->free();
        if ($nyF == 0) { ?>
            <div class='row'>
                <div class='col-sm-auto m-4'>
                    <button class="btn btn-sm btn-primary" onClick="window.location='/admin.php?buildNext=1';">Build <?PHP echo $conid;?> Setup</button>
                </div>
            </div>
<?php
        }
    }
    if (array_key_exists('msg', $_REQUEST)) {
        $msg = $_REQUEST['msg'];
?>
        <div class="row">
            <div class="col-sm-12 mt-4">
                <strong style="background-color:red;">
                    <?php echo $msg; ?>
                </strong>

<?php
    }
?>
        </div>
    </div>
    <?php
}

page_foot($page);

function drawErrorPage($who, $error) {
    $page = "Home";
    page_init($page,
            /*css*/ array('css/base.css'),
            /*js*/  array(),
            null);
    echo <<<EOS
<div class="container-fluid">
    <div class="row mt-4">
        <div class="col-sm-auto"><span class="h2">An error occured trying to log you in</span></div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-auto"><b>$who</b></div>
        <div class="col-sm-auto">$error</div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-auto">If this keeps happening, please seek assistance</div>
    </div>
    <div class="row mt-4">
        <div class="col-sm-auto"><button class='btn btn-sm btn-primary' onclick='location.reload();'>Click to Log in Again</button></div>
    </div>
</div>
EOS;
    page_foot($page);
}
