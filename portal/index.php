<?php
// Registration  Portal - index.php - Main page for the membership portal
require_once("lib/base.php");
require_once("lib/registrationForms.php");

global $config_vars;

$con = get_conf('con');
$conid = $con['id'];
$portal_conf = get_conf('portal');
$debug = get_conf('debug');
$ini = get_conf('reg');
$condata = get_con();

$in_session = false;

// encrypt/decrypt stuff (maybe needed?)
$ciphers = openssl_get_cipher_methods();
$cipher = 'aes-128-cbc';
$ivlen = openssl_cipher_iv_length($cipher);
$ivdate = date_create("now");
$iv = substr(date_format($ivdate, 'YmdzwLLwzdmY'), 0, $ivlen);
$key = $conid . $con['label'] . $con['regadminemail'];

$config_vars = array();
$config_vars['label'] = $con['label'];
$config_vars['debug'] = $debug['portal'];
$config_vars['uri'] = $portal_conf['portalsite'];

portal_page_init($condata['label'] . " Membership Portal");

// load country select
$countryOptions = '';
$fh = fopen(__DIR__ . '/../lib/countryCodes.csv', 'r');
while(($data = fgetcsv($fh, 1000, ',', '"'))!=false) {
  $countryOptions .= '<option value="' . escape_quotes($data[1]) . '">' .$data[0] . '</option>' . PHP_EOL;
}
fclose($fh);
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
            Welcome to the <?php echo $con['label']; ?>  Membrership Portal.
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
        header('location:' . $portal_conf['portalsite']);
        exit();
    } else {
        // nope, just set the vendor id
        $personType = $_SESSION['idType'];
        $personId = $_SESSION['id'];
        $in_session = true;
    }
/*
    // if archived, unarchive them, they just logged in again
    if ($match['archived'] == 'Y') {
        // they were marked archived, and they logged in again, unarchive them.
        $numupd = dbSafeCmd("UPDATE exhibitors SET archived = 'N' WHERE id = ?", 'i', array($exhibitor));
        if ($numupd != 1)
            error_log("Unable to unarchive vendor $exhibitor");
    }
*/
} else if (isset($_POST['si_email']) and isset($_POST['si_password'])) {
    // handle login submit
    $login = strtolower(sql_safe($_POST['si_email']));
    $loginQ = <<<EOS
SELECT e.id, e.exhibitorName, LOWER(e.exhibitorEmail) as eEmail, e.password AS ePassword, e.need_new as eNeedNew, ey.id AS eyID, 
       LOWER(ey.contactEmail) AS cEmail, ey.contactPassword AS cPassword, ey.need_new AS cNeedNew, archived, ey.needReview
FROM exhibitors e
LEFT OUTER JOIN exhibitorYears ey ON e.id = ey.exhibitorId
WHERE (e.exhibitorEmail=? OR ey.contactEmail = ?) AND conid = ?;
EOS;
    $loginR = dbSafeQuery($loginQ, 'ssi', array($login, $login, $conid));
    // find out how many match
    $matches = array();
    while ($result = $loginR->fetch_assoc()) { // check exhibitor email/password first
        $found = false;
        if ($login == $result['eEmail']) {
            if (password_verify($_POST['si_password'], $result['ePassword'])) {
                $result['loginType'] = 'e';
                $matches[] = $result;
                $found = true;
            }
        }
        if (!$found && $login == $result['cEmail']) { // try contact login second
            if (password_verify($_POST['si_password'], $result['cPassword'])) {
                $result['loginType'] = 'c';
                $matches[] = $result;
                $found = true;
            }
        }
    }
    $loginR->free();
    if (count($matches) == 0) {
        ?>
    <h2 class='warn'>Unable to Verify Password</h2>
    <?php
// not logged in, draw signup stuff
        //draw_registrationModal($portalType, $portalName, $con, $countryOptions);
        draw_login($config_vars);
        exit();
    }

    if (count($matches) > 1) {
        echo "<h4>This email address has access to multiple portal accounts</h4>\n" .
            "Please select one of the accounts below:<br/><ul>\n";

        foreach ($matches as $match) {
            $match['ts'] = time();
            $string = json_encode($match);
            $string = urlencode(openssl_encrypt($string, $cipher, $key, 0, $iv));
            echo "<li><a href='?vid=$string'>" .  $match['exhibitorName'] . "</a></li>\n";
        }
?>
    </ul>
    <button class='btn btn-secondary m-1' onclick="window.location='?logout';">Logout</button>
    <script type='text/javascript'>
        var config = <?php echo json_encode($config_vars); ?>;
    </script>
    <?php
        exit();
    }

    // a single  match....
    $match = $matches[0];
    $_SESSION['id'] = $match['id'];
    $exhibitor = $_SESSION['id'];
    $_SESSION['login_type'] = $match['loginType'];
    $in_session = true;
    if ($match['loginType'] == 'e') {
        if ($match['eNeedNew']) {
            $forcePassword = true;
        }
    } else {
        if ($match['cNeedNew']) {
            $forcePassword = true;
        }
    }

    // if archived, unarchive them, they just logged in again
    if ($match['archived'] == 'Y') {
        // they were marked archived, and they logged in again, unarchive them.
        $numupd = dbSafeCmd("UPDATE exhibitors SET archived = 'N' WHERE id = ?", 'i', array($exhibitor));
        if ($numupd != 1)
            error_log("Unable to unarchive vendor $exhibitor");
    }
} else {
    //draw_registrationModal($portalType, $portalName, $con, $countryOptions);
    draw_login($config_vars);
    exit();
}

// this section is for 'in-session' management
// build info array

if ($personType == 'p') {
    $personSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.contact_ok, p.share_reg_ok,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname
    FROM perinfo p
    WHERE id = ?;
EOS;
} else {
    $personSQL = <<<EOS
SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.address, p.addr_2, p.city, p.state, p.zip, p.country,
    'N' AS banned, p.createtime AS creation_date, 'Y' AS active, p.contact_ok, p.share_reg_ok,
    TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname
    FROM newperson p
    WHERE id = ?;
EOS;
}
$personR = dbSafeQuery($personSQL, 'i', array($personId));
if ($personR === false || $personR->num_rows == 0) {
    echo "Invalid Login, seek assistance";
    portal_page_foot();
    exit();
}
$info = $personR->fetch_assoc();
$personR->free();
?>
<script type='text/javascript'>
    var config = <?php echo json_encode($config_vars); ?>;
    var country_options = <?php echo json_encode($countryOptions); ?>;
    </script>
<?php
/*
draw_registrationModal($portalType, $portalName, $con, $countryOptions);
draw_passwordModal();
draw_exhibitorRequestModal();
draw_exhibitorInvoiceModal($exhibitor, $info, $countryOptions, $ini, $cc, $portalName, $portalType);
draw_exhibitorReceiptModal($portalType);
draw_itemRegistrationModal($portalType, $portal_conf['artsheets'], $portal_conf['artcontrol']);
*/
?>
    <!-- now for the top of the form -->
     <div class='container-fluid'>
        <div class='row p-1'>
            <div class='col-sm-12 p-0'>
                <h3>Welcome to the membership Portal Page for <?php echo $info['fullname']; ?></h3>
            </div>
        </div>
        <div class="row p-1">
            <div class="col-sm-auto p-0">
                <button class="btn btn-secondary m-1" onclick="Profile.profileModalOpen('update');">View/Change your personal information</button>
                <button class="btn btn-secondary m-1" onclick="window.location='?logout';">Logout</button>
            </div>
        </div>
    <?php
    portal_page_foot();
    ?>
