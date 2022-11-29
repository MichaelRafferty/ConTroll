<?php
## Pull INI for variables
global $db_ini;
if (!$db_ini) {    
    $db_ini = parse_ini_file(__DIR__ . "/../../../config/reg_conf.ini", true);
    $include_path_additions = PATH_SEPARATOR . $db_ini['client']['path'] . "/../../google_client";    
}

if ($db_ini['reg']['https'] <> 0) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER["HTTPS"] != "on") {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
        exit();
    }
}
set_include_path(get_include_path(). $include_path_additions);

require_once("vendor/autoload.php");
require_once(__DIR__ . "/../../../lib/db_functions.php");
require_once(__DIR__ . "/../../../lib/ajax_functions.php");
db_connect();


function bounce_page($new_page) {
    global $db_ini;
    $url = $db_ini['google']['redirect_base'] . "/$new_page";
    header("Location: $url");
}

/*
 * google_init()
 * takes $mode reflecting "ajax" or "page" mode (do we redirect or not)
 * return current status of google session
 */
function google_init($mode) {
  global $db_ini;
  session_start();

  // bypass for testing on Development PC
  if (stripos(__DIR__, "C:\\Websites\\") !== false) {
      $token_data = array();
      $token_data['email'] = 'syd.weinstein@philcon.org'; // 'todd.dashoff@philcon.org'; // 
      $token_data['sub'] = '114007818392249665998'; //  '123'; //
      //$token_data['email'] = 'syd@philcon.org'; // 'todd.dashoff@philcon.org'; // 
      //$token_data['sub'] = '1'; //  '123'; //
      $token_data['iat'] = time();
      $token_data['exp'] = time() + 3600;
      return($token_data);
  }

  // end bypass

  // set redirect URI to current page -- maybe make this better later.
  $redirect_uri = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

  $client = new Google_Client();
  $client->setAuthConfigFile($db_ini['google']['json']);
  $client->setRedirectUri($redirect_uri);
  $client->addScope('email');
  $client->setAccessType('offline');
  $client->setApprovalPrompt('force');

    //unset id_token if logging out.
    if(isset($_REQUEST['logout'])) {
        unset($_SESSION['id_token_token']);
        $client->revokeToken();
    }


    //handle code response
    if (isset($_GET['code'])) {
        $client->authenticate($_GET['code']);
        $token = $client->getAccessToken();
        // store in the session also
        $_SESSION['id_token_token'] = $token;
        // redirect back to the example
        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
    }

    /************************************************
    If we have an access token, we can make
    requests, else we generate an authentication URL.
    ************************************************/
    if (!empty($_SESSION['id_token_token'])
          && isset($_SESSION['id_token_token'])
    ) {
        $client->setAccessToken($_SESSION['id_token_token']);
        $token_data = $client->verifyIdToken();
        if(is_array($token_data) && array_key_exists('exp', $token_data) && ($token_data['exp'] - time() < 900)) {
            $client->refreshToken($_SESSION['id_token_token']['refresh_token']);
        }
    } else {
        $authUrl = $client->createAuthUrl();
        if($mode == "page") { // we're on a page
            header('Location: ' . $authUrl);
        } else {
            return $authUrl;
        }
    }

    /************************************************
    If we're signed in we can go ahead and retrieve
    the ID token, which is part of the bundle of
    data that is exchange in the authenticate step
    - we only need to do a network call if we have
    to retrieve the Google certificate to verify it,
    and that can be cached.
    ************************************************/
    $token = $client->getAccessToken();
    if ($token) {
        $client->setAccessToken($token);
        $token_data = $client->verifyIdToken();
        return($token_data);
    } else {
        return false;
    }
}

function isWebRequest()
{
return isset($_SERVER['HTTP_USER_AGENT']);
}

function page_init($title, $css, $js, $auth) {
    global $db_ini;
// auth gets the token in need_login
    if (is_array($auth) && array_key_exists('email', $auth)) {
        newUser($auth['email'], array_key_exists('sub', $auth) ? $auth['sub'] : '');
    }
    
    if(isWebRequest()) { 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title><?php echo $title . '--' . $db_ini['con']['conname']?> Reg</title>
    <link href='/css/jquery-ui-1.13.1.css' rel='stylesheet' type='text/css' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
  
    <?php
    if(isset($css) && $css != null) { foreach ($css as $sheet) {
        ?><link href='<?php echo $sheet; ?>' 
                rel=stylesheet type='text/css' /><?php
    }}
                                                 ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script type='text/javascript' src='/javascript/jquery-min-3.60.js'></script>
    <script type='text/javascript' src='/javascript/jquery-ui.min-1.13.1.js'></script>
    <?php
    if(isset($js) && $js != null) { foreach ($js as $script) {
        ?><script src='<?php echo $script; ?>' 
                type='text/javascript'></script><?php
    }}
    ?>
</head>
<body>
    <?php
    page_head($title, $auth);
    con_info($auth);
    tab_bar($auth, $title);
    }
}

