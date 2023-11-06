<?php

// This is now a common db_functions for all of the reg sections including:
//      onlinereg
//      reg_control
//  (others still need checking and adding as required)
//  goal is for it to be common, and used by all of the reg system, so database API changes are in a common location.

global $dbObject;
global $db_ini;
global $logdest;
global $debug_set;

$dbObject = null;
if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . "/../config/reg_conf.ini", true);
}
$debug_set = get_conf('debug');
$log = get_conf("log");
$logdest = $log['web'];

// Function web_error_log($string)
// $string = string to write to file $logdest with added newline at end
function web_error_log($string): void
{
    global $logdest;
    global $debug_set;

    if (($debug == '') or (array_key_exists($debug, $debug_set) and ($debug_set[$debug] == 1))) {
        error_log(date("Y-m-d H:i:s") . ": " . $string . "\n", 3, $logdest);
        error_log(date("Y-m-d H:i:s") . ": " . $string . "\n");
    }
}
// Function var_error_log()
// $object = object to be dumped to the PHP error log
// the object is walked and written to the PHP error log using var_dump and a redirect of the output buffer.
function var_error_log($object = null): void
{
    global $logdest;
    ob_start();                    // start buffer capture
    var_dump($object);           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log($contents . "\n", 3, $logdest);        // log contents of the result of var_dump( $object )
}

// Common function to log a mysql error
function log_mysqli_error($query, $additional_error_message):void
{
    global $dbObject;
    $result = "";
    error_log("mysql query error in {$_SERVER["SCRIPT_FILENAME"]}");
    if (!empty($query)) {
        web_error_log($query);
    }
    $errno = $dbObject->errno;
    if (!empty($errno)) {
        $query_error = "Error (" . $dbObject->errno . ") " . $dbObject->error .  ")";
        error_log($query_error);
        $result = $query_error . "<br>\n";
    }
    if (!empty($additional_error_message)) {
        error_log($additional_error_message);
        $result .= $additional_error_message . "<br>\n";
    }
    echo $result;
}

function db_connect():bool
{
    global $dbObject;
    global $db_ini;

    $port = 3306;
    if (array_key_exists("port", $db_ini['mysql'])) {
        $port = $db_ini['mysql']['port'];
    }

    if (is_null($dbObject)) {
        $dbObject = new mysqli(
            $db_ini['mysql']['host'],
            $db_ini['mysql']['user'],
            $db_ini['mysql']['password'],
            $db_ini['mysql']['db_name'],
            $port
        );

        if ($dbObject->connect_errno) {
            echo "Failed to connect to MySQL: (" . $dbObject->connect_errno . ") " . $dbObject->connect_error;
            web_error_log("Failed to connect to MySQL: (" . $dbObject->connect_errno . ") " . $dbObject->connect_error);
        }

        // for mysql with nonstandard sql_mode (from zambia point of view) temporarily force ours
        $sql = "SET sql_mode='" .  $db_ini['mysql']['sql_mode'] . "';";
        $success = $dbObject -> query($sql);
        if (!$success) {
            web_error_log("failed setting sql mode on db connection");
            return false;
        }

        if (array_key_exists('php_timezone', $db_ini['mysql'])) {
            date_default_timezone_set($db_ini['mysql']['php_timezone']);
        }
        if (array_key_exists('db_timezone', $db_ini['mysql'])) {
            $sql = "SET time_zone ='" .  $db_ini['mysql']['db_timezone'] . "';";
            $success = $dbObject -> query($sql);
            if (!$success) {
                web_error_log("failed setting sql mode on db connection");
                return false;
            }
        }
    }
    return true;
}

// dbSafeQuery - using prepare safely perform a db operation
// This should replace all database calls to dbQuery that use variable data in their query string
//
function dbSafeQuery($query, $typestr, $value_arr)
{
    global $dbObject;
    $res = null;
    $stmt = null;
    if (!is_null($dbObject)) {
        try {
            // prepare the query and check its syntax (parse)
            $stmt = $dbObject->prepare($query);
            if ($stmt === false || $dbObject->errno) {
                log_mysqli_error($query, "Prepare Error");
                return false;
            }

            // apply the parameters by type and value to the query, note strlen(typestr) must equal array size of value_arr
            $stmt->bind_param($typestr, ...$value_arr);
            if ($dbObject->errno) {
                $typelen = strlen($typestr);
                $paramlen = sizeof($value_arr);

                log_mysqli_error($query, "Bind Error: Types length = $typelen, Num Parameters = $paramlen");
                return false;
            }

            // execute the statement
            if (!$stmt->execute()) {
                log_mysqli_error($query, "Execute Error");
                return false;
            }

            // get the results
            $res = $stmt->get_result();
            if (!$res) {
                log_mysqli_error($query, "Result Error");
                return false;
            }
        } catch (\mysqli_sql_exception $e) {
            log_mysqli_error($query, $e->getMessage());
            return false;
        } catch (Exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        }
        return $res;
    } else {
        echo "ERROR: DB Connection Not Open";
        web_error_log("ERROR: DB Connection Not Open");
        return false;
    }
}

