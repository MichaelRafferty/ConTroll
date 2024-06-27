<?php
// Registration  Portal - index.php - Login/re-login page for the membership portal
require_once("lib/base.php");
require_once("lib/getLoginMatch.php");
require_once("lib/loginItems.php");
require_once("lib/portalForms.php");
require_once("../lib/cipher.php");
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
$loginId = null;
$loginType = null;

// first lets check the Oauth2 stuff. but only if not loging out
    // in session, is it a logout?
    if (isset($_REQUEST['logout'])) {
        clearSession();
        header('location:' . $portal_conf['portalsite']);
        exit();
    }

    if (isset($_GET['oauth2'])) {
        if (!isSessionVar('oauth2pass')) {
            setSessionVar('oauth2', $_GET['oauth2']);
            setSessionVar('oauth2pass', 'setup');
        }
    }

    $oauth2pass = getSessionVar('oauth2pass');
    if ($oauth2pass != null && $oauth2pass != 'token') {
        // ok, we are in the process of an oauth2 sequence, continue it until token
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
        setSessionVar('email', $email);
        setSessionVar('displayName', $oauthParams['displayName']);
        setSessionVar('firstName', $oauthParams['firstName']);
        setSessionVar('lastName', $oauthParams['lastName']);
        setSessionVar('avatarURL', $oauthParams['avatarURL']);
        setsessionVar('subscriberId', $oauthParams['subscriberId']);

        $account = chooseAccountFromEmail($email, null, null, $cipherInfo, getSessionVar('oauth2'));
        if ($account == null || !is_numeric($account)) {
            if ($account == null) {
                $account = "Error looking up data for $email";
            }
            clearSession('oauth2');;
            draw_login($config_vars, $account, 'bg-danger text-white');
        }
        exit();
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
            clearSession();
            header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
            exit();
        }
    }
}

index_page_init($condata['label'] . ' Membership Portal');
?>
<body id="membershipPortalBody">
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 p-0">
            <?php
                if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') {
                    if (array_key_exists('logoalt', $ini)) {
                        $altstring = $ini['logoalt'];
                    }
                    else {
                        $altstring = 'Logo';
                    } ?>
                    <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo $altstring; ?>"/>
                    <?php
                }
                if (array_key_exists('logotext', $ini) && $ini['logotext'] != '') {
                    echo $ini['logotext'];
                }
            ?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 mt-2">
            <h1>Membership Portal</h1>
        </div>
    </div>
    <?php
        if ($portal_conf['open'] == 0) { ?>
            <p class='text-primary'>The membership portal is currently closed. Please check the website to determine when it will open or try again
                tomorrow.</p>
            <?php
            exit;
        }
    ?>
    <div class="row p-1">
        <div class="col-sm-auto">
            Welcome to the <?php echo $con['label']; ?> Membership Portal.
        </div>
    </div>
    <div class="row p-1">
        <div class="col-sm-12">
            From here you can create and manage your membership account.
        </div>
    </div>
    <?php
        if ($portal_conf['test'] == 1) {
            ?>
            <div class="row">
                <div class="col-sm-12">
                    <h2 class='warn'>This Page is for test purposes only</h2>
                </div>
            </div>
            <?php
        }

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
    } else {
        $email = $match['email'];
        $id = null;
    }
    if (array_key_exists('validationType', $match)) {
        $validation_type = $match['validationType'];
        if ($match['validationType'] != 'token') {
            if ($match['validationType'] != getSessionVar('oauth2') || $email != getSessionVar('email')) {
                draw_login($config_vars, "<div class='bg-danger text-white'>The link is invalid</div>");
                exit();
            }
        }
    } else {
        $validation_type = 'token';
    }
    $timediff = time() - $match['ts'];
    web_error_log('login @ ' . time() . ' with ts ' . $match['ts'] . " for $email/$id");
    if ($timediff > (1*3600)) {
        draw_login($config_vars, "<div class='bg-danger text-white'>The link has expired, please request a new link</div>");
        exit();
    }
    if ($validation_type == 'token') {
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
    }

    $account = chooseAccountFromEmail($email, $id, $linkid, $cipherInfo, 'token');
    if ($account == null || !is_numeric($account)) {
        if ($account == null) {
            $account = "Error looking up data for $email";
        }
        draw_login($config_vars, $account, 'bg-danger text-white');
    }
    exit();
} else if ($loginId == null) {
    draw_login($config_vars);
    exit();
}
?>
    <script type='text/javascript'>
        window.location = "<?php echo $portal_conf['portalsite'] . '/portal.php' ?>";
    </script>
