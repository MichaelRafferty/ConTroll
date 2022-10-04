<?php
require("lib/base.php");

$page = "Atcon Administration";

page_init($page,
    /* css */ array('css/jquery-ui.css',
                    'css/base.css',
                    'css/registration.css',
                   ),
    /* js  */ array('js/jquery.js',
                    'js/jquery-ui.min.js',
                    'js/d3.js',
                    'js/base.js',
                    'js/admin.js'
                   )
           );

$con = get_conf("con");
$conid=$con['id'];
$method='manager';

//var_dump($_SESSION);
//echo $conid;


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
Password: <input type='password' name='passwd/'><br/>
<input type='submit' value='Login'/>
</form>
<?php

} else {

?>
<?php passwdForm(); ?>
<script>
  $(function() {
    $('#addUser').dialog({
        title: "New User",
        autoOpen: false,
        width: 500,
        height: 300,
        modal: true
    });
  });
</script>
<div id='addUser'>
    <form id='addUserForm' action='javascript:void(0);'>
        Perid: <input name='perid'/><br/>
        Passwd: <input type='password' name='newpw'/><br/>
        <table>
            <tr><th>Reg Checkin</th><th>Cashier</th><th>Artshow</th><th>Admin</th></tr>
            <tr>
                <td><input name='data_entry' type='checkbox'/></td>
                <td><input name='register' type='checkbox'/></td>
                <td><input name='artshow' type='checkbox'/></td>
                <td><input name='manager' type='checkbox'/></td>
            </tr>
        </table>
        <input onclick='addUser()' type='Submit' value='Add Person'/>
    </form>
</div>
<div id='main'>
  <table>
    <thead>
        <tr>
        <th>perid</th><th>Name</th><th>Reg Checkin</th><th>Cashier</th>
        <th>Artshow</th><th>Admin</th><th>Update</th>
        </tr>
    </thead>
    <tbody id='users'>
    </tbody>
  </table>
    <button onclick='$("#addUser").dialog("open");' class='bigButton'>Add User</button>
</div>
<?php
}
?>
<pre id='test'></pre>