// dbSafeInsert - using prepare safely perform an insert operation
// returns the id of the created row
// This should replace all database calls to dbInsert that use variable data in their SQL string
//
function dbSafeInsert($sql, $typestr, $value_arr)
{
    global $dbObject;
    $stmt = null;
    $id = null;
    if (!is_null($dbObject)) {
        try {
            // prepare the sql statement and check its syntax (parse)
            $stmt = $dbObject->prepare($sql);
            if ($stmt === false || $dbObject->errno) {
                log_mysqli_error($sql, "Prepare Error");
                return false;
            }

            // apply the parameters by type and value to the query, note strlen(typestr) must equal array size of value_arr
            $stmt->bind_param($typestr, ...$value_arr);
            if ($dbObject->errno) {
                $typelen = strlen($typestr);
                $paramlen = sizeof($value_arr);

                log_mysqli_error($sql, "Bind Error: Types length = $typelen, Num Parameters = $paramlen");
                return false;
            }

            // execute the statement
            if (!$stmt->execute()) {
                log_mysqli_error($sql, "Execute Error");
                return false;
            }

            // get the inserted id
            $id = $dbObject->insert_id;
        } catch (\mysqli_sql_exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        } catch (Exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        }
        return $id;
    } else {
        echo "ERROR: DB Connection Not Open";
        web_error_log("ERROR: DB Connection Not Open");
        return false;
    }
}

// dbSafeCmd - using prepare safely perform an update/delete/multi-line insert operation
// returns the number of rows modified/deleted (actually changed a value)
// This should replace all database calls to db functions that use variable data in their SQL string
//
function dbSafeCmd($sql, $typestr, $value_arr)
{
    global $dbObject;
    $stmt = null;
    $numrows = null;
    if (!is_null($dbObject)) {
        try {
            // prepare the sql statement and check its syntax (parse)
            $stmt = $dbObject->prepare($sql);
            if ($stmt === false || $dbObject->errno) {
                log_mysqli_error($sql, "Prepare Error");
                return false;
            }

            // apply the parameters by type and value to the query, note strlen(typestr) must equal array size of value_arr
            $stmt->bind_param($typestr, ...$value_arr);
            if ($dbObject->errno) {
                $typelen = strlen($typestr);
                $paramlen = sizeof($value_arr);

                log_mysqli_error($sql, "Bind Error: Types length = $typelen, Num Parameters = $paramlen");
                return false;
            }

            // execute the statement
            if (!$stmt->execute()) {
                log_mysqli_error($sql, "Execute Error");
                return false;
            }

            // get the number of rows affected
            $numrows = $dbObject->affected_rows;
        } catch (\mysqli_sql_exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        } catch (Exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        }
        return $numrows;
    } else {
        echo "ERROR: DB Connection Not Open";
        web_error_log("ERROR: DB Connection Not Open");
        return false;
    }
}
// dbCmd - for sql commands without any ? in the command
// returns the number of rows modified/deleted (actually changed a value)
// NOTE: All queries built dynamically should use ? notation and use dbSafeCmd instead
//
function dbCmd($sql)
{
    global $dbObject;
    $numrows = null;
    if (!is_null($dbObject)) {
        try {
            // execute the command
            $res = $dbObject->query($sql);
            if ($res === false || $dbObject->errno) {
                log_mysqli_error($sql, "Command Execute Error");
                return false;
            }
            // get the number of rows affected
            $numrows = $dbObject->affected_rows;
        } catch (\mysqli_sql_exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        } catch (Exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        }
        return $numrows;
    } else {
        echo "ERROR: DB Connection Not Open";
        web_error_log("ERROR: DB Connection Not Open");
        return false;
    }
}

// dbQuery - sql SELECT with no ? parameters
// NOTE: All queries built dynamically should use ? notation and use dbSafeQuery instead
//
function dbQuery($query)
{
    global $dbObject;
    $res = null;
    if (!is_null($dbObject)) {
        try {
            // execute the command
            $res = $dbObject->query($query);
            if ($res === false || $dbObject->errno) {
                log_mysqli_error($query, "Query Error");
                return false;
            }
        } catch (\mysqli_sql_exception $e) {
            log_mysqli_error($query, $e->getMessage());
            return false;
        } catch (Exception $e) {
            log_mysqli_error($query, $e->getMessage());
            return false;
        }
        return $res;
    } else {
        echo "ERROR: DB Connection Not Open";
        web_error_log("ERROR: DB Connection Not Open");
        return false;
    }
}

