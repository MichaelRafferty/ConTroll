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
    <div id='main'>
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-auto">
                    You successfully Logged in.
                </div>
            </div>
            <div class="row">
                <div class="col-sm-auto">
                    If you need more access please email the appropriate person with the email and sub value listed below:
                </div>
            </div>
            <div class="row">
                <div class="col-sm-auto mt-4 mb-0">
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
                        ?> </pre>
                </div>
            </div>
        </div>
    </div>
    <?php
}

page_foot($page);
?>
