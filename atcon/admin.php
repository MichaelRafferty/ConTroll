<?php

require("lib/base.php");

if (!isset($_SESSION['user'])) {
    header("Location: /index.php");
    exit(0);
}

$page = "Atcon Administration";

page_init($page, 'admin',
    /* css */ array('css/registration.css'),
    /* js  */ array('js/admin.js')
    );

$con = get_conf("con");
$conid=$con['id'];
$method='manager';

//var_dump($_SESSION);
//echo $conid;

?>
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
            <tr><th>Reg Checkin</th><th>Cashier</th><th>Art Inventory</th><th>Art Sales</th><th>Admin</th></tr>
            <tr>
                <td><input name='data_entry' type='checkbox'/></td>
                <td><input name='register' type='checkbox'/></td>
                <td><input name='artinventory' type='checkbox'/></td>
                <td><input name='artsales' type='checkbox'/></td>
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
        <th>perid</th><th>Name</th>
        <th>Reg Checkin</th><th>Cashier</th>
        <th>Art Inventory</th><th>Art Sales</th>
        <th>Admin</th>
        <th>Update</th>
        </tr>
    </thead>
    <tbody id='users'>
    </tbody>
  </table>
    <button onclick='$("#addUser").dialog("open");' class='bigButton'>Add User</button>
</div>
<pre id='test'></pre>
