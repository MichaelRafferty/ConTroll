<?php
require_once(__DIR__ . '/../../lib/global.php');
## Pull INI for variables
global $monthLengths, $oneYearInterval, $appSessionPrefix;

//              XXX, Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec
$monthLengths = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
$oneYearInterval = date_interval_create_from_date_string('1 year');

if (loadConfFile())
    $include_path_additions = PATH_SEPARATOR . getConfValue('client', 'path', '.') . '/../Composer';

if (getConfValue('reg', 'https') <> 0) {
    if(!isset($_SERVER['HTTPS']) || $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}
set_include_path(get_include_path(). $include_path_additions);

require_once("vendor/autoload.php");
require_once(__DIR__ . "/../../lib/db_functions.php");
require_once(__DIR__ . "/../../lib/cipher.php");
require_once(__DIR__ . '/../../lib/jsVersions.php');
require_once(__DIR__ . "/../../lib/ajax_functions.php");
db_connect();
$appSessionPrefix = 'Ctrl/ConTroll/';
if (!session_start()) {
    session_regenerate_id(true);
    session_start();
}

function bounce_page($new_page) {
    $url = getConfValue('controll','redirect_base', '') . "/$new_page";
    header("Location: $url");
}

function isWebRequest() : bool {
    return isset($_SERVER['HTTP_USER_AGENT']);
}

function page_init($title, $css, $js, $authToken) : void {
    global $portalJSVersion, $libJSversion, $controllJSversion, $globalJSversion, $atJSversion, $exhibitorJSversion;

    $cdn = getTabulatorIncludes();
    $tabbs5=$cdn['tabbs5'];
    $tabcss=$cdn['tabcss'];
    $tabjs=$cdn['tabjs'];
    $bs5js=$cdn['bs5js'];
    $bs5css=$cdn['bs5css'];
    $jqjs=$cdn['jqjs'];
    $jquijs=$cdn['jquijs'];
    $jquicss=$cdn['jquicss'];
    $pageTitle = $title . '--' . getConfValue('con', 'conname');

echo <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>$pageTitle Reg</title>
    <link rel='icon' type='image/x-icon' href='/lib/favicon.ico'>
    <link href='$jquicss' rel='stylesheet' type='text/css' />
    <link href='$bs5css' rel='stylesheet' type='text/css' />
    <link href="csslib/bootstrap-icons.css?v=$controllJSversion" rel='stylesheet' type='text/css' />
EOF;
    if(isset($css) && $css != null) {
        foreach ($css as $sheet) {
?><link href='<?php echo "$sheet?v=$controllJSversion"; ?>' rel=stylesheet type='text/css' />
<?php
        }
    }
echo <<<EOF
    <script src='$bs5js'></script>
    <script type='text/javascript' src='$jqjs'></script>
    <script type='text/javascript' src='$jquijs'></script>
    <script type='text/javascript' src="jslib/global.js?v=$globalJSversion"></script>
    <script type='text/javascript' src="js/base.js?v=$controllJSversion"></script>
EOF;
    if(isset($js) && $js != null) {
        foreach ($js as $script) {
            if (str_starts_with($script, 'jslib/'))
                $callout = "$script?v=$libJSversion";
            else if (str_starts_with($script, 'js/'))
                $callout = "$script?v=$controllJSversion";
            else
                $callout = $script;
    ?>
    <script src='<?php echo $callout;?>' type='text/javascript'></script>
<?php
        }
    }
?>
</head>
<body>
    <?php
    page_head($title, $authToken);
    con_info($authToken);
    tab_bar($authToken, $title);
}

function page_head($title, $authToken) : void {
    $displayQ = <<<EOS
SELECT display
FROM auth
WHERE name = ?;
EOS;
    $dR = dbSafeQuery($displayQ, 's', array($title));
    if ($dR === false) {
        $display = $title;
    } else if ($dR->num_rows != 1) {
        $display = $title;
        $dR->free();
    } else {
        $display = $dR->fetch_row()[0];
    }
    ?>

    <div class="container-fluid mb-2">
        <div class="row titlebar" id='titlebar'>
            <div class="col-sm-9">
                <h1 class='title'>
                    <?php echo getConfValue('con', 'conname');?> ConTroll <?php echo $display; ?> page
                </h1>
            </div>
            <?php if ($authToken != null && $authToken->isLoggedIn()) { ?>
            <div class="col-sm-3">
                <button class="btn" id="login" style="background-color: #ccc; float: right;" onclick="window.location.href='index.php?logout';">
                    Logout <?php echo $authToken->getEmail(); ?>
                </button>
            </div>
            <?php } ?>
        </div>
    <?php if (getConfValue('reg','test') == 1) { ?>

        <div class="row">
            <h2 class='text-danger'><strong>This Page is for test purposes only</strong></h2>
        </div>   
    <?php } ?>
<?php
}

function con_info($authToken) : void {
    $unlockCount = 0;
    $badgeCount = 0;
    $con = get_con();
    if($authToken != null && $authToken->checkAuth('overview')) {
        $cQ = <<<EOS
SELECT status, count(*) AS num
FROM reg
WHERE conid = ? AND status IN ('paid', 'plan', 'unpaid')
GROUP BY status;
EOS;
        $count_res = dbSafeQuery($cQ, 'i', array($con['id']));
        while ($countRow = $count_res->fetch_row()) {
            $badgeCount += $countRow[1];
            if ($countRow[0] == 'paid')
                $unlockCount += $countRow[1];
        }
?>

        <div class="row" id='regInfo'>
            <div class="col-sm-auto">
                <span id='regInfoCon' class='left'>Con: 
                    <span class='blocktitle'> <?php echo $con['label']; ?> </span>
                    <small><?php echo $badgeCount . " Badges (" . $unlockCount . " Ready)"; ?></small>
                </span>
            </div>       
        </div>
    <?php } else if ($authToken == null) { ?>
        <div class="row" id='regInfo'>
            <div class="col-sm-auto">
                <span>Con:
                    <span class='blocktitle'> <?php echo $con['label']; ?> </span>
                    <span class="h3 ms-4">Your login is about to expire and will be refreshed.</span>
            </div>
        </div>
    <?php } else { ?>
        <div class="row" id='regInfo'>
            <div class="col-sm-auto">
                <span>Con:
                    <span class='blocktitle'> <?php echo $con['label']; ?> </span>
                    <small>Please log in for convention information.</small>
            </div>
        </div>
    <?php
    }
}

function tab_bar($authToken, $page) : void {
    if ($authToken == null)
        $id = 'Refresh';
    else
        $id = $authToken->getAuthId();

    if ($id != 'Not Logged In' && $id != 'Refresh') {
        $page_list = [];
        $sql = <<<EOS
SELECT DISTINCT A.id, A.name, A.display, A.sortOrder
FROM user U
JOIN user_auth UA ON (U.id = UA.user_id)
JOIN auth A ON (A.id = UA.auth_id)
WHERE U.google_sub = ? AND A.page='Y'
ORDER BY A.sortOrder;
EOS;
        $pQ = dbSafeQuery($sql, 's', array($id));
        if ($pQ !== false) {
            while ($new_auth = $pQ->fetch_assoc()) {
                $page_list[] = $new_auth;
            }
        }
    } else {
        $page_list = array();
    }
    $active = $page == "Home" ? "active" : "";
    $ariainfo = $page == "Home" ? 'aria-current="page"' : '';

    ?>
        <nav class="navbar navbar-light navitem navbar-expand-lg mb-2 ps-2">
            <div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navbarNav">
                <button class="btn btn-outline-dark navitem me-3 <?php echo $active; ?>" type='button' <?php echo $ariainfo; ?>
                        style='border-bottom-right-radius: 20px;' onclick='window.location="index.php";'>Home
                </button>
                <?php foreach ($page_list as $pageInfo) {
                    $p = $pageInfo['name'];
                    $d = $pageInfo['display'];
                    $active = $page == $p ? "active" : "";
                    $ariainfo = $page == $p ? 'aria-current="page"' : '';
                ?>
                <button class="btn btn-outline-dark navitem me-3 <?php echo $active; ?>" type='button' <?php echo $ariainfo; ?>
                        style='border-top-left-radius: 20px; border-bottom-right-radius: 20px;' onclick="window.location='<?php echo $p; ?>.php';"><?php echo $d;
                        ?></button>
                <?php } ?>
            </div>
        </nav>
    </div>
    <?php
}

function page_foot($title = "") : void {
    ?>
    </div>
    <div class="container-fluid">
        <div class='row mt-2'>
            <?php drawBug(12); ?>
        </div>
    </div>
</body>
</html>
<?php
}

// reg_ uses the atcon ajax renders
function RenderErrorAjax($message_error) : void
{
    global $return500errors;
    if (isset($return500errors) && $return500errors) {
        Render500ErrorAjax($message_error);
    } else {
        echo "<div class=\"error-container alert\"><span>$message_error</span></div>\n";
    }
}

function Render500ErrorAjax($message_error) : void
{
    // pages which know how to handle 500 errors are expected to format the error message appropriately.
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo "$message_error";
}

// draw a bs5 modal popup for editing a field in tinymce
function bs_tinymceModal() : void {
    $html = <<<EOS
    <div id='tinymce-modal' class='modal modal-xl fade' tabindex='-1' aria-labelledby='Edit field in TinyMCE' aria-hidden='true' style='--bs-modal-width: 80%;'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-bg-primary'>
                <div class='modal-title'>
                    <strong id='editTitle'>Edit Field</strong>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body' style='padding: 4px; background-color: lightcyan;'>
                <div id="editTable" hidden="hidden">editTable init</div>
                <div id="editField" hidden="hidden">editField init</div>
                <div id="editIndex" hidden="hidden">editIndex init</div>
                <div id="editClass" hidden="hidden">editClass init</div>
                <div class='container-fluid'>
                    <div class="row">
                        <div class="col-sm-12" id="editFieldName">Editing ...</div>
                    </div>
                    <div class="row">
                         <div class='col-sm-12' id='editFieldValue'><textarea id='editFieldArea'>Content</textarea></div>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <button class='btn btn-sm btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                <button class='btn btn-sm btn-primary' id='saveEdit' onClick='saveEdit()'>Save Edit</button>
            </div>
            <div id='result_message_edit' class='mt-4 p-2'></div>
        </div>
    </div>
</div>
EOS;
    echo $html;
}

function startEndDateTimeToNextYear($datestr) : string {
    global $monthLengths, $oneYearInterval;

    $date = date_create($datestr);
    [$day, $month, $year, $dow, $leapYear, $time] = explode(',', date_format($date, 'd,m,Y,w,L,H:i:s'));
    $nextYear = date_add($date, $oneYearInterval);
    [$nYear, $nyDow, $nyLeapYear] = explode(',', date_format($nextYear, 'Y,w,L'));

    // rules;
    //  add one year
    //      if last day of month stop there
    //      else make same day of week
    //
    $lastDay = $monthLengths[$month + 0];
    if ($month == 2 && $leapYear == 1) {
        $lastDay++;
    }
    if ($day != 1) {
        if ($day == $lastDay && $month == 2) {
            $day = $monthLengths[2] + $nyLeapYear;
        }
        else if ($day != $lastDay) {
            $day += $dow - $nyDow;
            if ($day > $monthLengths[$month])
                $day -= 7;
            if ($day < 1)
                $day += 7;
        }
    }

    return "$nYear-$month-$day $time";
}

function startEndDateToNextYear($datestr) : string {
    global $monthLengths, $oneYearInterval;

    $date = date_create($datestr);
    [$day, $month, $year, $dow, $leapYear] = explode(',', date_format($date, 'd,m,Y,w,L'));
    $nextYear = date_add($date, $oneYearInterval);
    [$nYear, $nyDow, $nyLeapYear] = explode(',', date_format($nextYear, 'Y,w,L'));

    // rules;
    //  add one year
    //      if last day of month stop there
    //      else make same day of week
    //
    $lastDay = $monthLengths[$month + 0];
    if ($month == 2 && $leapYear == 1) {
        $lastDay++;
    }
    if ($day != 1) {
        if ($day == $lastDay && $month == 2) {
            $day = $monthLengths[2] + $nyLeapYear;
        }
        else if ($day != $lastDay) {
            $day += $dow - $nyDow;
        }
    }

    return "$nYear-$month-$day";
}
