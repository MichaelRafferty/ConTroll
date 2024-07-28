<?php
// portal - base.php - base functions for membership portal
global $db_ini;
global $appSessionPrefix;

if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . '/../../config/reg_conf.ini', true);
}

if ($db_ini['reg']['https'] <> 0) {
    if (!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

require_once(__DIR__ . "/../../lib/db_functions.php");
require_once(__DIR__ . '/../../lib/ajax_functions.php');
require_once(__DIR__ . '/../../lib/global.php');

db_connect();
$appSessionPrefix = 'Ctrl/Portal/';
session_start();

function index_page_init($title) {
    $cdn = getTabulatorIncludes();
    $tabbs5=$cdn['tabbs5'];
    $tabcss=$cdn['tabcss'];
    $tabjs=$cdn['tabjs'];
    $bs5js=$cdn['bs5js'];
    $bs5css=$cdn['bs5css'];
    $jqjs=$cdn['jqjs'];
    $jquijs=$cdn['jquijs'];
    $jquicss=$cdn['jquicss'];
    $portal_conf = get_conf('portal');
    if (array_key_exists('customtext', $portal_conf)) {
        $filter = $portal_conf['customtext'];
    } else {
        $filter = 'production';
    }
    loadCustomText('portal', basename($_SERVER['PHP_SELF'], '.php'), $filter);

    $versions = get_conf('js');
    $portal = $versions['portal'];
    $global = $versions['global'];
    $jslib = $versions['jslib'];
    echo <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>$title</title>
    <link rel='icon' type='image/x-icon' href='/lib/favicon.ico'>
    <link href='css/style.css' rel='stylesheet' type='text/css' />
    <link href='$jquicss' rel='stylesheet' type='text/css' /> 
    <link href='$tabcss' rel='stylesheet'>
    <link href='$bs5css' rel='stylesheet'>
    
    <script src='$bs5js'></script>
    <script type='text/javascript' src='$jqjs''></script>
    <script type='text/javascript' src='$jquijs'></script>
    <script type="text/javascript" src="$tabjs"></script>
    <script type="text/javascript" src="jslib/global.js?v=$global"></script>
    <script type='text/javascript' src='js/base.js?v=$portal'></script>
    <script type='text/javascript' src='js/login.js?v=$portal'></script>
</head>
EOF;
}

function portalPageInit($page, $info, $css, $js, $refresh = false) {
    global $db_ini;

    $con = get_conf('con');
    $label = $con['label'];
    $ini = get_conf('reg');
    $portal_conf = get_conf('portal');
    if(isWebRequest()) {
        if (array_key_exists('customtext', $portal_conf)) {
            $filter = $portal_conf['customtext'];
        } else {
            $filter = 'production';
        }
        loadCustomText('portal', basename($_SERVER['PHP_SELF'], '.php'), $filter);
        $includes = getTabulatorIncludes();
        $loginId = getSessionVar('id');
        $loginType = getSessionVar('idType');
        if ($loginType == 'p') {
            $title = $info['fullname'] . ' (ID: ' . $loginId . ')';
        } else {
            $title = $info['fullname'] . ' (ID: In Progress)';
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8"/>
            <title><?php echo $title . '--' . $db_ini['con']['conname']?> Reg</title>
            <link rel='icon' type='image/x-icon' href='/lib/favicon.ico'>
            <link href='css/style.css' rel='stylesheet' type='text/css' />
            <link href='<?php echo $includes['jquicss'];?>' rel='stylesheet' type='text/css' />
            <link href='<?php echo $includes['bs5css'];?>' rel='stylesheet'/>
            <?php
            if(isset($css) && $css != null) { foreach ($css as $sheet) {
                ?><link href='<?php echo $sheet; ?>' rel=stylesheet type='text/css' />
                <?php
            }}
           $versions = get_conf('js');
            $portal = $versions['portal'];
            $global = $versions['global'];
            $jslib = $versions['jslib'];
            ?>
            <script src='<?php echo $includes['bs5js'];?>'></script>
            <script type='text/javascript' src='<?php echo $includes['jqjs']; ?>'></script>
            <script type='text/javascript' src='<?php echo $includes['jquijs']; ?>'></script>
            <script type='text/javascript' src='jslib/global.js?v=$global'></script>
            <script type='text/javascript' src='js/base.js?v=$portal'></script>
            <?php
            if(isset($js) && $js != null) { foreach ($js as $script) {
                ?><script src='<?php echo $script . '?v=$portal'; ?>' type='text/javascript'></script><?php
            }}
            ?>
        </head>
        <body>
        <body id='membershipPortalBody'>
        <div class='container-fluid' id='LogoBar'>
            <div class='row'>
                <div class='col-sm-auto p-1'>
                    <?php
                    if (array_key_exists('logoimage', $portal_conf) && $portal_conf['logoimage'] != '') {
                        $logoImage = $portal_conf['logoimage'];
                    } else if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') {
                        $logoImage = $ini['logoimage'];
                    }
                    if (array_key_exists('logoalt', $portal_conf) && $portal_conf['logoalt'] != '') {
                        $altstring = $portal_conf['logoalt'];
                    } else if (array_key_exists('logoalt', $ini) && $ini['logoalt'] != '') {
                        $altstring = $ini['logoalt'];
                    } else {
                        $altstring = 'Logo';
                    }
                    echo "<img class='img-fluid' src='images/$logoImage' style='max-height: 150px;' alt='$altstring'/>\n";
                    ?>
                </div>
                <div class="col text-bg-primary text-white">
                    <div class='container-fluid'>
                        <div class='row'>
                            <div class='col-sm-12'>
                                <h2 class='title'>
                                        <?php echo $label; ?> Membership Portal for <?php echo $title; ?>
                                </h2>
                            </div>
                        </div>
                        <?php
                    if ($portal_conf['test'] == 1) {
                    ?>
                            <div class="row">
                                <div class="col-sm-12">
                                    <span class='warn' style="font-size: 32pt;">This Page is for test purposes only</span>
                                </div>
                            </div>
                    <?php
                    } ?>
                        <div class="row">
                            <div class='col-sm-12'><?php tabBar($page, $portal_conf, $info, $refresh);?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        if ($portal_conf['open'] == 0) { ?>
            <p class='text-primary'>The membership portal is currently closed. Please check the website to determine when it will open or try again tomorrow.</p>
            <?php
            exit;
            }
        outputCustomText('main/top');
    }
}

function portalPageFoot() {
    $msg = '';
    $class='';
    if (array_key_exists('messageFwd', $_GET)) {
        $msg = $_GET['messageFwd'];
        $class = ' bg-success text-white';

        if (array_key_exists('type', $_GET)) {
            $type = $_GET['type'];
            if ($type == 'w')
                $class = ' bg-warning';
            else if ($type == 'e')
                $class = ' bg-danger text-white';
        }
    }
    outputCustomText('main/bottom');
    ?>
    <div class="container-fluid">
        <div class='row'>
            <div class='col-sm-12 m-0 p-0'>
                <div id='result_message' class='mt-4 p-2<?php echo $class; ?>'><?php echo $msg; ?></div>
            </div>
        </div>
        <div class='row mt-2'>
            <?php drawBug(12); ?>
        </div>
    </div>
    </body>
    </html>
    <?php
}

function tabBar($page, $portal_conf, $info, $refresh = false) {
    $page_list = [];
    if (!$refresh) {
        $showHistory = true;
        if (array_key_exists('history', $portal_conf) && $portal_conf['history'] == '0') {
            $showHistory = false;
        }

        if ($showHistory) {
            $page_list[] = ['name' => 'membershipHistory', 'display' => 'Membership History'];
        }
        if ($info['managedByName'] == null) {
            $page_list[] = ['name' => 'accountSettings', 'display' => 'Account Settings'];
        }
        if (isSessionVar('multiple')) {
            $page_list[] = ['name' => 'index', 'args' => 'switch=account', 'display' => 'Switch Account'];
        }
        $page_list[] = ['name' => 'portalHelp', 'target' =>  '_blank', 'display' => 'Help'];
    }

    $active = $page == 'portal' ? 'active' : '';
    $ariainfo = $page == 'portal' ? 'aria-current="page"' : '';
    ?>

    <nav class="navbar navbar-dark bg-primary navbar-expand-lg mb-2">
        <div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                    style="border-radius: 5px;" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="navbarNav">
            <button class="btn btn-outline-light navitem me-3 <?php echo $active; ?>" type="button" <?php echo $ariainfo; ?>
                style='border-bottom-right-radius: 20px;' onclick='window.location="portal.php";'>Home</button>
<?php
    if ($portal_conf['open'] != 0) {
        foreach ($page_list as $pageInfo) {
            $p = $pageInfo['name'];
            $d = $pageInfo['display'];
            $active = $page == $p ? 'active' : '';
            $ariainfo = $page == $p ? 'aria-current="page"' : '';
            $url = $pageInfo['name'] . '.php';
            if (array_key_exists('args', $pageInfo)) {
                $url .= '?' . $pageInfo['args'];
            }

            if (array_key_exists('target', $pageInfo)) {
                $onclick = "window.open('$url', '" . $pageInfo['target'] . "').focus();";
            } else {
                $onclick = "window.location.href='$url';";
            }
?>
            <button class="btn btn-outline-light navitem me-3 <?php echo $active; ?>" type='button' <?php echo $ariainfo; ?>
                style='border-top-left-radius: 20px; border-bottom-right-radius: 20px;' onclick="<?php echo $onclick; ?>"><?php echo $d;?></button>
<?php
        }
    }
?>
            <button class="btn btn-outline-light navitem <?php echo $active; ?>" type='button' <?php echo $ariainfo; ?>
                style='border-top-left-radius: 20px;' onclick="window.location='index.php?logout';">Logout</button>
        </div>
    </nav>
    <?php
}

function isWebRequest() {
    return isset($_SERVER['HTTP_USER_AGENT']);
}

// getPersonInfo - retrieve the data for the logged in person
    // build info array about the account holder

function getPersonInfo($conid) {
    $personType = getSessionVar('idType');
    $personId = getSessionVar('id');
    if ($personType == 'p') {
        $pfield = 'perid';
        $personSQL = <<<EOS
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
           p.address, p.addr_2, p.city, p.state, p.zip, p.country,
           p.banned, p.creation_date, p.update_date, p.change_notes, p.active, p.managedBy, p.lastVerified, 'p' AS personType,
           TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
           TRIM(REGEXP_REPLACE(CONCAT(IFNULL(pm.first_name, ''),' ', IFNULL(pm.middle_name, ''), ' ', IFNULL(pm.last_name, ''), ' ', IFNULL(pm.suffix, '')), '  *', ' ')) AS managedByName
        FROM perinfo p
        LEFT OUTER JOIN perinfo pm ON p.managedBy = pm.id
        WHERE p.id = ?;
    EOS;
    } else {
        $pfield = 'newperid';
        $personSQL = <<<EOS
    SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, p.email_addr, p.phone, p.badge_name, p.legalName, p.pronouns,
           p.address, p.addr_2, p.city, p.state, p.zip, p.country,
           'N' AS banned, p.createtime AS creation_date, 'Y' AS active, p.managedByNew, p.managedBy, p.lastVerified, 'n' AS personType,
           TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ', IFNULL(p.suffix, '')), '  *', ' ')) AS fullname,
           TRIM(REGEXP_REPLACE(CONCAT(IFNULL(pm.first_name, ''),' ', IFNULL(pm.middle_name, ''), ' ', IFNULL(pm.last_name, ''), ' ', IFNULL(pm.suffix, '')), '  *', ' ')) AS managedByName
        FROM newperson p
        LEFT OUTER JOIN newperson pm ON p.managedByNew = pm.id
        WHERE p.id = ?;
    EOS;
    }
    $personR = dbSafeQuery($personSQL, 'i', array($personId));
    if ($personR === false || $personR->num_rows == 0) {
        return false;
    }
    $info = $personR->fetch_assoc();
    $personR->free();
    // not get the count of the number required policies answered no by this person
    $pQ = <<<EOS
SELECT IFNULL(count(*), 0) AS requiredMissing
FROM policies p
LEFT OUTER JOIN memberPolicies m ON (m.policy = p.policy AND m.conid = ? AND IFNULL(m.$pfield, -1) = ?)
WHERE p.ACTIVE = 'Y'  AND p.required = 'Y' AND IFNULL(m.response, 'N') = 'N';
EOS;
    $pR = dbSafeQuery($pQ, 'ii', array($conid, $personId));
    $missingPolicies = 0;
    if ($pR !== false) {
        while ($pL = $pR->fetch_assoc()) {
            $missingPolicies += intval($pL['requiredMissing']);
        }
        $pR->free();
    }
    $info['missingPolicies'] = $missingPolicies;
    return $info;
}

// timeSinceLastToken - how many seconds since the last token send for this reason to this email - to avoid flooding
// used in index for login and in account settings for attach and identity
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

