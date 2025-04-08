<?php
require_once "lib/base.php";

$method='index';
$con = get_conf("con");
$conid=$con['id'];

global $perms;
$perms = array();

if(!isSessionVar('userhash')) {
    if(isset($_POST['user']) && isset($_POST['passwd'])) {
        $access = login($_POST['user'], $_POST['passwd'], $conid);
        if ($access['success'] == 1) {
            $perms = $access['auth'];
            setSessionVar('user', $_POST['user']);
            setSessionVar('first_name', $access['first_name']);
            // printers passed as display_name:::server:-:printer:-:printer type
            $printers = ['badge', 'receipt', 'generic'];
            foreach ($printers as $prt) {
                $printer = array(
                    'name' => 'None',
                    'host' => '',
                    'queue' => '',
                    'type' => '',
                    'code' => 'UTF-8',
                );
                if (array_key_exists($prt . '_printer', $_POST) && $_POST[$prt . '_printer'] != '') {
                    $pr = $_POST[$prt . '_printer'];
                    $printerTop = explode(':::', $pr);
                    $server = explode(':-:', $printerTop[1]);
                    $printer = array (
                        'name' => $printerTop[0],
                        'host' => $server[0],
                        'queue' => $server[1],
                        'type' => $server[2],
                        'code' => $server[3],
                    );
                }
                setSessionVar($prt . 'Printer', $printer);
                $response[$prt] = $printer['name'];
            }
            setSessionVar('userhash', $access['userhash']);
        }
    } else {
        clearSession();
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
    clearSession();
    echo "<script>window.location.href=window.location.pathname</script>";
}

if(!isSessionVar('user')) {
    // get printer list for this location
?>
<div class="container-fluid mt-4">
    <form method='POST' class="form-floating">
        <div class="row">
            <div class="col-sm-6">
                <div class='form-floating mb-3'>
                    <input type='number' name='user' class="no-spinners form-control" min="1" placeholder="Your badge number"  style="width:150px;" required/>
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
        <?PHP echo Draw_Printer_Select(2); ?>
        <div class="row mt-4">
            <div class="col-sm-auto">
                <button type='submit' class="btn btn-primary">Login</button>
            </div>
        </div>
    </form>
</div>
<?php

} else if(isset($_GET['action']) && $_GET['action']=='change_passwd') {?>
    <input type="hidden" name='idval' id='idval' value="<?php echo getSessionVar('userhash'); ?>"
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
SELECT a.auth, u.userhash, u.passwd, p.first_name
FROM atcon_user u 
JOIN atcon_auth a ON (a.authuser = u.id)
JOIN perinfo p ON (u.perid = p.id)
WHERE u.perid=? AND u.conid=?;
EOS;
        $r = dbSafeQuery($q, 'si', array($user, $conid));
        $upasswd = null;
        if ($r->num_rows > 0) {
            $response['success'] = 1;
            $auths = array();
            while ($l = $r->fetch_assoc()) {
                $auths[] = $l['auth'];
                $response['userhash'] = $l['userhash'];
                $response['first_name'] = $l['first_name'];
                if ($upasswd == null) {
                    $upasswd = $l['passwd'];
                    if ($upasswd != $passwd && !password_verify($passwd, $upasswd)) {
                        $response['success'] = 0;
                        $r->free();
                        return($response);
                    }
                }
            }
            $response['auth'] = $auths;
            $r->free();
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
            if ($response['userhash'] == '' || $response['userhash'] == 'null' || $response['userhash'] == null) {
                // update userhash and retrieve it again for login
                $q = <<<EOS
UPDATE atcon_user
SET userhash = MD5(concat(id, perid))
WHERE userhash IS NULL;
EOS;
                $r = dbCmd($q);
                $q = <<<EOS
SELECT u.userhash
FROM atcon_user u 
WHERE u.perid=? AND u.conid=?;
EOS;
                $r = dbSafeQuery($q, 'si', array($user, $conid));
                $l = $r->fetch_assoc();
                $response['userhash'] = $l['userhash'];
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
