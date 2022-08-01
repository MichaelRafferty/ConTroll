<?php
// This is now a common db_functions for all of the reg sections including:
//      onlinereg
//      reg_control
//  (others still need checking and adding as required)
//  goal is for it to be common, and used by all of the reg system, so database API changes are in a common location.

global $dbObject;
global $db_ini;

$dbObject = null;
if (!$db_ini) {
    $db_ini = parse_ini_file(__DIR__ . "/../config/reg_conf.ini", true);
}


// Function var_error_log()
// $object = object to be dumped to the PHP error log
// the object is walked and written to the PHP error log using var_dump and a redirect of the output buffer.
function var_error_log( $object=null ){
    ob_start();                    // start buffer capture
    var_dump( $object );           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log( $contents );        // log contents of the result of var_dump( $object )
}

// Common function to log a mysql error
function log_mysqli_error($query, $additional_error_message) {
    global $dbObject;
    $result = "";
    error_log("mysql query error in {$_SERVER["SCRIPT_FILENAME"]}");
    if (!empty($query)) {
        error_log($query);
    }
    $errno = $dbObject->errno;
    if (!empty($errno)) {
        $query_error = "Error (". $dbObject->errno .") " . $dbObject->error.  ")";
        error_log($query_error);
        $result = $query_error . "<br>\n";
    }
    if (!empty($additional_error_message)) {
        error_log($additional_error_message);
        $result .= $additional_error_message . "<br>\n";
    }
    echo $result;
}

function db_connect() {
    global $dbObject;
    global $db_ini;
    if(is_null($dbObject)) {
        $dbObject = new mysqli(
            $db_ini['mysql']['host'],
            $db_ini['mysql']['user'],
            $db_ini['mysql']['password'],
            $db_ini['mysql']['db_name']);

        if($dbObject->connect_errno) {
            echo "Failed to connect to MySQL: (" . $dbObject->connect_errno .") " . $dbObject->connect_error;
            error_log("Failed to connect to MySQL: (" . $dbObject->connect_errno .") " . $dbObject->connect_error);
        }
    }
}

// dbSafeQuery - using prepare safely perform a db operation
// This should replace all database calls to dbQuery that use variable data in their query string
//
function dbSafeQuery($query, $typestr, $value_arr) {
    global $dbObject;
    $res=null;
    $stmt=null;
    if(!is_null($dbObject)) {
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
        }
        catch (Exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        }
        return $res;
    } else {
        echo "ERROR: DB Connection Not Open";
        error_log("ERROR: DB Connection Not Open");
        return false;
    }
}

// dbSafeInsert - using prepare safely perform an insert operation
// returns the id of the created row
// This should replace all database calls to dbInsert that use variable data in their SQL string
//
function dbSafeInsert($sql, $typestr, $value_arr) {
    global $dbObject;
    $stmt=null;
    $id=null;
    if(!is_null($dbObject)) {
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
        }
        catch (Exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        }
        return $id;
    } else {
        echo "ERROR: DB Connection Not Open";
        error_log("ERROR: DB Connection Not Open");
        return false;
    }
}

// dbSafeCmd - using prepare safely perform an update/delete/multi-line insert operation
// returns the number of rows modified/deleted (actually changed a value)
// This should replace all database calls to db functions that use variable data in their SQL string
//
function dbSafeCmd($sql, $typestr, $value_arr) {
    global $dbObject;
    $stmt=null;
    $numrows=null;
    if(!is_null($dbObject)) {
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
        }
        catch (Exception $e) {
            log_mysqli_error("", $e->getMessage());
            return false;
        }
        return $numrows;
    } else {
        echo "ERROR: DB Connection Not Open";
        error_log("ERROR: DB Connection Not Open");
        return false;
    }
}
function dbQuery($query) {
    global $dbObject;
    $res=null;
    if(!is_null($dbObject)) {
        $res = $dbObject->query($query);
        if($dbObject->errno) {
            log_mysqli_error($query, "Query Error");
            return false;
        }
        return $res;
    } else {
        echo "ERROR: DB Connection Not Open";
        error_log("ERROR: DB Connection Not Open");
        return false;
    }
}

function dbInsert($query) {
    global $dbObject;
    if(!is_null($dbObject)) {
        $res = $dbObject->query($query);
        $id = $dbObject->insert_id;
        if($dbObject->errno) {
            log_mysqli_error($query, "Insert Error");
            return false;
        }
        return $id;
    } else {
        echo "ERROR: DB Connection Not Open";
        error_log("ERROR: DB Connection Not Open");
        return false;
    }
}



function dbPrepare($query) {
    global $dbObject;
    if(!is_null($dbObject)) {
        $res = $dbObject->prepare($query);
        if(!$res) {
            echo "Prepare Failed: (". $dbObject->errno . ") " . $dbObject->error;
            error_log("Prepare Failed: (". $dbObject->errno . ") " . $dbObject->error);
            return false;
        } else { return $res; }
    } else {
        echo "ERROR: DB Connection Not Open";
        error_log("ERROR: DB Connection Not Open");
        return false;
    }
}

function sql_safe($string) {
    global $dbObject;
    return $dbObject->escape_string($string);
}

