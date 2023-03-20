<?php
require_once "lib/base.php";

$method='index';
$con = get_conf("con");
$conid=$con['id'];

global $perms;
$perms = array();

if(!isset($_SESSION['userhash'])) {
    if(isset($_POST['user']) && isset($_POST['passwd'])) {
        $access = login($_POST['user'], $_POST['passwd'], $conid);
        if ($access['success'] == 1) {
            $perms = $access['auth'];
            $_SESSION['user']=$_POST['user'];
            // printers passed as display_name:::server:-:printer:-:printer type
            // receipt printer
            if (isset($_POST['receipt_printer'])) {
                $pr = $_POST['receipt_printer'];
                if ($pr != '') {
                    $printer = explode(':::', $pr);
                    $server = explode(':-:', $printer[1]);
                    $_SESSION['receiptPrinter'] = array($printer[0], $server[0], $server[1], $server[2]);
                } else {
                    $_SESSION['receiptPrinter'] = array('None', '', '', '');
                }
            }

            // badge printer
            if (isset($_POST['badge_printer'])) {
                $pr = $_POST['badge_printer'];
                if ($pr != '') {
                    $printer = explode(':::', $pr);
                    $server = explode(':-:', $printer[1]);
                    $_SESSION['badgePrinter'] = array($printer[0], $server[0], $server[1], $server[2]);
                } else {
                    $_SESSION['badgePrinter'] = array('None', '', '', '');
                }
            }

            // general printer
            if (isset($_POST['generic_printer'])) {
                $pr = $_POST['generic_printer'];
                if ($pr != '') {
                    $printer = explode(':::', $pr);
                    $server = explode(':-:', $printer[1]);
                    $_SESSION['genericPrinter'] = array($printer[0], $server[0], $server[1], $server[2]);
                } else {
                    $_SESSION['genericPrinter'] = array('None', '', '', '');
                }
            }

            $_SESSION['userhash'] = $access['userhash'];
        }
    } else {
        unset($_SESSION['user']);
        unset($_SESSION['userhash']);
        unset($_SESSION['printer']);
        unset($_SESSION['perms']);
    }
} else {
    check_atcon('login', $conid);
}

$page = "Atcon Login";

page_init($page, 'index',
    /* css */ array(),
    /* js  */ array('js/index.js')
    );

if(isset($_GET['action']) && $_GET['action']=='logout') {
    unset($_SESSION['user']);
    unset($_SESSION['userhash']);
    unset($_SESSION['printer']);
    unset($_SESSION['perms']);
    echo "<script>window.location.href=window.location.pathname</script>";
}

