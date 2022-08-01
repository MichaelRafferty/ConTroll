<?php require("lib/base.php"); ?>
<html>
<head>
<title>Balticon Atcon Registration Site</title>
</head>
<body>
<?php echo "PHP Worked";
      echo callHome("echo.php", "POST", "test=abcd"); 
      echo "<br/>";
      echo "Fail: " . callHome("login.php", "POST", "user=0&passwd=fail");
      echo "<br/>Perms: ";
      if(isset($_POST) && isset($_POST['user']) && isset($_POST['passwd'])) {
        if(check_atcon($_POST['user'], $_POST['passwd'], 'data_entry')) {
            echo "Data Entry";
        } else { echo "Not Data Entry"; }
        if(check_atcon($_POST['user'], $_POST['passwd'], 'cashier')) {
            echo " . Cashier";
        } else { echo " . Not Cashier"; }
      } else { echo "Login Below"; }
?><br/>
<a href='checkin.php'>Check-In page</a>
<br/>
<a href='register.php'>Cash Register Page</a>

<form method='POST'>
    User: <input type='text' name='user'></input><br/>
    Pass: <input type='password' name='passwd'></input></br>
    <input type='submit'></input>
</form>

</body>
</html>
