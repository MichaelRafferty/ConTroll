<?php
## Pull INI for variables
global $db_ini;
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

require_once(__DIR__ . '/../../lib/db_functions.php');
require_once(__DIR__ . '/../../lib/ajax_functions.php');
db_connect();
session_start();

date_default_timezone_set("America/New_York");

function isWebRequest()
{
    return isset($_SERVER) && isset($_SERVER['HTTP_USER_AGENT']);
}

/*
function ageDialog($con)
{
    $ageListR = callHome("ageList.php", "POST", "con=" . $con['id']);
    $ageList = json_decode($ageListR,true);
    //echo "<div>"; var_dump($ageList) ; echo "</div>";
?>
<script>
    var prices = {
        <?php foreach($ageList as $age) {
            echo $age['memAge'] . " : " . $age['price'] . ", ";
        } ?>
        all : 0}
</script>
<div id='getAge' class='dialog'>
    <form id='getAgeForm' action='javascript:void(0);'>
        <input type='hidden' id='getAgeBadgeId' />
        <input type='hidden' id='getAgeBadgeWhich' />
        <input type='hidden' id='getAgeAction' />
        <select id='getAgeSelect'>
            <?php foreach($ageList as $age) {
                echo "<option value='" . $age['memAge'] . "'>"
                    . $age['label'] . " ($" . $age['price'] . ")</option>\n";
            } ?>
        </select>
        <input type='submit' id='getAgeSubmit' value='Set Age'
            onclick='addBadgeAddon($("#getAgeAction").val(),
                                   $("#getAgeBadgeId").val(),
                                   $("#getAgeBadgeWhich").val(),
                                   $("#getAgeSelect").val(), true);
                     $("#getAge").dialog("close");
                     return false;' />
    </form>
</div>
<?php }
*/
function page_init($title, $tab, $css, $js)
{
    $con = get_conf('con');
    $label = $con['label'];
    global $perms;
    if (isWebRequest()) {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>
        <?php echo $title . ' -- ' . $label; ?> Reg
    </title>
    <link href='/css/jquery-ui-1.13.1.css' rel='stylesheet' type='text/css' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
    <link href="css/base.css" rel='stylesheet' type='text/css' />
        <?php  if (isset($css) && $css != null) {
            foreach ($css as $sheet) { ?>
    <link href='<?php echo $sheet; ?>' rel=stylesheet type='text/css' />
            <?php }
        } ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script type='text/javascript' src='/js/jquery-min-3.60.js'></script>
    <script type='text/javascript' src='/js/jquery-ui.min-1.13.1.js'></script>
    <script type='text/javascript' src='/js/base.js'></script>
        <?php
        if (isset($js) && $js != null) {
            foreach ($js as $script) {
                ?><script src='<?php echo $script; ?>'
        type='text/javascript'></script><?php
            }
        }
        ?>
</head>
<body>
    <div class="container-fluid bg-primary text-white">
        <div class="row">
            <div class="col-sm-9">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-12">
                            <h1 class='title'>
                                    <?php echo $label; ?> Registration <?php echo $title; ?> page
                            </h1>
                        </div>
                    </div>
            <?php
        if (isset($_SESSION['userhash'])) {
            ?>
                    <div class="row">
                        <div class="col-sm-12 text-bg-primary">
                            <nav class="navbar navbar-dark bg-primary navbar-expand-lg">
                                <div>
                                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                                        <span class="navbar-toggler-icon"></span>
                                    </button>
                                </div>
                                <div class="collapse navbar-collapse" id="navbarNav">
                                    <ul class="navbar-nav me-auto p-0">
                                        <?php if (in_array('data_entry', $perms)) { ?>
                                        <li>
                                            <a class="nav-link navitem <?php echo $tab == "checkin" ? "active" : ""; ?>" <?php echo $tab == "checkin" ? 'aria-current="page"' : ""; ?> href="regpos.php">Reg Check In</a>
                                        </li>
                                        <?php  }
                                        if (in_array('cashier', $perms)) { ?>
                                        <li>
                                            <a class="nav-link navitem <?php echo $tab == "cashier" ? "active" : ""; ?>" <?php echo $tab == "cashier" ? 'aria-current="page"' : ""; ?> href="regpos.php?mode=cashier">Reg Cashier</a>
                                        </li>
                                        <?php  }
                                        if (in_array('artshow', $perms)) { ?>
                                        <li>
                                            <a class="nav-link navitem <?php echo $tab == "artshow" ? "active" : ""; ?>" <?php echo $tab == "artshow" ? 'aria-current="page"' : ""; ?> href="artsales.php">Artshow Cashier</a>
                                        </li>
                                        <?php  }
                                        if (in_array('data-entry', $perms) || in_array('cashier', $perms)) { ?>
                                        <li>
                                            <a class="nav-link navitem <?php echo $tab == "printform" ? "active" : ""; ?>" <?php echo $tab == "printform" ? 'aria-current="page"' : ""; ?> href="printform.php">Printform</a>
                                        </li>
                                        <?php  }
                                        if (in_array('manager', $perms)) { ?>
                                        <li>
                                            <a class="nav-link navitem <?php echo $tab == "admin" ? "active" : ""; ?>" <?php echo $tab == "admin" ? 'aria-current="page"' : ""; ?> href="admin.php">Administrator</a>
                                        </li>
                                        <li>
                                            <a class="nav-link navitem <?php echo $tab == "mockupCheckin" ? "active" : ""; ?>" <?php echo $tab == "mockupCheckin" ? 'aria-current="page"' : ""; ?> href="mockup2.php">Check-in Mockup</a>
                                        </li>
                                        <li>
                                            <a class="nav-link navitem <?php echo $tab == "mockupCashier" ? "active" : ""; ?>" <?php echo $tab == "mockupCashier" ? 'aria-current="page"' : ""; ?> href="mockup2.php?mode=cashier">Cashier Mockup</a>
                                        </li>
                                        <li>
                                            <a class="nav-link navitem <?php echo $tab == "atconArtInventory" ? "active" : ""; ?>" <?php echo $tab == "atconArtInventory" ? 'aria-current="page"' : ""; ?> href="newArtInventory.php?mode=inventory">Art Invetory Dev</a>
                                        </li>

                                        <?php  } ?>
                                        <li>
                                            <a class="nav-link navitem" <?php echo $tab == "change_password" ? "active" : ""; ?>" <?php echo $tab == "change_password" ? 'aria-current="page"' : ""; ?> href="index.php?action=change_passwd">Change Password</a>
                                        </li>
                                        <li>
                                            <a class="nav-link navitem" <?php echo $tab == "logout" ? "active" : ""; ?>" <?php echo $tab == "logout" ? 'aria-current="page"' : ""; ?> href="index.php?action=logout">Logout</a>
                                        </li>
                                    </ul>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3 text-bg-primary align-self-end">
                User: <?php echo $_SESSION['user']; ?><br/>
                Badge: <?php echo $_SESSION['badgePrinter'][0]; ?><br/>
                Receipt: <?php echo $_SESSION['receiptPrinter'][0]; ?><br/>
                General: <?php echo $_SESSION['genericPrinter'][0]; ?>
            </div>
        </div>
    </div>
    <?php
        } else {
            ?>
                </div>
            </div>
        </div>
    </div>
        <?php
        }
    } else {
        ?>
        <div id='titlebar' class="container-fluid bg-primary text-white">
            <h1 class='title'>
                <?php echo $label; ?> Registration <?php echo $title; ?> page
            </h1>
        </div>
        <?php
    }
}

