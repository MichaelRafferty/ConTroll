<?php
// ConTroll Registration System
// controll back end
// authentication session management
// Copyright 2026, Michael Rafferty

class AuthToken
{
    // definition of a session authorization token
    //  structured array:
    //      token webpage timeout (unix time)
    //      token script timeout (unix time)
    //      auths timeout (unix time)
    //      userId - from user table
    //      userPerid - similarly from user table
    //      userEmail - email address from user table
    //      source - google, or passkey
    //      authId - google: sub, passkey: key from passkey table
    //      auths - array of auth strings authorized for this user

    private $authToken;
    private $expSecs;
    private $authExpSecs;
    private $refreshGrace;

    function __construct() {
        if (!isSessionVar('authToken'))
            $this->authToken = null;
        else {
            $this->authToken = getSessionVar('authToken');
            if (!array_key_exists('auths', $this->authToken)) {
                $this->authToken['auths'] = $this->loadAuth($this->authToken['userId']);
                $this->authToken['authExpire'] = time();
            }
        }
        $this->expSecs = getConfValue('controll', 'tokenExpireHrs', 8) * 3600;
        $this->authExpSecs = getConfValue('controll', 'authExpireHrs', 0.25)  * 3600;
        $this->refreshGrace = getConfValue('controll', 'expiregrace', 1) * 3600;
    }

    // checkToken - check token for validity and refresh
    function checkToken($use): string {
        $refreshNeeded = false;

        if ($this->authToken == null)
            return 'none';

        $now = time();
        if ($use == 'web') {
            if ($now > $this->authToken['webExpire'])
                return 'expired';

            $refreshNeeded = ($now + $this->refreshGrace) > $this->authToken['webExpire'];
        } else {
            if ($now > $this->authToken['scriptExpire'])
                return 'expired';

            $refreshNeeded = ($now + $this->refreshGrace) > $this->authToken['scriptExpire'];
        }

        // we have a valid login token, check if the auths need reload
        if ($now > $this->authToken['authExpire']) {
            // refresh the auth contents, update the token and store it back in the structure
            $this->authToken['authExpire'] = $now;
            $this->authToken['auths'] = $this->loadAuth($this->authToken['userId']);
            setSessionVar('authToken', $this->authToken);
        }

        return $refreshNeeded ? 'refresh' : 'valid';
    }

    function buildToken($source, $authId, $email) : bool {
        switch ($source) {
            case 'google':
                $uQ = <<<EOS
SELECT *
FROM
user
WHERE google_sub = ? OR email = ?
ORDER BY google_sub DESC;
EOS;
                $typestr = 'ss';
                $valArray = array($authId, $email);
                break;
            case 'passkey':
                $rp = $_SERVER['SERVER_NAME'];
                $uQ = <<<EOS
SELECT u.*
FROM passkeys p
JOIN user u ON u.email = p.username OR u.google_sub = p.userId
WHERE p.relyingParty = ? AND p.userName = ? AND p.userId = ?
ORDER BY google_sub DESC;
EOS;
                $typestr = 'sss';
                $valArray = array($rp, $email, $authId);
                break;
            default:
                return false;
        }

        $uR = dbSafeQuery($uQ, $typestr, $valArray);
        if ($uR == false || $uR->num_rows == 0)
            return false;

        $user = $uR->fetch_assoc();
        $uR->free();

        // set google_sub on first login
        if ($user['google_sub'] == '') {
            $uU = <<<EOS
UPDATE user
SET google_sub = ?
WHERE id = ?;
EOS;
            if (dbSafeCmd($uU, 'si', array($authId, $user['id'])) !== false) {
                $user['google_sub'] = $authId;
            }
        }

        $now = time();
        $this->authToken = [];
        $this->authToken['webExpire'] = $now + $this->expSecs;
        $this->authToken['scriptExpire'] = $now + ($this->expSecs * 1.5);
        $this->authToken['authExpire'] = $now + $this->authExpSecs;
        $this->authToken['userId'] = $user['id'];
        $this->authToken['userPerid'] = $user['perid'];
        $this->authToken['userEmail'] = $user['email'];
        $this->authToken['auths'] = $this->loadAuth($this->authToken['userId']);
        $this->authToken['source'] = $source;
        $this->authToken['authId'] = $user['google_sub'];
        setSessionVar('authToken', $this->authToken);
        return true;
    }

    // loadAuth - load the auths array for a userId
    function loadAuth($userId): array {
        $authQ = <<<EOS
    SELECT name
    FROM user_auth u
    JOIN auth a ON (a.id = u.auth_id)
    WHERE u.user_id = ?
    ORDER BY name;
    EOS;

        $authR = dbSafeQuery($authQ, 'i', array ($userId));
        if ($authR === false)
            return [];
        $auths = [];
        while ($authName = $authR->fetch_row()[0]) {
            $auths[] = $authName;
        }
        $authR->free();
        return $auths;
    }

    // checkAuth - check if the user has a particular auth
    function checkAuth($authName): bool {
        return in_array($authName, $this->authToken['auths']);
    }
}
