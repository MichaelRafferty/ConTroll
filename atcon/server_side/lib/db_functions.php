<?php
global $dbObject;
global $db_ini;


$dbObject = null;
$db_ini = parse_ini_file(__DIR__ . "/../../../../config/reg_conf.ini", true);

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
            echo "Failed to connect to MySQL: (" . 
                $mysqli->connect_erno .") " . $mysqli->connect_error;
        }
    }
}

function dbQuery($query) {
    global $dbObject;
    $res=null;
    if(!is_null($dbObject)) {
        $res = $dbObject->query($query);
        if($dbObject->errno) {
            echo "Query Error (". $dbObject->errno .") " . $dbObject->error . "<br/>\n" . $query;
            return false;
        }
        return $res;
    } else {
        echo "ERROR: DB Connection Not Open";
        return false;
    } 
}

function dbInsert($query) {
    global $dbObject;
    if(!is_null($dbObject)) {
        $res = $dbObject->query($query);
        $id = $dbObject->insert_id;
        if($dbObject->errno) {
            echo "Query Error (". $dbObject->errno .") " . $dbObject->error . "<br/>\n". $query;
            return false;
        }
        return $id;
    } else {
        echo "ERROR: DB Connection Not Open";
        return false;
    } 
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

function getPages($sub) {
    $res = array();
    $auths = dbQuery("SELECT A.name FROM user AS U, auth AS A, user_auth as UA WHERE U.google_sub = '$sub' AND A.page='Y' AND U.id = UA.user_id AND A.id = UA.auth_id ORDER BY A.id;");
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
    if(is_null($res)) { return null; }
    $assoc = $res->fetch_assoc();
    if(is_null($assoc)) { return null; }
    foreach ($assoc as $key => $value) {
        $assoc[$key] = htmlentities($value, ENT_QUOTES);
    }
    return $assoc;
}

function fetch_safe_array($res) {
    if(is_null($res)) { return null; }
    $assoc = $res->fetch_row();
    if(is_null($assoc)) { return null; }
    foreach ($assoc as $key => $value) {
        $assoc[$key] = htmlentities($value, ENT_QUOTES);
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

function check_atcon($user, $passwd, $level, $conid) {
    $u = sql_safe($user);
    $p = sql_safe($passwd);

    $q = "SELECT id FROM atcon_auth WHERE perid=$u and passwd='$p' and conid=$conid and auth='$level';";
    $r = dbQuery($q);
    if($r->num_rows > 0) { return true; } 
    else { return false; }
}

function get_username($user) {
	$u = sql_safe($user);
	$q = "SELECT first_name, last_name FROM perinfo WHERE id = '$user';";
	$r = dbQuery($q);
	if ($r->num_rows <= 0)
		return $u;

	$ret = '';
	$res = fetch_safe_assoc($r);
	if ($res['first_name'] != '')
		$ret = $res['first_name'];
	if ( $res['last_name'] != '') {
		if ($ret != '')
			$ret .= ' ';
		$ret .= $res['last_name'];
	}
	return $ret;
}
?>