if(!isset($_SESSION['user'])) {
    // get printer list for this location
    $printerQ = <<<EOS
SELECT p.serverName, p.printerName, p.printerType, s.location, s.address
FROM printers p
JOIN servers s ON (s.serverName = p.serverName)
WHERE p.active = 1 and s.active = 1;
EOS;
    $printers = [];
    $genericFound = false;
    $receiptFound = false;
    $printerR = dbQuery($printerQ);
    while ($printer = fetch_safe_assoc($printerR)) {
        $printers[$printer['serverName'] . ':' . $printer['printerName']] = $printer;
        if (mb_substr($printer['printerType'], 0, 7) == 'generic') $genericFound = true;
        if (mb_substr($printer['printerType'], 0, 7) == 'receipt') $receiptFound = true;
    }
    mysqli_free_result($printerR);
?>
<div class="container-fluid mt-4">
    <form method='POST' class="form-floating">
        <div class="row">
            <div class="col-sm-6">
                <div class='form-floating mb-3'>
                    <input type='number' name='user' class="no-spinners form-control" min="1" max="999999" placeholder="Your badge number"  style="width:150px;" required/>
                    <label for="user">User Badge Id:</label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class='form-floating mb-3'>
                    <input type='password' name='passwd' class="form-control" placeholder="Assigned Password" required/>
                    <label for='passwd'>Password:</label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2">
                <label for='badge_printer'>Badge Printer:</label>
            </div>
            <div class="col-sm-auto">
                <select name="badge_printer" id="badge_printer">
                    <option value="">None</option>
                    <?php foreach ($printers as $key => $printer) {
                       if (mb_substr($printer['printerType'], 0, 5) == 'badge') {
                           echo '<option value="' . $key . ':::' . $printer['address'] . ':-:' . $printer['printerName'] . ':-:' . $printer['printerType'] . '">' . $key . "</option>\n";
                       }
                    } ?>
                </select>
            </div>
        </div>
        <div class='row mt-2'>
            <div class='col-sm-2'>
                <label for='receipt_printer'>Receipt Printer:</label>
            </div>
            <div class='col-sm-auto'>
                <select name='receipt_printer' id='receipt_printer'>
                    <option value=''>None</option>
                    <?php foreach ($printers as $key => $printer) {
                        if (mb_substr($printer['printerType'], 0, 7) == 'receipt') {
                            echo '<option value="' . $key . ':::' . $printer['address'] . ':-:' . $printer['printerName'] . ':-:' . $printer['printerType'] . '">' . $key . "</option>\n";
                        }
                    }
                    foreach ($printers as $key => $printer) {
                        if (mb_substr($printer['printerType'], 0, 7) == 'generic') {
                            echo '<option value="' . $key . ':::' . $printer['address'] . ':-:' . $printer['printerName'] . ':-:' . $printer['printerType'] . '">' . $key . "</option>\n";
                        }
                    } ?>
                </select>
            </div>
        </div>
        <?php if ($genericFound) { ?>
        <div class='row mt-2'>
            <div class='col-sm-2'>
                <label for='generic_printer'>General Printer:</label>
            </div>
            <div class='col-sm-auto'>
                <select name='generic_printer' id='generic_printer'>
                    <option value=''>None</option>
                    <?php foreach ($printers as $key => $printer) {
                        if (mb_substr($printer['printerType'], 0, 7) == 'generic') {
                            echo '<option value="' . $key . ':::' . $printer['address'] . ':-:' . $printer['printerName'] . '">' . $key . "</option>\n";
                        }
                    } ?>
                </select>
            </div>
        </div>
        <?php } else echo '<input type="hidden" name="generic_printer" id="generic_printer" value=""/>' ?>
        <div class="row mt-4">
            <div class="col-sm-auto">
                <button type='submit' class="btn btn-primary">Login</button>
            </div>
        </div>
    </form>
</div>
<?php

} else if(isset($_GET['action']) && $_GET['action']=='change_passwd') {?>
    <input type="hidden" name='idval' id='idval' value="<?php echo $_SESSION['userhash']; ?>"
    <div class='container-fluid mt-4'>
        <div class='row mt-4'>
            <div class='col-sm-6'>
                <div class='form-floating mb-3'>
                    <input type='password' name='old_password' id="old_password" class='form-control' placeholder="Existing Password" required/>
                    <label for="old_password">Current Password:</label>
                </div>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-6'>
                <div class='form-floating mb-3'>
                    <input type='password' name='new_password' id="new_password" class='form-control' placeholder='New Password' required/>
                    <label for='new_password'>New Password:</label>
                </div>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-6'>
                <div class='form-floating mb-3'>
                    <input type='password' name='confirm_new' id="confirm_new" class='form-control' placeholder='Confirm New Password' required/>
                    <label for='confirm_new'>Confirm New Password:</label>
                </div>
            </div>
        </div>
        <div class='row'>
            <div class='col-sm-2 mt-2'>
                <button type="button" class="btn btn-primary btn-sm" id="change_pw_btn" onclick="change_pw();">Change Password</button>
            </div>
            <div class='col-sm-auto mt-2' id='result_message'></div>
        </div>
    </div>
<?php } else if (isset($_POST) && isset($_POST['old']) && $_POST['old']=='') {
    echo "In Change Password Submit";
}
function login($user, $passwd, $conid): array {
    //error_log("login.php");

    if (isset($user) && isset($passwd)) {
        $passwd = trim($passwd);
        $q = <<<EOS
SELECT a.auth, u.userhash, u.passwd
FROM atcon_user u 
JOIN atcon_auth a ON (a.authuser = u.id)
WHERE u.perid=? AND u.conid=?;
EOS;
        $r = dbSafeQuery($q, 'si', array($user, $conid));
        $upasswd = null;
        if ($r->num_rows > 0) {
            $response['success'] = 1;
            $auths = array();
            while ($l = fetch_safe_assoc($r)) {
                $auths[] = $l['auth'];
                $response['userhash'] = $l['userhash'];
                if ($upasswd == null) {
                    $upasswd = $l['passwd'];
                    if ($upasswd != $passwd && !password_verify($passwd, $upasswd)) {
                        $response['success'] = 0;
                        mysqli_free_result($r);
                        return($response);
                    }
                }
            }
            $response['auth'] = $auths;
            mysqli_free_result($r);
            if ($passwd == $upasswd) /* update old style password */ {
                $dbpasswd = password_hash($passwd, PASSWORD_DEFAULT);
                $q = <<<EOS
UPDATE atcon_user
SET passwd = ?
WHERE perid = ? AND conid = ?;
EOS;

                $r = dbSafeCmd($q, 'sii', array($dbpasswd, $user, $conid));
                $response['updated'] = $r;
            }
        } else {
            $response['success'] = 0;
        }
    } else {
        $response['success'] = 0;
    }

    return ($response);
}
?>
</body>
</html>
