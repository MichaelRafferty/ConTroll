<?php
require("lib/base.php");

$method='login';
$con = get_conf("con");
$conid=$con['id'];

if(!isset($_SESSION['user']) ||
  !check_atcon($_SESSION['user'], $_SESSION['passwd'], $method, $conid)) {
    if(isset($_POST['user']) && isset($_POST['passwd']) &&
      check_atcon($_POST['user'], $_POST['passwd'], $method, $conid)) {
	    //var_dump($_POST);
        $_SESSION['user']=$_POST['user'];
        $_SESSION['passwd']=$_POST['passwd'];
        $_SESSION['printer']=$_POST['printer'];
    } else {
        unset($_SESSION['user']);
        unset($_SESSION['passwd']);
        unset($_SESSION['printer']);
        unset($_SESSION['perms']);
    }
}

$page = "Atcon Registration Site Login";

page_init($page, 'index',
    /* css */ array(),
    /* js  */ array()
    );



if(isset($_GET['action']) && $_GET['action']=='logout') {
    unset($_SESSION['user']);
    unset($_SESSION['passwd']);
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

} ?>
</body>
</html>
