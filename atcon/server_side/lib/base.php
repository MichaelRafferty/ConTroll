<?php
## Pull INI for variables
$ini = parse_ini_file("../../../config/reg_conf.ini", true);

###
# initialize Google Connection
###
#set_include_path(get_include_path(). PATH_SEPARATOR . $ini['client']['path'] . "/lib/google_client/src");
#require_once("Google/autoload.php");

require_once("db_functions.php");
db_connect();


function bounce_page($new_page) {
    global $ini;
    $url = $ini['google']['redirect_base'] . "/$new_page";
    header("Location: $url");
}

/*
 * google_init()
 * takes $mode reflecting "ajax" or "page" mode (do we redirect or not)
 * return current status of google session
 */
function google_init($mode) {
  global $ini;
  session_start();

  $client = new Google_Client();
  $client->setAuthConfigFile($ini['google']['json']);
  $client->setRedirectUri($ini['google']['redirect_base'] . "/index.php");
  $client->addScope('email');
  $client->setAccessType('offline');


if($mode == "page") { // if this is a page we can redirect stuff
/************************************************
  If we're logging out we just need to clear our
  local access token in this case
 ************************************************/
  if(isset($_REQUEST['logout'])) {
    $client->setAccessToken($_SESSION['access_token']);
    $client->revokeToken();
    unset($_SESSION['access_token']);
    session_destroy();
    return false;
  }


/************************************************
  If we have a code back from the OAuth 2.0 flow,
  we need to exchange that with the authenticate()
  function. We store the resultant access token
  bundle in the session, and redirect to ourself.
 ************************************************/
  if (isset($_GET['code'])) {
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
  }

} // the rest of this is fine for ajax calls

/************************************************
  If we have an access token, we can make
  requests, else we generate an authentication URL.
 ************************************************/
  if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $tokens = json_decode($_SESSION['access_token']);
    $client->setAccessToken($_SESSION['access_token']);
    if($client->isAccessTokenExpired()) {
        if(!isset($tokens->refresh_token)) { 
            $client->revokeToken();
            unset($_SESSION['access_token']);
            return false; 
        }
        $client->refreshToken($tokens->refresh_token);
    }
    $_SESSION['access_token'] = $client->getAccessToken();
    if(isset($tokens->refresh_token)) { 
        $t_hold = json_decode($_SESSION['access_token']);
        $t_hold->refresh_token = $tokens->refresh_token;
        $_SESSION['access_token'] = json_encode($t_hold);
    }
  } else {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
  }

  if($client->getAccessToken()) {
    try {
        return($client->verifyIdToken()->getAttributes());
    } catch (Exception $e) {
        return false;
    }
  }
}

function certSeal() {
?>
<!-- Begin DigiCert site seal HTML and JavaScript -->
<div id="DigiCertClickID_WdDqlpia" data-language="en_US">
  <a href="https://www.digicert.com/ssl-certificate.htm">SSL Certificate</a>
</div>
<script type="text/javascript">
  var __dcid = __dcid || [];__dcid.push(["DigiCertClickID_WdDqlpia", "3", "s", "black", "WdDqlpia"]);(function(){var cid=document.createElement("script");cid.async=true;cid.src="//seal.digicert.com/seals/cascade/seal.min.js";var s = document.getElementsByTagName("script");var ls = s[(s.length - 1)];ls.parentNode.insertBefore(cid, ls.nextSibling);}());
</script>
<!-- End DigiCert site seal HTML and JavaScript -->
<?php
}

function isWebRequest()
{
return isset($_SERVER['HTTP_USER_AGENT']);
}

