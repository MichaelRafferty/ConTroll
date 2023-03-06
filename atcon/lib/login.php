<?php
function login($user, $passwd, $conid): array {
    //error_log("login.php");

    if (isset($user) && isset($passwd)) {
        $passwd = trim($passwd);
        $dbpasswd = password_hash($passwd, PASSWORD_DEFAULT);
        $q = <<<EOS
SELECT a.auth, u.userhash, u.passwd
FROM atcon_user u 
JOIN atcon_auth a ON (a.authuser = u.id)
WHERE u.perid=? AND u.conid=?;
EOS;
        $r = dbSafeQuery($q, 'si', array($user, $conid));
        $upasswd = null;
        if ($r->num_rows > 0) {
            $response['success'] = 1;
            $auths = array();
            while ($l = fetch_safe_assoc($r)) {
                array_push($auths, $l['auth']);
                $response['userhash'] = $l['userhash'];
                if ($upasswd == null) {
                    $upasswd = $l['passwd'];
                    if ($upasswd != $passwd && !password_verify($passwd, $upasswd)) {
                        $response['success'] = 0;
                        return($response);
                    }
                }
            }
            $response['auth'] = $auths;
            if ($passwd == $upasswd) /* update old style password */ {
                $dbpasswd = password_hash($passwd, PASSWORD_DEFAULT);
                $q = <<<EOS
UPDATE atcon_user
SET passwd = ?
WHERE perid = ? AND conid = ?;
EOS;

                $r = dbSafeCmd($q, 'sii', array($dbpasswd, $user, $conid));
                $response['updated'] = $r;
            }
        } else {
            $response['success'] = 0;
        }
    } else {
        $response['success'] = 0;
    }

    return ($response);
}
?>