function page_head($title, $auth) {
    global $db_ini;
    ?>

    <div class="container-fluid">
        <div class="row titlebar" id='titlebar'>
            <div class="col-sm-9">
                <h1 class='title'>
                    <?php echo $db_ini['con']['conname']?> Reg Controller <?php echo $title; ?> page
                </h1>
            </div>
            <div class="col-sm-3">
                <button class="btn btn-light" id="login" style="float: right;" onclick="window.location.href='<?php echo $auth == null ? "index.php?logout" : "?logout"; ?>'">
                    <?php echo $auth == null ? "Login" : "Logout " . $auth['email']; ?>
                </button>
            </div>         
        </div>
    <?php if ($db_ini['reg']['test']==1) { ?>

        <div class="row">
            <h2 class='text-danger'><strong>This Page is for test purposes only</strong></h2>
        </div>   
    <?php } ?>
<?php
}

function con_info($auth) {
    if(is_array($auth) && checkAuth(array_key_exists('sub', $auth) ? $auth['sub'] : null, 'overview')) {
        $con = get_con();
        $count_res = dbQuery("select count(*) from reg where conid='".$con['id']."';");
        $badgeCount = fetch_safe_array($count_res);
        $count_res = dbQuery("select count(*) from reg where conid='".$con['id']."' AND price <= ifnull(paid,0);");
        $unlockCount = fetch_safe_array($count_res);
  
?>

        <div class="row" id='regInfo'>
            <div class="col-sm-auto">
                <span id='regInfoCon' class='left'>Con: 
                    <span class='blocktitle'> <?php echo $con['label']; ?> </span>
                    <small><?php echo $badgeCount[0] . " Badges (" . $unlockCount[0] . " Ready)"; ?></small>
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

        <nav class="navbar navbar-light navitem navbar-expand-lg mb-2">
            <div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto p-0">
                    <li>
                         <a class="nav-link navitem <?php echo $active; ?>" <?php echo $ariainfo; ?> href="index.php">Home</a>
                    </li> 
                    <?php foreach ($page_list as $pageInfo) {
                        $p = $pageInfo['name'];
                        $d = $pageInfo['display'];
                        $active = $page == $p ? "active" : "";
                        $ariainfo = $page == $p ? 'aria-current="page"' : '';
                    ?>
                    <li>
                         <a class="nav-link navitem <?php echo $active; ?>" <?php echo $ariainfo; ?> href="<?php echo $p; ?>.php"><?php echo $d; ?></a>
                    </li>
                    <?php  } ?>
                </ul>
            </div>
        </nav>
    <?php
}

function page_foot($title) {
    ?>

    </div>
</body>
</html>
<?php
}

function countConflicts($sub) {
    if(checkAuth($sub, "people")) {
        $countQ = "SELECT count(*) from newperson WHERE perid IS NULL;";
        $countA = dbQuery($countQ);
        if(is_null($countA)) { return 0; }
        $count = fetch_safe_array($countA);
        return $count[0];
    }
    return 0;
}

function paymentDialogs() {
  $con = get_conf('con');
  $taxRate = $con['taxRate'];
  ?>
  <script>
    $(function() {
    $('#cashPayment').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Cash Payment Window"
    })
    $('#creditPayment').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Creditcard Payment Window"
    })
    $('#checkPayment').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Check Payment Window"
    });
    $('#discountPayment').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Discount Window"
    });
    $('#signature').dialog({
      autoOpen: false,
      width: 300,
      height: 500,
      modal: true,
      title: "Receipt"
    });
    $('#receipt').dialog({
      autoOpen: false,
      width: 300,
      height: 500,
      modal: true,
      title: "Receipt"
    });
    });
  </script>
  <style>
    ui-dialog { padding: .3em; }
  </style>
  <div id='signature' class='dialog'>
    <div id='signatureHolder'>
    </div>
    <button id='signaturePrint' class='bigButton' 
        onclick='checkSignature($("#signatureHolder").data("transid"),
                                $("#signatureHolder").data("payment"));'>
        Reprint Signature Form
    </button>
    <button id='signatureComplete' class='bigButton' 
        onclick='checkReceipt($("#signatureHolder").data("transid"));
                 $("#signature").dialog("close");'>
        Print Receipt
    </button>
    <button id='signatureClose' class='bigButton'
        onclick='$("#signature").dialog("close");'>
        Close
    </button>
  </div>
  <div id='receipt' class='dialog'>
    <div id='receiptHolder'>
    </div>
    <button id='receiptPrint' class='bigButton' 
        onclick='checkReceipt($("#receiptHolder").data("transid"));'>
        Reprint Receipt
    </button>
    <button id='receiptComplete' class='bigButton' 
        onclick='completeTransaction("transactionForm");
                 $("#receipt").dialog("close");'>
        Complete Transaction
        </button>
  </div>
  <div id='discountPayment' class='dialog'>
    <form id='discountPaymentForm' action='javascript:void(0);'>
      TransactionID: <span id='discountTransactionId'></span>
      <hr/>
      <table class='center'>
        <tr>
          <td>SubTotal</td>
          <td width=50></td>
          <td id='discountPaymentSub' class='right' ></td>
        </tr>
        <tr style='border-bottom: 1px solid black;'>
          <td>+ <?php echo $taxRate; ?>% Tax</td>
          <td width=50></td>
          <td id='discountPaymentTax' class='right'></td>
        </tr>
        <tr>
          <td>Total</td>
          <td width=50></td>
          <td id='discountPaymentTotal' class='right'></td>
        </tr>
      </table>
      <div>
        <input required='required' class='right' type='text' size=10 name='amt' id='discountAmt'/>Amount
      </div>
      <div>
        <input required='required' class='right' type='text' size=20 name='notes' id='discountDesc'/>Note
      </div>
      <input id='discountPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#discountPaymentForm") && makePayment("discount");'/>
    </form>
  </div>
  <div id='checkPayment' class='dialog'>
    <form id='checkPaymentForm' action='javascript:void(0);'>
      TransactionID: <span id='checkTransactionId'></span>
      <hr/>
      <table class='center'>
        <tr>
          <td>SubTotal</td>
          <td width=50></td>
          <td id='checkPaymentSub' class='right' ></td>
        </tr>
        <tr style='border-bottom: 1px solid black;'>
          <td>+ <?php echo $taxRate; ?>% Tax</td>
          <td width=50></td>
          <td id='checkPaymentTax' class='right'></td>
        </tr>
        <tr>
          <td>Total</td>
          <td width=50></td>
          <td id='checkPaymentTotal' class='right'></td>
        </tr>
      </table>
      <div><input required='required' class='right' type='text' size=10 id='checkNo'/>
      Check #</div>
      <div>
        <input required='required' class='right' type='text' size=10 name='amt' id='checkAmt'/>Amount
      </div>
      <div>
        <input class='right' type='text' size=20 name='notes' id='checkDesc'/>Note
      </div>
      <input id='checkPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#checkPaymentForm") && makePayment("check");'/>
    </form>
  </div>
  <div id='cashPayment' class='dialog'>
    <form id='cashPaymentForm' action='javascript:void(0);'>
      TransactionID: <span id='cashTransactionId'></span>
      <hr/>
      <table class='center'>
        <tr>
          <td>SubTotal</td>
          <td width=50></td>
          <td id='cashPaymentSub' class='right' ></td>
        </tr>
        <tr style='border-bottom: 1px solid black;'>
          <td>+ <?php echo $taxRate; ?>% Tax</td>
          <td width=50></td>
          <td id='cashPaymentTax' class='right'></td>
        </tr>
        <tr>
          <td>Total</td>
          <td width=50></td>
          <td id='cashPaymentTotal' class='right'></td>
        </tr>
      </table>
      <div>
        <input required='required' class='right' type='text' size=10 name='amt' id='cashAmt'/>Amount
      </div>
      <div>
        <input class='right' type='text' size=20 name='notes' id='cashDesc'/>Note
      </div>
      <input id='cashPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#cashPaymentForm") && makePayment("cash");'/>
    </form>
  </div>
  <div id='creditPayment' class='dialog'>
    <form id='creditPaymentForm' action='javascript:void(0);'>
      TransactionID: <span id='creditTransactionId'></span>
      <hr/>
      <table class='center'>
        <tr>
          <td>SubTotal</td>
          <td width=50></td>
          <td id='creditPaymentSub' class='right' ></td>
        </tr>
        <tr style='border-bottom: 1px solid black;'>
          <td>+ <?php echo $taxRate; ?>% Tax</td>
          <td width=50></td>
          <td id='creditPaymentTax' class='right'></td>
        </tr>
        <tr>
          <td>Total</td>
          <td width=50></td>
          <td id='creditPaymentTotal' class='right'></td>
        </tr>
      </table>
      <div><input required='required' class='right' type='text' size=10 name='amt' id='creditAmt'/>Amount</div>
      <?php /* <div><input class='right' type='password' size=4 name='track' id='creditTrack'/>CC Data</div> */ ?>
      <div><input required='required' class='right' type='text' name='notes' id='creditDesc' autocomplete='off'/>Transaction</div>
      <input id='creditPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#creditPaymentForm") && makePayment("credit");'/>
      </div>
    </form>
  </div>
<?php
}

?>