function page_init($title, $css, $js, $need_login) {
    $auth = $need_login['payload'];
    if(isWebRequest()) { 
    ?>
<!doctype html>
<html>
<head>
    <title><?php echo $title; ?> -- Balticon Reg</title>
    <?php
    if(isset($css) && $css != null) { foreach ($css as $sheet) {
        ?><link href='<?php echo $sheet; ?>' 
                rel=stylesheet type='text/css' /><?php
    }}
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
?>
    <div id='titlebar'>
        <a id='login' class='right button' 
            <?php if($auth==null) {
                ?>href='index.php'>Login<?php
            } else { 
                ?>href='?logout'>Logout <?php echo $auth['email']; 
            } ?>
        </a>
        <h1 class='title'>
            Balticon Reg Controller <?php echo $title; ?> page
        </h1>
    </div>
<?php
}

function con_info($auth) {
    if(checkAuth($auth['sub'], 'overview')) {
        $con = get_con();
        $count_res = dbQuery("select count(*) from reg where conid='".$con['id']."';");
        $badgeCount = fetch_safe_array($count_res);
        $count_res = dbQuery("select count(*) from reg where conid='".$con['id']."' AND locked='N';");
        $unlockCount = fetch_safe_array($count_res);
  
        ?>
    <div id='regInfo'>
        <span id='regInfoCon' class='left'>
        Con: <span class='blocktitle'> <?php echo $con['label']; ?> </span>
        <small><?php 
            echo $badgeCount[0] . " Badges (" . 
                $unlockCount[0] . " Ready)";
        ?></small>
        </span>
    </div>
        <?php
    } else { 
        ?><div id='regInfo'>Please log in for convention information.</div><?php
    }
}

function tab_bar($auth, $page) {
    $page_list = getPages($auth['sub']);
    ?>
    <div class='tabbar'>
        <span class='
            <?php if($page=="Home") { 
                echo 'activeTab'; 
            } else { 
                echo 'tab'; 
            }?>'>
            <a href="index.php">Home</a></span><?php
        for($i = 0 ; $i < count($page_list); $i++) {
            $p = $page_list[$i];
            $thisTab = ($p == $page);
            ?><span class='<?php 
            if($thisTab) { echo "activeTab"; } else { echo "tab"; }
            ?>'><a href='<?php echo $p . ".php";?>'><?php echo $p; ?></a>
        </span><?php
        }
    ?>
    </div>
    <?php
}

function page_foot($title) {
    ?>
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
        <input required='required' class='right' type='text' size=10 name='amt' id='discountAmt'></input>Amount
      </div>
      <div>
        <input required='required' class='right' type='text' size=20 name='notes' id='discountDesc'></input>Note
      </div>
      <input id='discountPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#discountPaymentForm") && makePayment("discount");'></input>
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
      <div><input required='required' class='right' type='text' size=10 id='checkNo'></input>
      Check #</div>
      <div>
        <input required='required' class='right' type='text' size=10 name='amt' id='checkAmt'></input>Amount
      </div>
      <div>
        <input class='right' type='text' size=20 name='notes' id='checkDesc'></input>Note
      </div>
      <input id='checkPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#checkPaymentForm") && makePayment("check");'></input>
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
        <input required='required' class='right' type='text' size=10 name='amt' id='cashAmt'></input>Amount
      </div>
      <div>
        <input class='right' type='text' size=20 name='notes' id='cashDesc'></input>Note
      </div>
      <input id='cashPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#cashPaymentForm") && makePayment("cash");'></input>
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
      <div><input required='required' class='right' type='text' size=10 name='amt' id='creditAmt'></input>Amount</div>
      <?php /* <div><input class='right' type='password' size=4 name='track' id='creditTrack'></input>CC Data</div> */ ?>
      <div><input required='required' class='right' type='text' name='notes' id='creditDesc' autocomplete='off'></input>Transaction</div>
      <input id='creditPay' class='payBtn' type='submit' value='Pay' onClick='testValid("#creditPaymentForm") && makePayment("credit");'></input>
      </div>
    </form>
  </div>
<?php
}

function callOut($url, $data) {
   $ch = curl_init($url);
   curl_setopt($ch, CURLOPT_POST, TRUE);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Length: ' . strlen($data)
   ));
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
   $result = curl_exec($ch);
   curl_close($ch);
}

function var_error_log( $object=null ){
    ob_start();                    // start buffer capture
    var_dump( $object );           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log( $contents );        // log contents of the result of var_dump( $object )
}

?>