function register($email, $sub, $name) {
    global $dbObject;
    $email = $dbObject->escape_string($email);
    $sub = $dbObject->escape_string($sub);
    $name = $dbObject->escape_string($name);
    if(is_null($dbObject)) {return false;}
    $query = "INSERT INTO user (email, google_sub, name, new) values ('$email', '$sub', '$name', 'Y');";
    $res = $dbObject->query($query);
    $id = $dbObject->insert_id;
    if($res && $id>0) { return $id; }
    if($dbObject->errno) {
        echo "<p>Query Error (". $dbObject->errno .") " . $dbObject->error ."</p>";
        echo "<p>$query</p>";
        return false;
    }
    return $res;
}

function getAuthsById($id) {
    $res = array();
    $auths = dbQuery("SELECT A.name FROM user AS U, auth AS A, user_auth as UA WHERE U.id = '$id' AND U.id = UA.user_id AND A.id = UA.auth_id ORDER BY A.id;");
    if(!$auths) { return false; }
    while($new_auth = fetch_safe_assoc($auths)) {
        $res[count($res)] = $new_auth['name'];
    }
    return $res;
}

function getAuths($sub) {
    $res = array();
    $auths = dbQuery("SELECT A.name FROM user AS U, auth AS A, user_auth as UA WHERE U.google_sub = '$sub' AND U.id = UA.user_id AND A.id = UA.auth_id ORDER BY A.id;");
    if(!$auths) { return false; }
    while($new_auth = fetch_safe_assoc($auths)) {
        $res[count($res)] = $new_auth['name'];
    }
    return $res;
}

function checkAuth($sub, $name) {
    if(!isset($sub) || !$sub) { return false; }
    $res = array();
    $auths = dbQuery("SELECT A.name FROM user AS U, auth AS A, user_auth as UA WHERE U.google_sub = '$sub' AND A.name='$name' AND U.id = UA.user_id AND A.id = UA.auth_id ORDER BY A.id;");
    if(!$auths) { return false; }
    while($new_auth = $auths->fetch_array(MYSQLI_ASSOC)) {
        $res[count($res)] = $new_auth['name'];
    }
    return $res;
}

function checkUser($sub) {
    if(!isset($sub) || !$sub) { return false; }
    $res = dbQuery("SELECT email FROM user WHERE google_sub='$sub';");
    if(!$res || $res->num_rows <= 0) { return false; }
    else { return true; }
}

function getUsers($new=null) {
    $res = array();
    $query = "SELECT id, name, email FROM user";
    if($new === true) { $query .= " WHERE new='Y'"; }
    if($new === false) { $query .= " WHERE new='N'"; }
    $users = dbQuery($query . ';');
    if(!$users) { return false; }
    while($next_user = fetch_safe_assoc($users)) {
        $res[count($res)] = $next_user;
    }
    return $res;
}

function db_close() {
    global $dbObject;
    if(!is_null($dbObject)) { $dbObject->close(); $dbObject=null; }
}

function fetch_safe_assoc($res) {
    if (is_null($res)) { return null; }
    if ($res === false) { return null; }
    $assoc = $res->fetch_assoc();
    if (is_null($assoc)) { return null; }
    foreach ($assoc as $key => $value) {
        if (!is_null($value)) {
            $assoc[$key] = htmlentities($value, ENT_QUOTES);
        }
    }
    return $assoc;
}

function fetch_safe_array($res) {
    if (is_null($res)) { return null; }
    if ($res === false) { return null; }
    $assoc = $res->fetch_row();
    if (is_null($assoc)) { return null; }
    foreach ($assoc as $key => $value) {
        if (!is_null($value)) {
            $assoc[$key] = htmlentities($value, ENT_QUOTES);
        }
    }
    return $assoc;
}

function get_conf($name) {
  global $db_ini;
  return $db_ini[$name];
}

function get_con() {
    global $db_ini;
    return fetch_safe_assoc(dbQuery("SELECT * FROM conlist WHERE id='".$db_ini['con']['id']."';"));
}

function get_user($sub) {
    $query = "SELECT * FROM user WHERE google_sub='". sql_safe($sub) . "';";
    $res = fetch_safe_assoc(dbQuery($query));
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

function getPages($sub) {
    $res = array();
    $auths = dbQuery("SELECT DISTINCT A.name, A.display FROM user AS U, auth AS A, user_auth as UA WHERE U.google_sub = '$sub' AND A.page='Y' AND U.id = UA.user_id AND A.id = UA.auth_id ORDER BY A.id;");
    if(!$auths) { return false; }
    while($new_auth = fetch_safe_assoc($auths)) {
        $res[count($res)] = $new_auth;
    }
    return $res;
}

function newUser($email, $sub){
    if(!isset($sub) || !isset($email) || !$sub || !$email) {
        return false;
    }
    $userR = dbQuery("SELECT id,google_sub,email FROM user WHERE email='$email';");
    if(!$userR || $userR->num_rows!=1) {
        return false;
    }
    $user = fetch_safe_assoc($userR);
    if($user['google_sub'] == '') {
        $id = $user['id'];
        dbQuery("UPDATE user SET google_sub='$sub' WHERE id='$id';");
    }
}

function check_atcon($user, $passwd, $level, $conid) {
    $u = sql_safe($user);
    $p = sql_safe($passwd);

    $q = "SELECT id FROM atcon_auth WHERE perid=$u and passwd='$p' and conid=$conid and auth='$level';";
    $r = dbQuery($q);
    if($r->num_rows > 0) { return true; }
    else { return false; }
}
?>