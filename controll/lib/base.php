<?php
require_once(__DIR__ . '/../../lib/global.php');
## Pull INI for variables
global $db_ini, $monthLengths, $oneYearInterval;
//              XXX, Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec
$monthLengths = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
$oneYearInterval = date_interval_create_from_date_string('1 year');

if (!$db_ini) {    
    $db_ini = loadConfFile();
    $include_path_additions = PATH_SEPARATOR . $db_ini['client']['path'] . "/../Composer";
}

if (getConfValue('reg', 'https') <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
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
session_start();


function bounce_page($new_page) {
    $url = getConfValue('google','redirect_base') . "/$new_page";
    header("Location: $url");
}

/*
 * google_init()
 * takes $mode reflecting "ajax" or "page" mode (do we redirect or not)
 * return current status of google session
 */
function google_init($mode) {
  global $db_ini;

  // bypass for testing on Development PC
  if (stripos(__DIR__, "/Users/syd/") !== false && $_SERVER['SERVER_ADDR'] == "127.0.0.1") {
      $token_data = array();
      $token_data['email'] = 'syd.weinstein@philcon.org';
      $token_data['sub'] = '114007818392249665998';
      $token_data['iat'] = time();
      $token_data['exp'] = time() + 3600;
      $_SESSION['user_id'] = 88;
      $_SESSION['user_perid'] = 21389;
      return($token_data);
  }

  // end bypass

  // set redirect URI to current page -- maybe make this better later.
  $redirect_base = "https://" . $_SERVER['HTTP_HOST'];
  $redirect_uri = $redirect_base . "/index.php";
  $state = $_SERVER['PHP_SELF'];

  $client = new Google\Client();
  $client->setAuthConfigFile($db_ini['google']['json']);
  $client->addScope('email');
  $client->setAccessType('offline');
  $client->setState($state);
  $client->setRedirectUri($redirect_uri);
  //$client->setApprovalPrompt('force');

  //unset id_token if logging out.
  if(isset($_REQUEST['logout'])) {
      web_error_log("logout", "google");
      unset($_SESSION['access_token']);
      $client->revokeToken();
      $client->setPrompt('select_account');
      $client->setState('logout');
      $auth_url = $client->createAuthUrl();
      header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
      exit();
  }

    //handle code responses
    if (array_key_exists('code', $_GET)) { // need to handle other auth responses
        $code = $_GET['code'];
        $decode_count = 0;
        while(substr($code, 1,1) == '%') {
            $code = urldecode($code);
            if($decode_count > 3) { break; } else {$decode_count++;}
        }
        if($decode_count > 0) { web_error_log("decode called $decode_count times" . substr($code, 1, 1)); }
        $client->authenticate($code);
        $token = $client->getAccessToken();
        $state = "";
        if(array_key_exists('state', $_GET)) {
            // if I want to do anything with state, this is the place
            $state = $_GET['state']; 
        } else {
            $state = "N/A";
        }
        web_error_log("WITH google token: state='$state'", "google");
        // store in the session also
        $_SESSION['access_token'] = $token;

        if(!$token) {
            var_dump($token);
            exit();
            }
        // redirect back to the example
        // this is probably where to use state...
        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL)); exit();
    }

  if(isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
    web_error_log("with access token", "google");
  } else { //if(!array_key_exists('code', $_GET)) {
    $client->setState($state);
    $client->setRedirectUri($redirect_uri);
    if(array_key_exists('user_email', $_SESSION) && ($_SESSION['user_email'])) { $client->setLoginHint($_SESSION['user_email']); }
    $auth_url = $client->createAuthUrl();
    if($mode=='page') {
      header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
      if(array_key_exists('logout', $_REQUEST)) {
        web_error_log("weird logout", "google");
        exit();
      }
      web_error_log("Page WITHOUT access token from: " . $_SERVER['PHP_SELF'], "google");
    exit();
    } else { 
      web_error_log("AJAX WITHOUT access token from: " . $_SERVER['PHP_SELF'], "google");
      return false; 
    }
  }


    if($token_data = $client->verifyIdToken()) {
        web_error_log("verified token for: " . $token_data['email'], "google");
        return($token_data);
    } else {
        web_error_log("UNVERIFIED token from: " . $_SERVER['PHP_SELF'], "google");
        unset($_SESSION['access_token']);
        if($mode=='page') {
          $auth_url = $client->createAuthUrl();
          header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL)); exit();
        } else { return false; }
    }
}

function isWebRequest()
{
return isset($_SERVER['HTTP_USER_AGENT']);
}

function page_init($title, $css, $js, $auth) {
    global $portalJSVersion, $libJSversion, $controllJSversion, $globalJSversion, $atJSversion, $exhibitorJSversion;

    // auth gets the token in need_login
    if (is_array($auth) && array_key_exists('email', $auth)) {
        newUser($auth['email'], array_key_exists('sub', $auth) ? $auth['sub'] : '');
    }
    
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
    <link href='$bs5css' rel='stylesheet'/>
    <link href='csslib/bootstrap-icons.css' rel='stylesheet' type='text/css' />
EOF;
    if(isset($css) && $css != null) {
        foreach ($css as $sheet) {
?><link href='<?php echo $sheet; ?>' rel=stylesheet type='text/css' />
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
    page_head($title, $auth);
    con_info($auth);
    tab_bar($auth, $title);
}

function page_head($title, $auth) {
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
            <div class="col-sm-3">
                <button class="btn btn-light" id="login" style="float: right;" onclick="window.location.href='<?php echo $auth == null ? "index.php?logout" : "?logout"; ?>'">
                    <?php echo $auth == null ? "Login" : "Logout " . $auth['email']; ?>
                </button>
            </div>         
        </div>
    <?php if (getConfValue('reg','test') == 1) { ?>

        <div class="row">
            <h2 class='text-danger'><strong>This Page is for test purposes only</strong></h2>
        </div>   
    <?php } ?>
<?php
}

function con_info($auth) {
    $unlockCount = 0;
    $badgeCount = 0;
    if(is_array($auth) && checkAuth(array_key_exists('sub', $auth) ? $auth['sub'] : null, 'overview')) {
        $con = get_con();
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
    <?php } else { ?>
        <div class="row" id='regInfo'>
            <div class="col-sm-auto">Please log in for convention information.</div>
        </div>
    <?php
    }
}

function tab_bar($auth, $page) {
    if (is_array($auth) && array_key_exists('sub', $auth)) {
        $page_list = getPages($auth['sub']);
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

function page_foot($title = "") {
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
function RenderErrorAjax($message_error)
{
    global $return500errors;
    if (isset($return500errors) && $return500errors) {
        Render500ErrorAjax($message_error);
    } else {
        echo "<div class=\"error-container alert\"><span>$message_error</span></div>\n";
    }
}

function Render500ErrorAjax($message_error)
{
    // pages which know how to handle 500 errors are expected to format the error message appropriately.
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo "$message_error";
}

// draw a bs5 modal popup for editing a field in tinymce
function bs_tinymceModal() {
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

function startEndDateTimeToNextYear($datestr) {
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
        }
    }

    return "$nYear-$month-$day $time";
}

    function startEndDateToNextYear($datestr) {
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
