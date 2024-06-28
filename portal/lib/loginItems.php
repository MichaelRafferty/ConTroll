<?php
// draw_login - draw the login options form
function draw_login($config_vars, $result_message = '', $result_color = '') {
    ?>
 <!-- signin form (at body level) -->
    <div id='signin'>
        <div class='container-fluid form-floating'>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <h4>Please log in to continue to the Portal.</h4>
                </div>
            </div>
            <div class="row mb-2">
                <div class='col-sm-auto'>
                    <button class="btn btn-sm btn-primary" onclick="login.loginWithToken();">Login with Authentication Link via Email</button>
                </div>
            </div>
            <div id='token_email_div' hidden>
                <div class='row mt-1'>
                    <div class='col-sm-1'>
                        <label for='token_email'>*Email: </label>
                    </div>
                    <div class='col-sm-auto'>
                        <input class='form-control-sm' type='email' name='token_email' id='token_email' size='40' onchange='login.tokenEmailChanged();' required/>
                    </div>
                </div>
                <div class='row mt-2 mb-2'>
                    <div class='col-sm-1'></div>
                    <div class='col-sm-auto'>
                        <button type='button' class='btn btn-primary btn-sm' id='sendLinkBtn' onclick='login.sendLink();' disabled>Send Link</button>
                    </div>
                </div>
            </div>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <button class='btn btn-sm btn-primary' onclick='login.loginWithGoogle();'>Login with Google</button>
                </div>
            </div>
            <?php
            // bypass for testing on Development PC
    if (stripos(__DIR__, '/Users/syd/') !== false && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
                ?>
            <div class="row mt-3><div class="col-sm-12"><hr></div></div>
            <div class='row mt-2'>
                <div class='col-sm-auto'>
                    <label for='dev_email'>*Dev Email/Perid/Newperid: </label>
                </div>
                <div class='col-sm-auto'>
                    <input class='form-control-sm' type='email' name='dev_email' id='dev_email' size='40' required/>
                </div>
                <div class='col-sm-auto'>
                    <button type="button" class='btn btn-sm btn-primary' onclick='login.loginWithEmail();'>Login to Development</button>
                </div>
            </div>
            <div class='row mb-2'><div class="col-sm-12" id="matchList"></div></div>
            <?php
    } ?>
        </div>
    </div>
    <div class='container-fluid'>
        <div class='row'>
            <div class='col-sm-12 m-0 p-0'>
                <div id='result_message' class='mt-4 p-2 <?php echo $result_color; ?>'><?php echo $result_message; ?></div>
            </div>
        </div>
        <div class='row mt-2'>
            <?php drawBug(12); ?>
        </div>
    </div>
    </body>
    <script type='text/javascript'>
        var config = <?php echo json_encode($config_vars); ?>;
    </script>
</html>
<?php
}

// chooseAccountFromEmail - map an email address to a list of accounts
// email is a validated email by the validationType.
function chooseAccountFromEmail($email, $id, $linkid, $cipherInfo, $validationType) {
    global $config_vars;

    $portal_conf = get_conf('portal');

    $loginData = getLoginMatch($email, $id);
    if (!is_array($loginData)) {
        return $loginData;  // return the error message from getLoginMatch
    }
    $matches = $loginData['matches'];
    $count = count($matches);
    if ($count == 1) {
        $match = $matches[0];
        if (array_key_exists('email', $match)) {
            $email = $match['email'];
        }
        if (array_key_exists('email_addr', $match)) {
            $email = $match['email_addr'];
        }
        $id = $match['id'];
        $ts = ' ';
        if (array_key_exists('ts', $match)) {
            $ts = " with ts ". $match['ts'];
        }
        if (isSessionVar('id')) {
            // we had a prior session
            if (getSessionVar('id') != $id) {
                // not same id, treat it as a new login
                unsetSessionVar('transId');    // just in case it is hanging around, clear this
                unsetSessionVar('totalDue');   // just in case it is hanging around, clear this
                $type = 'id change login';
            } else {
                $type = 'refresh';
            }
        } else {
            $type = 'new login';
        }
        setSessionVar('id', $id);
        setSessionVar('idType', $match['tablename']);
        setSessionVar('idSource', $validationType);

        updateIdentityUsage($id, $validationType, $email);
        web_error_log("$type @ " . time() . "$ts for $email/$id via $validationType");
        header('location:' . $portal_conf['portalsite'] . '/portal.php');
        exit();
    }

    if (count($matches) == 0) {
        draw_editPersonModal();
        // ask to create new account
?>
        <h3>The email <?php echo $email;?> does not have an account.</h3>
        <div class='row'>
            <div class='col-sm-12'>
<?php
        if ($validationType == 'token') {
?>
                <p>You may have used a different email address for your account.
                    If this is the case, please use the 'Login with Authentication Link via Email' button below to try a different email address.
                </p>
<?php
        } else if ($validationType == 'google') {
?>
                <p>You may have used a different email address for your account.
                    If this is the case, please use the 'Login with Authentication Link via Email' button below to try a different email address. Once logged
                    in with that address use the 'Account Settings' menu item to add this address as an alternate identity.
                </p>
<?php
        } else {
 ?>
                <p>Either try a different "Login with" button than <?php echo $validationType;?> below.</p>
<?php
        }
?>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-sm-12">
                <p>Or, you can create a new account for this email address with</p>
                <button class="btn btn-sm btn-primary" onclick='login.createAccount("<?php echo $email;?>","<?php echo $validationType;?>")'>Create New Account for <?php echo $email;?></button>
            </div>
        </div>
        <hr/>
<?php
    // not logged in, draw signup stuff
        //draw_registrationModal($portalType, $portalName, $con, $countryOptions);
        draw_login($config_vars);
        exit();
    }

    if (count($matches) > 1) {
        $condata = get_con();
        $ini = get_conf('reg');
        index_page_init($condata['label'] . ' Membership Portal');
    ?>
        <body id="membershipPortalBody">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12 p-0">
                    <?php
                        if (array_key_exists('logoimage', $ini) && $ini['logoimage'] != '') {
                            if (array_key_exists('logoalt', $ini)) {
                                $altstring = $ini['logoalt'];
                            }
                            else {
                                $altstring = 'Logo';
                            } ?>
                            <img class="img-fluid" src="images/<?php echo $ini['logoimage']; ?>" alt="<?php echo $altstring; ?>"/>
                            <?php
                        }
                        if (array_key_exists('logotext', $ini) && $ini['logotext'] != '') {
                            echo $ini['logotext'];
                        }
                    ?>
                </div>
            </div>
        </div>
        <h4>This email address has access to multiple membership accounts</h4>
        Please select one of the accounts below:<br/><ul>
<?php

        foreach ($matches as $match) {
            $match['ts'] = time();
            $match['lid'] = $linkid;
            $match['validationType'] = $validationType;
            $string = json_encode($match);
            $string = urlencode(openssl_encrypt($string, $cipherInfo['cipher'], $cipherInfo['key'], 0, $cipherInfo['iv']));
            echo "<li><a href='?vid=$string'>" .  $match['fullname'] . "</a></li>\n";
        }
        ?>
        </ul>
        <button class='btn btn-sm btn-secondary m-1' onclick="window.location='?logout';">Logout</button>
        <script type='text/javascript'>
            var config = <?php echo json_encode($config_vars); ?>;
        </script>
        <div class='row mt-2'>
            <?php drawBug(12); ?>
        </div>
    </div>
</body>
        <?php
        exit();
    }

    // if we get here, something is drasticlly wrong
    draw_login($config_vars);
    exit();
}
