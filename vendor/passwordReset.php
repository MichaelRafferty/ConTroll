<?php
// Exhibitor (vendor directory) - index.php - Main page for exhibitor registration (artist, vendor, exhibitor, fan)
require_once("lib/base.php");
require_once("lib/changePassword.php");
require_once("../lib/exhibitorRegistrationForms.php");

$cc = get_conf('cc');
$con = get_conf('con');
$conid = $con['id'];

$condata = get_con();

$in_session = false;
$regserver = getConfValue('reg', 'server');
$exhibitor = '';

if (str_starts_with($_SERVER['HTTP_HOST'], 'artist')){
    $portalName = 'Artist';
    $portalType = 'artist';
} else if (str_starts_with($_SERVER['HTTP_HOST'], 'exhibit')){
    $portalName = 'Exhibitor';
    $portalType = 'exhibitor';
} else if (str_starts_with($_SERVER['HTTP_HOST'], 'fan')){
    $portalName = 'Fan';
    $portalType = 'fan';
} else {
    $portalName = 'Vendor';
    $portalType = 'vendor';
}

$testsite = getConfValue('vendor', 'test') == 1;

$locale = getLocale();
$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['vemail'] = getConfValue('vendor', $portalType, getConfValue('regadminemail', 'con'));
$config_vars['portalType'] = $portalType;
$config_vars['portalName'] = $portalName;
$config_vars['artistsite'] = getConfValue('vendor', 'artistsite');
$config_vars['vendorsite'] = getConfValue('vendor', 'vendorsite');
$config_vars['debug'] = getConfValue('debug', 'vendors', 0);
$config_vars['regserver'] = getConfValue('reg','server');

exhibitor_page_init($condata['label'] . " $portalName Registration", true);
?>

<body id="vendorPortalBody">
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 p-0">
<?php
$logoImage = getConfValue('reg', 'logoimage');
if ($logoImage != '') {
    $altString = getConfValue('reg', 'logoalt', 'Logo');
    ?>
                <img class="img-fluid" src="images/<?php echo $logoImage; ?>" alt="<?php echo $altString; ?>"/>
<?php
}
echo getConfValue('reg', 'logotext');
?>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 mt-2">
            <h1><?php echo $portalName;?> Portal</h1>
        </div>
    </div>
    <?php
if (getConfValue('vendor', 'open') != 1) { ?>
    <p class='text-primary'>The <?php echo $portalName;?> portal is currently closed. Please check the website to determine when it will open or try again tomorrow.</p>
<?php
    exit;
}
$valid = true;
?>
    <div class="row p-1">
        <div class="col-sm-auto">
            Welcome to the <?php echo $con['label'] . ' ' . $portalName; ?>  Portal.
        </div>
    </div>
<?php
if (getConfValue('vendor', 'test') == 1) {
?>
    <div class="row">
        <div class="col-sm-12">
            <h2 class='warn'>This Page is for test purposes only</h2>
        </div>
    </div>
<?php
}
if (isset($_GET['vid'])) {
    $match = decryptCipher($_GET['vid'], true);
    if ($match == null)  {
        $valid = false;
?>
    <div class='row' >
        <div class='col-sm-12'>
            <h3 class='warn'>Invalid request, seek assistance.</h3 >
        </div>
    </div>
<?php
    }
    // validate the token
    $age = time() - $match['ts'];
    if ($age > 60 * 60) {
        echo <<<EOS
    <div class='row' >
        <div class='col-sm-12'>
            <h3 class='warn'>The Password Reset Link has expired, please get a new one.</h3 >
        </div>
    </div>
EOS;
        $valid = false;
    } else {
        // validate it hasn't been used
        $getTokenSQL = <<<EOS
SELECT *
FROM portalTokenLinks
WHERE id = ?;
EOS;
        $tokenR = dbSafeQuery($getTokenSQL, 'i', array($match['lid']));
        if ($tokenR === false || $tokenR->num_rows != 1) {
            echo <<<EOS
    <div class='row' >
        <div class='col-sm-12'>
            <h3 class='warn'>The Password Reset Link has expired, please get a new one.</h3 >
        </div>
    </div>
EOS;
            $valid = false;
        } else {
            $tokenL = $tokenR->fetch_assoc();
            $tokenR->free();
            $useCnt = $tokenL['useCnt'];
            if ($useCnt > 0) {
                echo <<<EOS
    <div class='row' >
        <div class='col-sm-12'>
            <h3 class='warn'>The Password Reset Link has already been used, please get a new one.</h3 >
        </div>
    </div>
EOS;
                $valid = false;
            }
        }
    }
} else {
    $valid = false;
?>
    <div class='row' >
        <div class='col-sm-12'>
            <h3 class='warn'>Invalid request, seek assistance.</h3 >
        </div>
    </div>
<?php
}
if ($valid) {
?>
    <div class="row p-1">
        <div class="col-sm-12">
            <h2>You have requested to change your password.
        </div>
    </div>
<?php
    setSessionVar('pwToken', $match);
    $info = [ 'login' => $match['email'], 'admin' => $config_vars['vemail'] ];
    drawChangePassword(null, 3, true, $info, 'a');
}
?>
    </div>
    <div class='container-fluid'>
        <div class='row'>
            <div class='col-sm-12 m-0 p-0'>
                <div id='result_message' class='mt-4 p-2'></div>
            </div>
        </div>
        <div class='row'>
            <?php drawBug(12); ?>
        </div>
    </div>
</body>
</html>
