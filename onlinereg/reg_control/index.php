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
    # create the user session variable
    $user_email = $need_login['email'];
    if (!(array_key_exists('user_email', $_SESSION) && $user_email == $_SESSION['user_email']
        && array_key_exists('user_id', $_SESSION) && $_SESSION['user_id'] != null
        && array_key_exists('user_perid', $_SESSION) && $_SESSION['user_perid'] != null
    )) {
        $_SESSION['user_email'] = $user_email;
        // get the user id for database tracking
        $usergetQ = <<<EOS
SELECT id, perid
FROM user
WHERE email = ?;
EOS;
        $usergetR = dbSafeQuery($usergetQ, 's', array($user_email));
        $userid = null;
        if ($usergetR !== false) {
            $userL = $usergetR->fetch_assoc();
            if ($userL) {
                $userid = $userL['id'];
                $perid = $userL['perid'];
            }
        }
        $_SESSION['user_id'] = $userid;
        $_SESSION['user_perid'] = $perid;
    }
    // get the version string, and the current DB patch level
    $versionFile = '../../version.txt';
    if (is_readable($versionFile)) {
        $versionText = file_get_contents("../../version.txt");
    } else {
        $versionText = "Version information not available\n";
    }
    $patchLevel = dbQuery("SELECT MAX(id) FROM patchLog;")->fetch_row()[0];
    if ($patchLevel === null || $patchLevel === false || $patchLevel < 0) {
        $patchLevel = "unavailable";
    }
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
                    If you need more access please email the appropriate person with the email and sub value listed below.<br/>
                    If your user id or user perid below is blank, not all functions will work correctly for you,
                    also email the appropriate person to have your user id or user perid is updated.
                </div>
            </div>
            <div class="row">
                <div class="col-sm-auto mt-4 mb-0">
                    <pre><?php //var_export($need_login);
                            //echo var_export($need_login);
                            //echo var_export($_SESSION['id_token_token']);
                            echo "Email: " . $need_login['email'] . "\n";
                            echo "User id: " . $_SESSION['user_id'] . "\n";
                            echo "User perid: " . $_SESSION['user_perid'] . "\n";
                            echo "Sub: " . $need_login['sub'] . "\n";
                            echo "Google Check: " . date('c', $need_login['iat']) . "\n";
                            echo "Current Time: " . date('c') . "\n";
                            echo "Next Check: " . date('c', $need_login['exp']) . "\n";
                            echo "Refresh Token: " . (isset($_SESSION['id_token_token']['refresh_token'])?"Exists":"Doesn't Exist") . "\n";
                            echo "$versionText";
                            echo "Database Patch Level: $patchLevel\n";
                        ?> </pre>
                </div>
            </div>
        </div>
    </div>
    <?php
}

page_foot($page);
?>