function con_info()
{
    $con = get_conf("con");
##        $count_res = dbQuery("select count(*) from reg where conid='".$con['id']."';");
##        $badgeCount = fetch_safe_array($count_res);
##        $count_res = dbQuery("select count(*) from reg where conid='".$con['id']."' AND locked='N';");
##        $unlockCount = fetch_safe_array($count_res);

    ?>
    <div id='regInfo'>
        <span id='regInfoCon' class='left'>
            Con: <span class='blocktitle'>
                <?php echo $con['label']; ?>
            </span>
            <small>
                <?php echo "con_info() doesn't work yet";
##           echo $badgeCount[0] . " Badges (" .
##                $unlockCount[0] . " Ready)";
                ?>
            </small>
        </span>
    </div>
    <?php
}

/*
function callHome($script, $method, $data) {
#print("<br/>" . $script ." :" . strtoupper($method) . " :'". $data . "'<br/>");
    $access = get_conf('user');
    $url = $access['server'] . "/" . $script;
    #error_log($url);
#print("<br/>server: " . $url . "<br/>");
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_USERPWD, $access['user'] . ":" . $access['passwd']);
    if(strtoupper($method)=='POST') {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Length: ' . strlen($data))
    );

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    $result = curl_exec($ch);
    if($result === false) {
        print("CURL Error: " . curl_error($ch) . " (" . curl_errno($ch) . ")<br>");
    }

    curl_close($ch);

    return($result);
}
*/

