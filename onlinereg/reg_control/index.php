<?php
require_once "lib/base.php";
//initialize google session
$need_login = google_init("page");

$page = "Home";

page_init($page,
    /*css*/ array('css/base.css'),
    /*js*/  null,
            $need_login);

if($need_login == false) {
?>
    <div id='main'>You haven't Logged in</div>
    <?php
} else {
    ?>
    <div id='main'> You successfully Logged in. <br/>
    If you need more access please email the appropriate person with the email and sub value listed below:
    <pre><?php //var_export($need_login);
        //echo var_export($need_login);
        //echo var_export($_SESSION['id_token_token']);
        echo "Email: " . $need_login['email'];
        echo "\n";
        echo "Sub: " . $need_login['sub'];
        echo "\n";
        echo "Google Check: " . date('c', $need_login['iat']);
        echo "\n";
        echo "Current Time: " . date('c');
        echo "\n";
        echo "Next Check: " . date('c', $need_login['exp']);
        echo "\n";
        echo "Refresh Token: " . (isset($_SESSION['id_token_token']['refresh_token'])?"Exists":"Doesn't Exist");
        echo "\n";
    ?></pre>
    </div>
    <?php
}

page_foot($page);
?>
