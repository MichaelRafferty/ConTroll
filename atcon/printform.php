<?php
require_once "lib/base.php";

$page = "Register";

page_init($page, 'printform',
    /* css */ array('css/registration.css','css/atcon.css'),
    /* js  */ array('js/atcon.js')
    );

$con = get_conf("con");
$conid=$con['id'];
$method='cashier';

if(!isset($_SESSION['user']) || 
  !check_atcon($_SESSION['user'], $_SESSION['passwd'], $method, $conid)) {
    if(isset($_POST['user']) && isset($_POST['passwd']) && isset($_POST['printer']) &&
      check_atcon($_POST['user'], $_POST['passwd'], $method, $conid)) {
        $_SESSION['user']=$_POST['user'];
        $_SESSION['passwd']=$_POST['passwd'];
        $_SESSION['printer']=$_POST['printer'];
    } else {
        unset($_SESSION['user']);
        unset($_SESSION['passwd']);
    }
}

if(isset($_GET['action']) && $_GET['action']=='logout') {
    unset($_SESSION['user']);
    unset($_SESSION['passwd']);
    echo "<script>window.location.href=window.location.pathname</script>";
}

if(!isset($_SESSION['user'])) {
?>
<form method='POST'>
User Badge Id: <input type='text' name='user'/><br/>
Password: <input type='password' name='passwd'/><br/>
Badge Printer: <input type='number' name='printer'/><br/>
<input type='submit' value='Login'/>
</form>
<?php

} else {

?>
<?php passwdForm(); ?>

<form id='newBadge' action='javascript:void(0);'>
<label>Badge Name: <input type='text' size=36 name='badge_name' id='badge_name'/></label><br/>
Category:<select name='category' id='category'>
    <option>voter</option>
    <option>NoRights</option>
    </select><br/>
<label>Badge Id: <input type='number' size='6' name='id' id='badge_id'/></label><br/>
Duration: <select name='type' id='type'>
    <option value='full'>Full Convention</option>
    <option value='oneday'>One Day</option>
    </select>
Day: <select name='day' id='day'>
    <option>Wednesday</option>
    <option>Thursday</option>
    <option>Friday</option>
    <option>Saturday</option>
    <option>Sunday</option>
    </select><br/>
Age: <select name='age' id='age'>
    <option value='adult'>Adult</option>
    <option value='youth'>Youth</option>
    <option value='child'>Child</option>
    <option value='kit'>Kid in Tow</option>
    </select><br/>

    <input type='submit' onclick='printTestLabel();'/>
</form>

</div>
<?php
}
?>
<pre id='test'></pre>
<div id='alert' class='popup'>
    <div id='alertInner'>
    </div>
    <button class='center' onclick='$("#alert").hide();'>Close</button>
</div>
</body></html>
