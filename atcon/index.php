<?php
require("lib/base.php");
require_once('lib/login.php');

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
            $_SESSION['printer']=$_POST['printer'];
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

$page = "Atcon Registration Site Login";

page_init($page, 'index',
    /* css */ array(),
    /* js  */ array()
    );



if(isset($_GET['action']) && $_GET['action']=='logout') {
    unset($_SESSION['user']);
    unset($_SESSION['userhash']);
    unset($_SESSION['printer']);
    unset($_SESSION['perms']);
    echo "<script>window.location.href=window.location.pathname</script>";
}

if(!isset($_SESSION['user'])) {
?>
<div class="container-fluid mt-4">
    <form method='POST'>
        <div class="row">
            <div class="col-sm-2">
                User Badge Id:
            </div>
            <div class="col-sm-4">
                 <input type='number' name='user' class="no-spinners" style="width: 80px;" min="1", max="999999" />
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2">
                Password:
            </div>
            <div class="col-sm-4">
                <input type='password' name='passwd' />
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2">
                Badge Printer Number:
            </div>
            <div class="col-sm-4">
                 <input type='number' name='printer' class="no-spinners" style="width: 40px;" min="0", max="99" />
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2 mt-2">
                <input type='submit' value='Login' />
            </div>
        </div>
    </form>
</div>
<?php

} else if(isset($_GET['action']) && $_GET['action']=='change_passwd') {?>
    <div class='container-fluid mt-4'>
        <form method='POST'>
            <div class='row'>
                <div class='col-sm-2'>
                    Old Password:
                </div>
                <div class='col-sm-4'>
                    <input type='password' name='old'/>
                </div>
            </div>
            <div class='row'>
                <div class='col-sm-2'>
                    New Password:
                </div>
                <div class='col-sm-4'>
                    <input type='password' name='new'/>
                </div>
            </div>
            <div class='row'>
                <div class='col-sm-2'>
                    Confirm New Password:
                </div>
                <div class='col-sm-4'>
                    <input type='password' name='confirm_new'/>
                </div>
            </div>
            <div class='row'>
                <div class='col-sm-2 mt-2'>
                    <input type='submit' value='Login'/>
                </div>
            </div>
        </form>
    </div>
<?php } ?>
</body>
</html>
