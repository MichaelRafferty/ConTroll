<?php
// Registration  Portal - index.php - Login/re-login page for the membership portal
require_once("lib/base.php");
require_once("lib/getLoginMatch.php");
require_once("lib/loginItems.php");
require_once("lib/portalForms.php");
require_once("../lib/cipher.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$condata = get_con();

$in_session = false;

// encrypt/decrypt stuff (maybe needed?)
$cipherInfo = getLoginCipher();

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = $debug['portal'];
$config_vars['uri'] = $portal_conf['portalsite'];

index_page_init($condata['label'] . " Membership Portal");
?>
<body id="membershipPortalBody">
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 p-0">
            <?php
if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') {
    if (array_key_exists('logoalt', $ini)) {
        $altstring = $ini['logoalt'];
    } else {
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
    <p class='text-primary'>The membership portal is currently closed. Please check the website to determine when it will open or try again tomorrow.</p>
<?php
    exit;
}
?>
    <div class="row p-1">
        <div class="col-sm-auto">
            Welcome to the <?php echo $con['label']; ?>  Membership Portal.
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
if (isset($_SESSION['id'])) {
// in session, is it a logout?
    if (isset($_REQUEST['logout'])) {
        session_destroy();
        unset($_SESSION['id']);
        unset($_SESSION['idType']);
        unset($_SESSION['idSource']);
        unset($_SESSION['transId']);
        unset($_SESSION['totalDue']);
        header('location:' . $portal_conf['portalsite']);
        exit();
    } else {
        // nope, just set the vendor id
        $personType = $_SESSION['idType'];
        $personId = $_SESSION['id'];
        $in_session = true;
    }
} else if (isset($_GET['vid'])) {
    // handle link login
    $match = openssl_decrypt($_GET['vid'], $cipherInfo['cipher'], $cipherInfo['key'], 0, $cipherInfo['iv']);
    $match = json_decode($match, true);
    $linkid = $match['lid'];
    if (array_key_exists('id', $match)) {
        $email = $match['email_addr'];
        $id = $match['id'];
    } else {
        $email = $match['email'];
        $id = null;
    }
    $timediff = time() - $match['ts'];
    web_error_log('login @ ' . time() . ' with ts ' . $match['ts'] . " for $email/$id");
    if ($timediff > (1*3600)) {
        draw_login($config_vars, "<div class='bg-danger text-white'>The link has expired, please request a new link</div>");
        exit();
    }
    // check if the link has been used
    $linkQ = <<<EOS
SELECT id, email, useCnt
FROM portalTokenLinks
WHERE id = ? AND action = 'login'
ORDER BY createdTS DESC;
EOS;
    $linkR = dbSafeQuery($linkQ, 's', array($linkid));
    if ($linkR == false || $linkR->num_rows != 1) {
        draw_login($config_vars, "<div class='bg-danger text-white'>The link is invalid, please request a new link</div>");
        exit();
    }
    $linkL = $linkR->fetch_assoc();
    if ($linkL['email'] != $email) {
        draw_login($config_vars, "<div class='bg-danger text-white'>The link is invalid, please request a new link</div>");
        exit();
    }

    if (($linkL['useCnt'] > 0 && $id == NULL) || ($linkL['useCnt'] > 1 && $id != null)) {
        draw_login($config_vars, "<div class='bg-danger text-white'>The link has already been used, please request a new link</div>");
        exit();
    }

    // mark link as used
    $updQ = <<<EOS
UPDATE portalTokenLinks
SET useCnt = useCnt + 1, useIP = ?, useTS = now()
WHERE id = ?;
EOS;
    $updcnt = dbSafeCmd($updQ, 'si', array($_SERVER['REMOTE_ADDR'], $linkid));
    if ($updcnt != 1) {
        web_error_log("Error updating link $linkid as used");
    }

    $account = chooseAccountFromEmail($email, $id, $linkid, $cipherInfo, 'token');
    if ($account == null || !is_numeric($account)) {
        if ($account == null) {
            $account = "Error looking up data for $email";
        }
        draw_login($config_vars);
        show_message($account, 'error');
    }
    exit();
} else {
    draw_login($config_vars);
    exit();
}
?>
    <script type='text/javascript'>
        window.location = "<?php echo $portal_conf['portalsite'] . '/portal.php' ?>";
    </script>