// dbInsert - insert a row into the database and return the new key field
// NOTE: All inserts built dynamically should use ? notation and use dbSafeInsert instead
//
function dbInsert($query)#: int|bool
{
    global $dbObject;
    if (!is_null($dbObject)) {
        try {
            $res = $dbObject->query($query);
            if ($res === false) {
                log_mysqli_error($query, 'Insert Error');
                return false;
            }
            $id = $dbObject->insert_id;
            if ($dbObject->errno) {
                log_mysqli_error($query, "Insert Error");
                return false;
            }
        } catch (\mysqli_sql_exception $e) {
            log_mysqli_error($query, $e->getMessage());
            return false;
        } catch (Exception $e) {
            log_mysqli_error($query, $e->getMessage());
            return false;
        }
        return $id;
    } else {
        echo "ERROR: DB Connection Not Open";
        web_error_log("ERROR: DB Connection Not Open");
    }
    return false;
}



function dbPrepare($query)
{
    global $dbObject;
    if (!is_null($dbObject)) {
        $res = $dbObject->prepare($query);
        if (!$res) {
            echo "Prepare Failed: (" . $dbObject->errno . ") " . $dbObject->error;
            web_error_log("Prepare Failed: (" . $dbObject->errno . ") " . $dbObject->error);
            return false;
        } else {
            return $res;
        }
    } else {
        echo "ERROR: DB Connection Not Open";
        web_error_log("ERROR: DB Connection Not Open");
        return false;
    }
}

// escape_quotes - change " to \" for use in HTML parameters
// For use at location of actual data use
//
function escape_quotes($param) {
    return str_replace('"', '\"', $param);
}

// escape_appos - change ' to \' for use in HTML parameters
// For use at location of actual data use
//
function escape_appos($param) {
    return str_replace("'", "\'", $param);
}

// Should NOT Be used going forward - Obsolete, use ? notation and the 'dbSafe' variants instead
// also any encoding of data should be where it is needed to be used and not global to all queries
//
function sql_safe($string)
{
    global $dbObject;
    return $dbObject->escape_string($string);
}

// obsolete function register, lets delete it and see if we still need it
/*
function register($email, $sub, $name)
{
    global $dbObject;

    if (is_null($dbObject)) {
        return false;
    }
    $email = $dbObject->escape_string($email);
    $sub = $dbObject->escape_string($sub);
    $name = $dbObject->escape_string($name);
    $query = "INSERT INTO user (email, google_sub, name, new) values ('$email', '$sub', '$name', 'Y');";
    $res = $dbObject->query($query);
    $id = $dbObject->insert_id;
    if ($res && $id > 0) {
        return $id;
    }
    if ($dbObject->errno) {
        echo "<p>Query Error (" . $dbObject->errno . ") " . $dbObject->error . "</p>";
        echo "<p>$query</p>";
        return false;
    }
    return $res;
}
*/

function getPages($sub)#: array|bool
{
    $res = [];
    $sql = <<<EOS
SELECT DISTINCT A.id, A.name, A.display
FROM user U
JOIN user_auth UA ON (U.id = UA.user_id)
JOIN auth A ON (A.id = UA.auth_id)
WHERE U.google_sub = ? AND A.page='Y'
ORDER BY A.id;
EOS;
    $auths = dbSafeQuery($sql, 's', [$sub]);
    if (!$auths) {
        return false;
    }
    while ($new_auth = $auths->fetch_assoc()) {
        $res[] = $new_auth;
    }
    return $res;
}


function getAuthsById($id)#: array|bool
{
    $res = [];
    $sql = <<<EOS
SELECT A.name
FROM user U
JOIN user_auth UA ON (U.id = UA.user_id)
JOIN auth A ON (A.id = UA.auth_id)
WHERE U.id = ?
ORDER BY A.id;
EOS;
    $auths = dbSafeQuery($sql, 's', [$id]);
    if (!$auths) {
        return false;
    }
    while ($new_auth = $auths->fetch_assoc()) {
        $res[] = $new_auth['name'];
    }
    return $res;
}

function getAuths($sub)#: array|bool
{
    $res = [];
    $sql = <<<EOS
SELECT A.name
FROM user U
JOIN user_auth UA ON (U.id = UA.user_id)
JOIN auth A ON (A.id = UA.auth_id)
WHERE U.google_sub = ?
ORDER BY A.id;
EOS;
    $auths = dbSafeQuery($sql, 's', [$sub]);
    if (!$auths) {
        return false;
    }
    while ($new_auth = $auths->fetch_assoc()) {
        $res[] = $new_auth['name'];
    }
    return $res;
}

