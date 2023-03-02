<?php
function login($user, $passwd, $conid): array {
    //error_log("login.php");

    if (isset($user) && isset($passwd)) {
        $q = "SELECT auth FROM atcon_auth WHERE perid=? AND passwd=? AND conid=?;";
        $r = dbSafeQuery($q, 'ssi', array($user, $passwd, $conid));
        if ($r->num_rows > 0) {
            $response['success'] = 1;
            $auths = array();
            while ($l = fetch_safe_assoc($r)) {
                array_push($auths, $l['auth']);
            }
            $response['auth'] = $auths;
        } else {
            $response['success'] = 0;
        }
    } else {
        $response['success'] = 0;
    }

    return ($response);
}
?>
