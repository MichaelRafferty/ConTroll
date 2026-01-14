<?php
require_once "lib/base.php";
require_once "lib/sessionAuth.php";

$page = "Home";
$authToken = new authToken('web');
$tokenState = $authToken->checkToken();

//unset id_token if logging out.
if (isset($_REQUEST['logout'])) {
    web_error_log('logout', 'controll');
    $authToken->deleteToken();
    session_regenerate_id(true);
    header('Location: index.php');
    exit();
}

if (array_key_exists('oauth2', $_REQUEST) && $_REQUEST['oauth2'] == 'google') {
    $homeDir = getConfValue('controll', 'internalHome', 'not-a-valid-path');
    if (stripos(__DIR__, $homeDir) !== false && $_SERVER['SERVER_ADDR'] == '127.0.0.1' && array_key_exists('id', $_REQUEST)) {
        $id = $_REQUEST['id'];
        // we are internal, force a login for sub $id
        $authToken->buildToken('internal', $id, 'noemail');
        $tokenState = $authToken->checkToken();
    } else {
        // this is a real login with google... start / continue the process
    }
}

if ($tokenState == 'refresh' || array_key_exists('refresh', $_REQUEST)) {
    echo "force refresh due to $tokenState\n\n";
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
        <?php
} else {
        $homeDir = getConfValue('controll', 'internalHome', 'not-a-valid-path');
        if (stripos(__DIR__, $homeDir) !== false && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
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
    ?>
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
    if ($source == 'google') {
        $allowPasskey = getConfValue('vendor', 'passkeyRpLevel', 'd') != 'd' &&
                array_key_exists('HTTPS', $_SERVER) && (isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'on');
        if ($allowPasskey) {
            ?>
            <div class='row mt-4'>
                <div class='col-sm-2'>
                    <button class='btn btn-sm btn-primary' id='newPasskey' onclick='login.newPasskey();'>
                        <img src='lib/passkey.png'>Add New Passkey
                    </button>
                </div>
                <!---
                <div class='col-sm-auto'><label for='userDisplayName'>Display Name:</label></div>
                <div class='col-sm-auto'><input type='text' id='userDisplayName' name='userDisplayName' size=64 maxlength=255/></div>
                -->
            </div>
            <?php
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