function checkAuth($sub, $name)#: array|bool
{
    if (!isset($sub) || !$sub) {
        return false;
    }
    $res = [];
    $sql = <<<EOS
SELECT A.name
FROM user U
JOIN user_auth UA ON (U.id = UA.user_id)
JOIN auth A ON (A.id = UA.auth_id)
WHERE U.google_sub = ? AND A.name=?
ORDER BY A.id;
EOS;
    $auths = dbSafeQuery($sql, 'ss', [$sub, $name]);
    if (!$auths) {
        return false;
    }
    while ($new_auth = $auths->fetch_assoc()) {
        $res[] = $new_auth['name'];
    }
    return $res;
}

function checkUser($sub)#: bool
{
    if (!isset($sub) || !$sub) {
        return false;
    }
    $res = dbSafeQuery("SELECT email FROM user WHERE google_sub=?;", 's', [$sub]);
    if (!$res || $res->num_rows <= 0) {
        return false;
    } else {
        return true;
    }
}

function getUsers($new = null)#:array|bool
{
    $res = [];
    $query = "SELECT id, name, email FROM user";
    if ($new === true) {
        $query .= " WHERE new='Y'";
    }
    if ($new === false) {
        $query .= " WHERE new='N'";
    }
    $users = dbQuery($query . ';');
    if (!$users) {
        return false;
    }
    while ($next_user = $users->fetch_assoc()) {
        $res[] = $next_user;
    }
    return $res;
}

function db_close(): void
{
    global $dbObject;
    if (!is_null($dbObject)) {
        $dbObject->close();
        $dbObject = null;
    }
}

// older style convert quotes association, to be phased out
// should be phased out and just use res->fetch_assoc() and proper escaping where data is used when needed
function fetch_safe_assoc($res)
{
    if (is_null($res)) {
        return null;
    }
    if ($res === false) {
        return null;
    }
    $assoc = $res->fetch_assoc();
    if (is_null($assoc)) {
        return null;
    }
    foreach ($assoc as $key => $value) {
        if (!is_null($value)) {
            $assoc[$key] = htmlentities($value, ENT_QUOTES);
        }
    }
    return $assoc;
}

// obsolete method of escaping values, should be phased out and just use res->fetch_array() and proper escaping where data is used when needed
function fetch_safe_array($res)
{
    if (is_null($res)) {
        return null;
    }
    if ($res === false) {
        return null;
    }
    $assoc = $res->fetch_row();
    if (is_null($assoc)) {
        return null;
    }
    foreach ($assoc as $key => $value) {
        if (!is_null($value)) {
            $assoc[$key] = htmlentities($value, ENT_QUOTES);
        }
    }
    return $assoc;
}

function get_conf($name)
{
    global $db_ini;
    return $db_ini[$name];
}

function get_con()
{
    global $db_ini;
    $r = dbSafeQuery("SELECT * FROM conlist WHERE id=?;", 'i', [$db_ini['con']['id']]);
    return $r->fetch_assoc();
}

function get_user($sub)
{
    $r = dbSafeQuery("SELECT * FROM user WHERE google_sub=?;", 's', [$sub]);
    $res = $r->fetch_assoc();
    return $res['id'];
}

/* if I want to handle refresh tokens in the database I'll need something like this
function unset_refresh($id) {
$query = "UPDATE user SET refresh_token = NULL WHERE id= $id;";
dbQuery($query);
}

function set_refresh($id) {
if(isset($_SESSION['refresh_token'])) {
$token = sql_safe($_SESSION['refresh_token']);
$query = "UPDATE user SET refresh_token = '$token' WHERE id= $id;";
dbQuery($query);
}
}

function get_refresh($id) {
$query = "SELECT refresh_token FROM user WHERE id = $id;";
dbQuery($query);
}
 */

function newUser($email, $sub):bool
{
    if (!isset($sub) || !isset($email) || !$sub || !$email) {
        return false;
    }
    $userR = dbSafeQuery("SELECT id,google_sub,email FROM user WHERE email=?;", 's', [$email]);
    if (!$userR || $userR->num_rows != 1) {
        return false;
    }
    $user = $userR->fetch_assoc();
    if ($user['google_sub'] == '') {
        $id = $user['id'];
        dbQuery("UPDATE user SET google_sub='$sub' WHERE id='$id';");
    }
    return true;
}

//  for use in url parameters for get's to make things clean
function base64_encode_url($string) {
    return str_replace(['+','/','='], ['-','_',''], base64_encode($string));
}

function base64_decode_url($string) {
    return base64_decode(str_replace(['-','_'], ['+','/'], $string));
}
