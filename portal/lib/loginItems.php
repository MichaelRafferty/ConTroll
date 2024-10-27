<?php
// draw_login - draw the login options form
function draw_login($config_vars, $result_message = '', $result_color = '', $why = 'continue to the portal') {
    $con = get_conf('con');
    $policies = getPolicies();
    ?>
 <!-- signin form (at body level) -->
    <div id='signin'>
        <div class='container-fluid form-floating'>
            <div class='row mb-2'>
                <div class='col-sm-auto'>
                    <h4>Please log in to <?php echo $why; ?>.</h4>
                </div>
            </div>
            <div class="row mb-2">
                <div class='col-sm-auto'>
                    <button class="btn btn-sm btn-primary" onclick="login.loginWithToken();">
                        Create Account or Login with Email with Authentication
                    </button>
                </div>
            </div>
            <div id='token_email_div' hidden>
                <div class='row mt-1'>
                    <div class='col-sm-1'>
                        <label for='token_email'>*Email: </label>
                    </div>
                    <div class='col-sm-auto'>
                        <input class='form-control-sm' type='email' name='token_email' id='token_email' size='40' onchange='login.tokenEmailChanged(0);'
                               required/>
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
                    <button class='btn btn-sm btn-primary' onclick='login.loginWithGoogle();'>Create Account or Login with Google</button>
                </div>
            </div>
            <?php
            // bypass for testing on Development PC
                // TODO: back out seattle regtest from here.
    if ((stripos(__DIR__, '/Users/syd/') !== false && $_SERVER['SERVER_ADDR'] == '127.0.0.1')  ||
        (stripos(__DIR__, '/home/seattle/regtest.seattlein2025.org/ConTroll') !== false && $_SERVER['SERVER_ADDR'] == '192.168.88.4')) {
                ?>
            <div class="row mt-3><div class="col-sm-12"><hr></div></div>
            <div class='row mt-2'>
                <div class='col-sm-auto'>
                    <label for='dev_email'>*Direct to Email/Perid/Newperid: </label>
                </div>
                <div class='col-sm-auto'>
                    <input class='form-control-sm' type='email' name='dev_email' id='dev_email' size='40' required/>
                </div>
                <div class='col-sm-auto'>
                    <button type="button" class='btn btn-sm btn-primary' onclick='login.loginWithEmail();'>Direct Login</button>
                </div>
            </div>
            <div class='row mb-2'><div class="col-sm-12" id="matchList"></div></div>
            <?php
    } ?>
        </div>
    </div>
<?php
    outputCustomText('main/bottom');
?>
    <div class='container-fluid'>
        <div class="row">
            <div class="col-sm-11 m-4">
                <p>
                    Accessibility note: This system is not fully accessible for assistive technology; disabled users may need assistance to complete registration.
                    Known issues vary by browser and specific assistive technology but include only partial keyboard access to all interface elements.
                    We are working on improving accessibility with future updates.
                    We apologize for the inconvenience and appreciate your understanding.
                </p>
            </div>
        </div>
        <div class='row mt-4'>
            <div class='col-sm-11'>
                For any difficulties with the registration system please contact registration at
                <a href="mailto:<?php echo $con['regadminemail']; ?>?subject=Portal%20Difficulties">
                    <?php echo $con['regadminemail']; ?>
                </a>
            </div>
        </div>
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
        var policies = <?php echo json_encode($policies); ?>;
    </script>
</html>
<?php
}

