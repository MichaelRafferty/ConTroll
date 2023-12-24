<?php

// checks for the required perinfo records and creates the initial admin if needed
//  options: the array returned by getoptions.
function createMissingRecords($options) : int {
    global $db_ini;

    $conid = $db_ini['con']['id'];
    $errors = 0;
    logEcho('Creating missing records in the database');

    // check reg_control/atcon user
    $checkSQL = <<<EOS
SELECT count(*) occurs
FROM perinfo
WHERE id = ?;
EOS;

    $insertPQ = <<<EOS
INSERT INTO perinfo(id, first_name, last_name, email_addr, banned, active, open_notes, contact_ok, share_reg_ok)
VALUES (?,?,?,?,?,?,?,?,?);
EOS;

    $insertUQ = <<<EOS
INSERT INTO user(id, perid, email, google_sub, name, new)
VALUES (?,?,?,?,?,?);
EOS;

    $checkAdminQ = <<<EOS
SELECT count(*) occurs
FROM user_auth ua
JOIN auth a ON ua.auth_id = a.id
WHERE a.name = 'admin'
EOS;

    // check if user 2 exists in the system
    $checkR = dbSafeQuery($checkSQL, 'i', array(2));
    if ($checkR === false || $checkR->num_rows == 0) {
        logEcho('Error retrieving number of people in the database, cannot continue');
        return(1);
    }
    $num_rows = $checkR->fetch_row()[0];
    $checkR->free();
    if ($num_rows ==  1) {
        logEcho("Person 2/User 2 exist", true);
    } else {
        // insert user 2, person record first
        $newid = dbSafeInsert($insertPQ, 'issssssss', array(2, 'Atcon', 'Internal', NULL, 'N', 'N', 'INTERNAL NOT FOR REGISTRAITON USE', 'N', 'N'));
        if ($newid === false) {
            logEcho("Unable to insert Person Info 2 for ATCON");
            $errors++;
        } else {
            // user record for 2, note, no user_auth records for this user
            $newid = dbSafeInsert($insertUQ, 'iissss', array(2, 2, NULL, 'not a sub', 'atcon', 'N'));
            if ($newid === false) {
                logEcho('Unable to insert user 2 for ATCON');
                $errors++;
            }
        }
        logEcho("Created person and user 2 for ATCON");
    }

    // check if the initial conid exists in the system
    $checkCQ = <<<EOS
SELECT id
FROM conlist
WHERE id = ?;
EOS;
    $conidR = dbSafeQuery($checkCQ, 'i', array($conid));
    if ($conidR == false) {
        logEcho('check if $conid exists in conlist, cannot continue');
        return(1);
    }

    if ($conidR->num_rows == 0) {
        logecho("Creating initial conlist entry for $conid, it will need to be edited in reg_control/admin");
        $insertConlistQ = <<<EOS
INSERT INTO conlist(id, name, label, open, startdate, enddate)
VALUES(?, ?, ?, ?, ?, ?);
EOS;
        $params = array(
            $conid,
            $db_ini['con']['conname'] . $conid,
            $db_ini['con']['label'],
            'Y',
            '1900-01-01',
            '2099-12-31'
        );
        $newid = dbSafeInsert($insertConlistQ, 'isssss', $params);
        if ($newid == false) {
            logecho("Error inserting initial conlist entry for $conid, cannot continue");
            return(1);
        }
    } else {
        logEcho('conlist entry for $conid exists', true);
    }
    $conidR->free();

    // check if any admins exist in the system
    $checkR = dbQuery($checkAdminQ);
    if ($checkR === false || $checkR->num_rows == 0) {
        logEcho('Error retrieving number of admins in the database, cannot continue');
        return(1);
    }
    $num_admins = $checkR->fetch_row()[0];
    $checkR->free();

    if ($num_admins == 0) {
        // check if original admin record exists in the system
        $checkR = dbSafeQuery($checkSQL, 'i', array(1));
        $num_rows = $checkR->fetch_row()[0];
        $checkR->free();
        if ($num_rows == 0) {
            // insert user 1, Master User
            echo <<<EOS
Peron 1 does not exist in the database.  Person one is the first administrator in the system.
It will be created with Admin privileges in reg_control and manager privileges in atcon.

Please enter the email address for this administrator: 
EOS;

            $email_addr = trim(fgets(STDIN));
            while (!(filter_var($email_addr, FILTER_VALIDATE_EMAIL) && str_contains($email_addr, '.') && str_contains($email_addr, '@'))) {
                if ($email_addr == '/') {
                    $email_addr = '';
                    break;
                }
                echo PHP_EOL . "That is not a valid email address." . PHP_EOL . "Please enter a valid email address or '/' to skip this step: ";
                $email_addr = trim(fgets(STDIN));
            }

            echo <<<EOS

Please enter the first name for $email_addr: 
EOS;
            $first_name = trim(fgets(STDIN));
            while ($first_name == '') {
                echo PHP_EOL . "Enter / to skip the first name, otherwise the first name should not be empty" . PHP_EOL . "Please enter the first name for $email_addr: ";
                $first_name = trim(fgets(STDIN));
            }
            if ($first_name == '/') {
                $first_name = '';
            }

            echo <<<EOS

Please enter the last name for $email_addr: 
EOS;
            $last_name = trim(fgets(STDIN));
            while ($last_name == '') {
                echo PHP_EOL . 'Enter / to skip the last name, otherwise the last name should not be empty' . PHP_EOL . "Please enter the last name for $email_addr: ";
                $last_name = trim(fgets(STDIN));
            }
            if ($last_name == '/') {
                $last_name = '';
            }

            echo <<<EOS

Please enter the ATCON login name for $email_addr: 
EOS;
            $login_name = trim(fgets(STDIN));
            while ($login_name == '') {
                echo PHP_EOL . 'The ATCON login name is required.' . PHP_EOL . "Please enter the ATCON login name for $email_addr: ";
                $login_name = trim(fgets(STDIN));
            }

            echo <<<EOS

Please enter the ATCON password for $email_addr: 
EOS;
            $atcon_password = trim(fgets(STDIN));
            while (mb_strlen($atcon_password < 8)) {
                echo PHP_EOL . 'The ATCON password is required and must be a mimimum of 8 characters.' . PHP_EOL . "Please enter the ATCON password for $email_addr: ";
                $atcon_password = trim(fgets(STDIN));
            }

            // insert user 1 into perinfo
            $newid = dbSafeInsert($insertPQ, 'issssssss', array(1, $first_name, $last_name, $email_addr, 'N', 'Y', 'Initial Administrator', 'Y', 'Y'));
            if ($newid === false) {
                logEcho('Unable to insert Person Info 1 for administrator');
                $errors++;
            } else {
                logEcho("Created person 1 as $email_addr");
                // user record for 1
                $num_rows = dbSafeInsert($insertUQ, 'iissss', array(1, 1, $email_addr, NULL, trim($first_name . ' ' . $last_name), 'N'));
                if ($num_rows != 1) {
                    logEcho('Unable to insert user 1 for administrator');
                    $errors++;
                } else {
                    logEcho("Created user 1 as $email_addr");
                    // insert admin auth for user 1
                    $insertAuth = <<<EOS
INSERT INTO user_auth(auth_id, user_id)
SELECT id, 1
FROM auth
WHERE name = 'admin';
EOS;
                    $newid = dbInsert($insertAuth);
                    if ($newid === false) {
                        logEcho('Unable to insert admin rights for user 1');
                        $errors++;
                    } else {
                        logEcho("Created admin rights for user 1");
                    }

                    // create the atcon_user record
                    $new_enc_passwd = password_hash($atcon_password, PASSWORD_DEFAULT);
                    $insertAU = <<<EOS
INSERT INTO atcon_user(perid, conid, passwd)
VALUES(?, ?, ?);
EOS;
                    $newid = dbSafeInsert($insertAU, 'iis', array(1, $conid, $new_enc_passwd));
                    if ($newid === false) {
                        logEcho('Unable to insert ATCON user for user 1');
                        $errors++;
                    } else {
                        logEcho("Created ATCON user 1");
                        $insertAAuth = <<<EOS
INSERT INTO atcon_auth(authuser, auth)
VALUES (?, ?);
EOS;
                        $newid = dbSafeInsert($insertAAuth, 'is', array($newid, 'manager'));
                        if ($newid === false) {
                            logEcho('Unable to insert ATCON user rights for user 1');
                            $errors++;
                        } else {
                            logEcho('Created manager rights for ATCON user 1');
                        }
                    }
                }
            }
        }
    }

    if ($errors > 0)
        logEcho("Errors while adding initial users");
    return $errors;
}
?>