function page_foot($title)
{
    ?>
</body>
</html>
    <?php
}

function paymentDialogs()
{
 /* obsolete method, to be phased out, only used by art sales right now */
    $con = get_conf('con');
    $taxRate = array_key_exists('taxRate', $con) ? $con['taxRate'] : 0;
    ?>
<script>
    $(function() {
    $('#getAge').dialog({
        autoOpen: false,
        width: 400,
        height: 310,
        title: "Set Age"
    })
    $('#cashPayment').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Cash Payment Window"
    })
    $('#offline').dialog({
      autoOpen: false,
      width: 325,
      height: 310,
      modal: true,
      title: "Credit Card Payment Window"
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
    <?php ageDialog($con); ?>
<div id='signature' class='dialog'>
    <div id='signatureHolder'></div>
    <button id='signaturePrint' class='bigButton'
        onclick='checkSignature($("#signatureHolder").data("transid"),
                                $("#signatureHolder").data("payment"),
                                false);'>
        Reprint Signature Form
    </button>
    <button id='signatureComplete' class='bigButton'
        onclick='checkReceipt($("#signatureHolder").data("transid"));
                 $("#signature").dialog("close");'>
        Print Receipt
    </button>
</div>
<div id='receipt' class='dialog'>
    <div id='receiptHolder'></div>
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
        <hr />
        <table class='center'>
            <tr>
                <td>SubTotal</td>
                <td width=50></td>
                <td id='discountPaymentSub' class='right'></td>
            </tr>
            <tr style='border-bottom: 1px solid black;'>
                <td>
                    + <?php echo $taxRate; ?>% Tax
                </td>
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
            <input required='required' class='right' type='text' size=10 name='amt' id='discountAmt' />Amount
        </div>
        <div>
            <input required='required' class='right' type='text' size=20 name='notes' id='discountDesc' />Note
        </div>
        <input id='discountPay' class='payBtn' type='submit' value='Pay' onclick='testValid("#discountPaymentForm") && makePayment("discount");' />
    </form>
</div>
<div id='checkPayment' class='dialog'>
    <form id='checkPaymentForm' action='javascript:void(0);'>
        TransactionID: <span id='checkTransactionId'></span>
        <hr />
        <table class='center'>
            <tr>
                <td>SubTotal</td>
                <td width=50></td>
                <td id='checkPaymentSub' class='right'></td>
            </tr>
            <tr style='border-bottom: 1px solid black;'>
                <td>
                    + <?php echo $taxRate; ?>% Tax
                </td>
                <td width=50></td>
                <td id='checkPaymentTax' class='right'></td>
            </tr>
            <tr>
                <td>Total</td>
                <td width=50></td>
                <td id='checkPaymentTotal' class='right'></td>
            </tr>
        </table>
        <div>
            <input required='required' class='right' type='text' size=10 id='checkNo' />
            Check #
        </div>
        <div>
            <input required='required' class='right' type='text' size=10 name='amt' id='checkAmt' />Amount
        </div>
        <div>
            <input class='right' type='text' size=20 name='notes' id='checkDesc' />Note
        </div>
        <input id='checkPay' class='payBtn' type='submit' value='Pay' onclick='testValid("#checkPaymentForm") && makePayment("check");' />
    </form>
</div>
<div id='cashPayment' class='dialog'>
    <form id='cashPaymentForm' action='javascript:void(0);'>
        TransactionID: <span id='cashTransactionId'></span>
        <hr />
        <table class='center'>
            <tr>
                <td>SubTotal</td>
                <td width=50></td>
                <td id='cashPaymentSub' class='right'></td>
            </tr>
            <tr style='border-bottom: 1px solid black;'>
                <td>
                    + <?php echo $taxRate; ?>% Tax
                </td>
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
            <input required='required' class='right' type='text' size=10 name='amt' id='cashAmt' />Amount
        </div>
        <div>
            <input class='right' type='text' size=20 name='notes' id='cashDesc' />Note
        </div>
        <input id='cashPay' class='payBtn' type='submit' value='Pay' onclick='testValid("#cashPaymentForm") && makePayment("cash");' />
    </form>
</div>
<div id='offline' class='dialog'>
    <form id='offlinePaymentForm' action='javascript:void(0);'>
        TransactionID: <span id='creditTransactionId'></span>
        <hr />
        <table class='center'>
            <tr>
                <td>SubTotal</td>
                <td width=50></td>
                <td id='offlinePaymentSub' class='right'></td>
            </tr>
            <tr style='border-bottom: 1px solid black;'>
                <td>
                    + <?php echo $taxRate; ?>% Tax
                </td>
                <td width=50></td>
                <td id='offlinePaymentTax' class='right'></td>
            </tr>
            <tr>
                <td>Total</td>
                <td width=50></td>
                <td id='offlinePaymentTotal' class='right'></td>
            </tr>
        </table>
        <input type='hidden' name='amt' id='offlineAmt' />
        <div>
            <input disabled='disabled' class='right' type='text' size=10 name='view' id='offlineView' />Amount
        </div>
        <div>
            <input required='optional' class='right' type='text' size=10 name='cc_approval_code' id='offlineCode' autocomplete='off' />Approval Code
        </div>
        <input id='offlinePay' class='payBtn' type='submit' value='Pay' onclick='testValid("#offlinePaymentForm") && makePayment("offline");' />
        <div id='creditPayment' class='dialog'>
            <form id='creditPaymentForm' action='javascript:void(0);'>
                TransactionID: <span id='creditTransactionId'></span>
                <hr />
                <table class='center'>
                    <tr>
                        <td>SubTotal</td>
                        <td width=50></td>
                        <td id='creditPaymentSub' class='right'></td>
                    </tr>
                    <tr style='border-bottom: 1px solid black;'>
                        <td>
                            + <?php echo $taxRate; ?>% Tax
                        </td>
                        <td width=50></td>
                        <td id='creditPaymentTax' class='right'></td>
                    </tr>
                    <tr>
                        <td>Total</td>
                        <td width=50></td>
                        <td id='creditPaymentTotal' class='right'></td>
                    </tr>
                </table>
                <input type='hidden' name='amt' id='creditAmt' />
                <div>
                    <input disabled='disabled' class='right' type='text' size=10 name='view' id='creditView' />Amount
                </div>
                <div>
                    <input required='required' class='right' type='password' size=4 name='track' id='creditTrack' autocomplete='off' />CC
                </div>
                <input id='creditPay' class='payBtn' type='submit' value='Pay' onclick='testValid("#creditPaymentForm") && makePayment("credit");' />
        </div>
        </form>
</div>
    <?php
}

$perms = [];
function check_atcon($method, $conid)
{
    global $perms;
    if (count($perms) == 0) {
        $q = <<<EOS
SELECT a.auth
FROM atcon_user u 
JOIN atcon_auth a ON (a.authuser = u.id)
WHERE u.perid=? AND u.userhash=? AND u.conid=?;
EOS;
        $r = dbSafeQuery($q, 'ssi', [$_SESSION['user'], $_SESSION['userhash'], $conid]);
        if ($r->num_rows > 0) {
            while ($l = fetch_safe_assoc($r)) {
                $perms[] = $l['auth'];
            }
        }
    }
    return in_array($method, $perms);
}
/*
function initReceipt() {
  $con = get_conf('con');
  $width = 30;
  $pad = floor($width/2 + strlen("Receipt")/2);
  $return = "\n" . sprintf("%${pad}s", "Receipt") . "\n";
  $pad = floor($width/2 + strlen($con['label'])/2);
  $return = "\n" . sprintf("%${pad}s", $con['label']) . "\n";

  date_default_timezone_set("America/New_York");
  $date = date("M j, Y H:m:s");
  $pad =  floor($width/2 + strlen($date)/2);
  $return .= sprintf("%${pad}s", $date) . "\n";

  $return .= "\n" . str_repeat('-',$width) . "\n";

    return $return;
}

function closeReceipt($info) {
  $width=30;

  $type=$info['type'];
  $sub = $info['price'];
  $tax = $info['tax'];
  $total=$info['withtax'];
  $amt = $info['amount'];
  $change = $info['change_due'];
  $desc = $info['description'];
  $cc_num = $info['cc'];
  $cc_code = $info['cc_approval_code'];

  $return = "\n";
  if($sub>0) {
  $subStr = sprintf("%01.2f", $sub);
  $pad = $width - strlen("Subtotal:");
  $return .= "Subtotal:" . sprintf("%${pad}s",$subStr) . "\n";

  $subTax = sprintf("%01.2f", $tax);
  $pad = $width - strlen("+ Tax:");
  $return .= "+ Tax:" . sprintf("%${pad}s",$subTax) . "\n";
  }

  $subTotal = sprintf("%01.2f", $total);
  $pad = $width - strlen("Total:");
  $return .= "Total:" . sprintf("%${pad}s",$subTotal) . "\n";

  $subAmt = sprintf("%01.2f", $amt);
  $pad = $width - strlen("Payment Received: $type");
  $return .= "Payment Received" . sprintf("%${pad}s",$subAmt) . "\n";
  if($type == "check") {
    $return .= "  - Check #$desc\n";
  }
  if($type == "credit") {
    $return .= "  - Card: " . $cc_num . "\n";
    $return .= "  - Auth: " . $cc_code . "\n";
  }
  $return .= "\n";

  $subCng = sprintf("%01.2f", $change);
  $pad = $width - strlen("Change:");
  $return .= "Change:" . sprintf("%${pad}s",$subCng) . "\n";
  $return .= "\n\n\n\n\n";

  return $return;
}
*/
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
/*
function passwdForm() {
?>
<div id='passwordWrap'>
    <button id='logout' class='right'
        onclick='window.location.href=window.location.pathname+"?action=logout"'>
        Logout
    </button>
    <span class='blocktitle' onclick='$("#chpw").toggle()'>Change Password</span>
    <div id='chpw'>
        <form action='javascript:void(0)' id='chpwForm'>
            Current Password: <input type='password' name='passwd' /><br />
            New Password: <input type='password' name='newpasswd' id='newpw1' /><br />
            New PW again: <input type='password' id='newpw2' /><br />
            <input type='submit' onclick='pw_script("#chpwForm");' />
        </form>
    </div>
</div>
<?php
}
*/
?>