// chooseAccountFromEmail - map an email address to a list of accounts
// email is a validated email by the validationType.
function chooseAccountFromEmail($email, $id, $linkid, $passedMatch, $validationType) {
    global $config_vars;

    $portal_conf = get_conf('portal');
    $con_conf = get_conf('con');
    $origEmail = strtolower($email);

    $loginData = getLoginMatch($email, $id, $validationType);
    if (!is_array($loginData)) {
        return $loginData;  // return the error message from getLoginMatch
    }
    $matches = $loginData['matches'];
    $count = count($matches);
    if ($count == 1) {
        $match = $matches[0];
        if (array_key_exists('banned', $match)) {
            if ($match['banned'] != 'N') {
                return('There is an issue with your account, please contact registration at ' .
                    $con_conf['regadminemail'] . ' for assistance.');
                }
            }
        if (array_key_exists('issue', $match)) {
            if ($match['issue'] != 'N') {
                return('There is an issue with your account, please contact registration at ' .
                    $con_conf['regadminemail'] . ' for assistance.');
            }
        }
        if (array_key_exists('email', $match)) {
            $email = strtolower($match['email']);
        }
        if (array_key_exists('email_addr', $match)) {
            $email = strtolower($match['email_addr']);
        }
        $id = $match['id'];
        $idType = $match['tablename'];
        $ts = ' ';
        if (array_key_exists('ts', $match)) {
            $ts = " with ts ". $match['ts'];
        }
        if (isSessionVar('id')) {
            // we had a prior session
            if (isSessionVar('oauth')) {
                $type = 'validation';
            } else if (getSessionVar('id') != $id) {
                // not same id, treat it as a new login
                unsetSessionVar('transId');    // just in case it is hanging around, clear this
                unsetSessionVar('totalDue');   // just in case it is hanging around, clear this
                $type = 'id change login';
            } else {
                $type = 'refresh';
            }
        } else {
            if (isSessionVar('oauth')) {
                $type = 'validation';
            } else {
                $type = 'new login';
            }
        }
        $multiple = null;
        if ($passedMatch != null) {
            if (array_key_exists('multiple', $passedMatch)) {
                $multiple = $passedMatch['multiple'];
            }
        }
        if (array_key_exists('multiple', $match)) {
            $multiple = $match['multiple'];
        }

        if ($idType == 'p')
            updateIdentityUsage($id, $validationType, $origEmail);
        web_error_log("$type @ " . time() . "$ts for $email/$id via $validationType");
        validationComplete($id, $idType, $email, $validationType, $multiple);
        exit();
    }

    if (count($matches) == 0) {
        $policies = getPolicies();
        draw_editPersonModal('login', $policies);
        // ask to create new account
?>
        <h3>The email <?php echo $email;?> does not have an account.</h3>
        <div class='row'>
            <div class='col-sm-12'>
                <p>If you believe you already have a membership or account it may been created using a different email address. If this is the case, please
                    login again using the correct email address. If you cannot remember what email you used, please contact
                    <?php echo $con_conf['regadminemail']?>.</p>
                <p>Once you have logged in using the correct email address, you can use the 'Account
                    Settings’ menu item to add email addresses to your account.</p>
            </div>
        </div>
<?php
        if (isSessionVar('oauth')) {
            $oauth = getSessionVar('oauth');
            $app = $oauth['app'];
?>
        <div class='row'>
            <div class='col-sm-12'>
                <p>You received an authentication request from <?php echo $app; ?>.  If you feel you have an account, please try logging in again with a
                    different email address to continue the authentication.</p>
            </div>
        </div>
<?php
        } else {

?>
        <div class="row mb-4">
            <div class="col-sm-12">
                <button class="btn btn-sm btn-primary" onclick='login.createAccount("<?php echo $email;?>","<?php echo $validationType;?>")'>Create New Account for <?php echo $email;?></button>
            </div>
        </div>
<?php
        }
?>
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
?>
        <h4>This email address has access to multiple membership accounts</h4>
<?php
        outputCustomText('main/multiple');
?>
        Please select one of the accounts below:<br/><ul>
<?php
        foreach ($matches as $match) {
            $match['ts'] = time();
            $match['lid'] = $linkid;
            $match['validationType'] = $validationType;
            $match['multiple'] = strtolower($email);
            $match['issue'] = $match['banned'];
            $string = json_encode($match);
            $string = encryptCipher($string, true);
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

// we now have a valid authentication and an email address, handle the appropriate response
//  possible responses:
//      direct login: redirect to portal
//      oauth authentication request: redirect back to oauth with the appropriate values
function validationComplete($id, $idType, $email, $validationType, $multiple) {
    // if not oauth session variable to go portal
    $portal_conf = get_conf('portal');
    if (!isSessionVar('oauth')) {
        if ($id != getSessionVar('id')) {
            unsetSessionVar('transId');    // just in case it is hanging around, clear this
            unsetSessionVar('totalDue');   // just in case it is hanging around, clear this
            setSessionVar('id', $id);
            setSessionVar('idType', $idType);
            setSessionVar('idSource', $validationType);
            setSessionVar('email', $email);
            if ($multiple != null) {
                setSessionVar('multiple', $multiple);
            }
        }
        header('location:' . $portal_conf['portalsite'] . '/portal.php');
        exit();
    }

    // oauth session variable found, delete that variable and go to the server to respond back to the app
    // get the information for this response
    $reg_conf = get_conf('reg');
    $con_conf = get_conf('con');
    $conid = $con_conf['id'];
    $nomDate = $portal_conf['nomdate'];
    $oauth = getSessionVar('oauth');
    unsetSessionVar('oauth'); // prevent endless loops

    if ($idType == 'p') {
        $rSQL = <<<EOS
SELECT p.id AS perid, n.id AS newperid, p.email_addr AS email, m.label, m.memCategory, t.complete_date, t.complete_date < ? AS inTime,
       TRIM(REGEXP_REPLACE(CONCAT(IFNULL(p.first_name, ''),' ', IFNULL(p.middle_name, ''), ' ', IFNULL(p.last_name, ''), ' ',  
        IFNULL(p.suffix, '')), '  *', ' ')) AS fullName, p.first_name, p.last_name
FROM perinfo p
LEFT OUTER JOIN newperson n ON n.perid = p.id
LEFT OUTER JOIN reg r ON r.perid = p.id AND r.conid = ? AND r.status = 'paid'
LEFT OUTER JOIN transaction t ON r.complete_trans = t.id
LEFT OUTER JOIN memList m ON r.memId = m.id
WHERE p.id = ?;
EOS;
    } else {
        $rSQL = <<<EOS
SELECT NULL AS perid, n.id AS newperid, n.email_addr AS email, m.label, m.memCategory, t.complete_date, t.complete_date < ? AS inTime,
       TRIM(REGEXP_REPLACE(CONCAT(IFNULL(n.first_name, ''),' ', IFNULL(n.middle_name, ''), ' ', IFNULL(n.last_name, ''), ' ',  
        IFNULL(n.suffix, '')), '  *', ' ')) AS fullName, n.first_name, n.last_name
FROM newperson n
LEFT OUTER JOIN reg r ON r.newperid = n.id AND r.conid = ? AND r.status = 'paid'
LEFT OUTER JOIN transaction t ON r.complete_trans = t.id
LEFT OUTER JOIN memList m ON r.memId = m.id
WHERE n.id = ?;
EOS;
    }

    $rR = dbSafeQuery($rSQL, 'sii', array($nomDate, $conid, $id));
    if ($rR === false || $rR->num_rows == 0) {
        header('location:portal.php?type=e&messageFwd=' .
               urlencode('There is an issue getting the authorization information for this account. Please contact registration at ' .
                         $con_conf['regadminemail'] . ' for assistance.'));
        exit();
    }
    $regs = [];
    while ($rL = $rR->fetch_assoc()) {
        $regs[] = $rL;
    }
    $rR->free();

    // ok we now have the authentication information, build the response array
    // build response string
    $resp = [];
    $resp['email'] = $regs[0]['email'];
    $resp['perid'] = $regs[0]['perid'];
    $resp['newperid'] = $regs[0]['newperid'];
    $resp['resType'] = $oauth['retdata'];
    $resp['legalName'] = null;
    $resp['first_name'] = $regs[0]['first_name'];
    $resp['last_name'] = $regs[0]['last_name'];
    $resp['fullName'] = $regs[0]['fullName'];
    $resp['rights'] = '';

    switch (strtolower($resp['resType'])) {
        case 'nom':
            for ($row = 0; $row < count($regs); $row++) {
                $reg = $regs[$row];
                if ($reg['memCategory'] == 'wsfs' && $reg['inTime'] == 1) {
                    $resp['rights'] = 'hugo_nominate';
                    break;
                }
            }
            break;
        case 'vote':
            for ($row = 0; $row < count($regs); $row++) {
                $reg = $regs[$row];
                if ($reg['memCategory'] == 'wsfs' && str_contains(str_to_lower($reg['label']), ' only') == false) {
                    $resp['rights'] = 'hugo_vote';
                    break;
                }
            }
            break;
    }

    // now we have the response data in $resp, json encode it.
    $respJson = json_encode($resp);
    // encrypt it for the return
    $respenc = encryptCipher($respJson, true);
    // and now do the redirect
    $loc = $oauth['returl'];
    header('location:' . $loc . '?oauth=' . $respenc);
    exit();
}