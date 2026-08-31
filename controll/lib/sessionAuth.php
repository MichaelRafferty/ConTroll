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
    //      source - google, or passke
    //      authId - google: sub, passkey: key from passkey table
    //      auths - array of auth strings authorized for this user

    private $authToken;
    private $expSecs;
    private $authExpSecs;
    private $refreshGrace;
    private $use;
    private $sessionLogFile;

    function __construct($use) {
        if (!isSessionVar('authToken'))
            $this->authToken = null;
        else {
            $this->authToken = getSessionVar('authToken');
            if ((!array_key_exists('userPerid', $this->authToken)) || $this->authToken['userPerid'] == null) {
                $this->authToken['userPerid'] = null;
                unsetSessionVar('authToken');
            }  else if (!array_key_exists('auths', $this->authToken)) {
                $this->authToken['auths'] = $this->loadAuth($this->authToken['userId']);
                $this->authToken['authExpire'] = time();
            }
        }
        $this->use = $use;
        $this->expSecs = getConfValue('controll', 'tokenExpireHrs', 8) * 3600;
        $this->authExpSecs = getConfValue('controll', 'authExpireHrs', 0.25)  * 3600;
        $this->refreshGrace = getConfValue('controll', 'expiregrace', 1) * 3600;
        if ($this->refreshGrace > ($this->expSecs / 2))
            $this->refreshGrace = $this->expSecs / 2;
        $this->sessionLogFile = getConfValue('log', 'logins');
    }

    // get functions
    function getSource() : string {
        if (!$this->authToken)
            return "Not Logged In";

        return $this->authToken['source'];
    }

    function getEmail(): string {
        if (!$this->authToken)
            return 'Not Logged In';

        return $this->authToken['userEmail'];
    }

    function getName(): string {
        if (!$this->authToken)
            return 'Not Logged In';

        return $this->authToken['userName'];
    }

    function getUserId(): string {
        if (!$this->authToken)
            return 'Not Logged In';

        return $this->authToken['userId'];
    }

    function getPerid(): string {
        if (!$this->authToken)
            return 'Not Logged In';

        return $this->authToken['userPerid'];
    }

    function getAuthId(): string {
        if (!$this->authToken)
            return 'Not Logged In';

        return $this->authToken['authId'];
    }

    function getExpire() : int {
        if (!$this->authToken)
            return -1;

        if ($this->use == 'web')
            return $this->authToken['webExpire'];

        return $this->authToken['scriptExpire'];
    }

    function getAuths() : array {
        if (!$this->authToken)
            return [];

        return $this->authToken['auths'];
    }

    function getRefreshCount() : int {
        if (!$this->authToken)
            return -1;

        return $this->authToken['refreshCount'];
    }

    function isLoggedIn() : bool {
        if ($this->authToken == null)
            return false;

        $status = $this->checkToken();
        return $status != 'expired';
    }

    function getRefresh() : int {
        if (!$this->authToken)
            return -1;

        if ($this->use == 'web')
            return $this->authToken['webExpire'] - $this->refreshGrace;

        return $this->authToken['scriptExpire'] - $this->refreshGrace;
    }

    // deleteToken - delete the token (logoff)
    function deleteToken() : void {
        unsetSessionVar('authToken');
        unsetSessionVar('user_perid');
        $this->authToken = null;
    }

    // checkToken - check token for validity and refresh
    function checkToken(): string {
        $refreshNeeded = false;

        if ($this->authToken == null)
            return 'none';

        $now = time();
        if ($this->use == 'web') {
            if ($now > $this->authToken['webExpire'])
                return 'expired';

            $refreshNeeded = ($now + $this->refreshGrace) > $this->authToken['webExpire'];
        } else {
            if ($now > $this->authToken['scriptExpire'])
                return 'expired';

            $refreshNeeded = ($now + $this->refreshGrace) > $this->authToken['webExpire'];
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
            case 'internal':
            case 'google':
                $uQ = <<<EOS
SELECT *
FROM user
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
        if ($uR === false)
            return false;

        if ($uR->num_rows == 0 && function_exists('get_admin_sets')) {
            $user = $this->buildUser($email);
            if ($user == null)
                return false;
        } else {
            $user = $uR->fetch_assoc();
            $uR->free();
        }

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

        $type = $this->authToken == null ? 'login' : 'refresh';
        $now = time();
        $this->authToken = [];
        $this->authToken['webExpire'] = $now + $this->expSecs;
        $this->authToken['scriptExpire'] = $now + ($this->expSecs * 1.5);
        $this->authToken['authExpire'] = $now + $this->authExpSecs;
        $this->authToken['userId'] = $user['id'];
        $this->authToken['userPerid'] = $user['perid'];
        $this->authToken['userEmail'] = $user['email'];
        $this->authToken['userName'] = $user['name'];
        $this->authToken['auths'] = $this->loadAuth($this->authToken['userId']);
        $this->authToken['source'] = $source;
        $this->authToken['authId'] = $user['google_sub'];
        $this->authToken['refreshCount'] = 0;
        setSessionVar('authToken', $this->authToken);
        // credit card processing still needs user_perid in session and cannot deal with getting an authtoken, as it app agnostic
        setSessionVar('user_perid', $user['perid']);
        $source = $this->getSource();
        $sesPerid = getSessionVar('user_perid');
        if (!$sesPerid)
            $sesPerid = '(no login)';
        setSessionVar('authToken', $this->authToken);
        $logMsg = "ConTroll Admin $source $type by perid:$sesPerid, userid:" . $this->getUserId()
            . ', token perid:' . $this->getPerid() . ', email:' . $this->getEmail() . ', name:' . $this->getName()
            . ' from ' . $_SERVER['REMOTE_ADDR'];
        $this->logSession($logMsg);
        return true;
    }

    function refreshExpire() : bool {
        $now = time();
        $this->authToken['webExpire'] = $now + $this->expSecs;
        $this->authToken['scriptExpire'] = $now + ($this->expSecs * 1.5);
        $this->authToken['authExpire'] = $now + $this->authExpSecs;
        $this->authToken['refreshCount']++;
        $source = $this->getSource();
        $sesPerid = getSessionVar('user_perid');
        if (!$sesPerid)
            $sesPerid = '(no login)';
        setSessionVar('authToken', $this->authToken);
        $logMsg = "ConTroll Admin $source refresh by perid:$sesPerid, userid:" . $this->getUserId()
        . ', token perid:' . $this->getPerid() . ', email:' . $this->getEmail() . ', name:' . $this->getName()
        . ' from ' . $_SERVER['REMOTE_ADDR'];
        $this->logSession($logMsg);
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
        while ($authName = $authR->fetch_row()) {
            $auths[] = $authName[0];
        }
        $authR->free();
        return $auths;
    }

    // checkAuth - check if the user has a particular auth
    function checkAuth($authName): bool {
        if ($this->authToken == null)
            return false;

        return in_array($authName, $this->authToken['auths']);
    }

    // log session actions
    function logSession($logMsg) {
        // if no log file specified, revert to web_error_log
        if (!$this->sessionLogFile) {
            web_error_log($logMsg, 'controll_auth', false);
            return;
        }

        // if file cannot be opened, revert to web_error_log
        $fh = fopen($this->sessionLogFile, 'a');
        if ($fh === false) {
            web_error_log($logMsg, 'controll_auth', false);
            return;
        }

        $now = date('Y/m/d H:i:s');
        fprintf($fh, "%s: %s\n", $now, $logMsg);
        fclose($fh);
    }

    // build user - build a default user if allowed
    function buildUser($email) {
        // check if allowed auto create
        $autoCreate = getConfValue('controll', 'autoCreate', 0);
        if ($autoCreate == 0)
            return null;

        // ok, we have the rights to create it get the list of domains to validate
        $authDomans =  trim(getConfValue('controll', 'autoDomains', ''));
        if ($authDomans == '')
            return null;
        // validate we have an email address to check
        if (trim($email) == '')
            return null;

        // see if this user exists in perinfo
        $userQ = <<<EOS
SELECT id, fullName
FROM perinfo WHERE email_addr = ?;
EOS;
        $userR = dbSafeQuery($userQ, 's', array($email));
        if ($userR === false || $userR->num_rows != 1) {
            page_init($page,
                /*css*/ array('css/base.css'),
                /*js*/  array(
                    'jslib/passkey.js',
                    'js/login.js'
                ),
                null);
            echo <<<EOS
<h1 class="h3">Error: there must be a single user in the system with the email address '$email'</h1>
EOS;
            if ($userR !== false) {
                if ($userR->num_rows == 0) {
                    echo <<<EOS
<p>You attempted to log in with an email address that will auto create a user with general reporting rights in the ConTroll Administrative back end.
No such email exists in the ConTroll system.  Please create an account for yourself in the registration portal using the email '$email,
and wait for a permanent id to be assigned to you.</p>
<p>Once you have been assigned a permanent id (your registration portal account will say "Membership Number:" instead of "Temp Membership Number:"),
then return to controll to try to login to the ConTroll administrative system.</p>
EOS;
                } else {
                    echo <<<EOS
<p>There are multiple users in the system with the email address '$email'. Please contact the system administrator to ask them to create your account.</p>
EOS;
                }
            }

            return null;    // dups or does not exist
        }

        $user = $userR->fetch_assoc();
        $perid = $user['id'];
        $userName = $user['fullName'];

        $authDomains = explode(',', $authDomans);
        $lastAt = strrpos($email, '@');
        if ($lastAt === false)
            return null;
        $domain = trim(substr($email,$lastAt + 1));

        // this is what we will insert if the domain is one of ours
        $authInsertQ = <<<EOS
INSERT IGNORE INTO user_auth(user_id, auth_id)
SELECT ?, id
FROM auth
WHERE name = ?;
EOS;
        // now insert the user
        $userInsertQ = <<<EOS
INSERT IGNORE INTO user(perid, email, google_sub, name)
VALUES (?, ?, null, ?);
EOS;

        $authList = getConfValue('controll', 'autoSets', '');
        $sets = get_admin_sets();
// now validate the domains
        foreach ($authDomains as $allowed) {
            if ($domain == 'gmail.com')
                continue;

            if ($domain == trim($allowed)) {
                // ok, its one of ours, add the user to the user list

                $userId = dbSafeInsert($userInsertQ, 'iss', array ($perid, $email, $userName));
                if ($userId === false || $userId < 0)
                    return null;    // cannot create the user

                if ($authList != '') {
                    $auths = explode(',', $authList);
                    // now insert the auths into the auths table
                    foreach ($auths as $auth) {
                        if (trim($auth) == '')
                            continue;

                        // now load the auths in the set
                        if (array_key_exists($auth, $sets)) {
                            foreach ($sets[$auth] AS $perm) {
                                error_log("Insert auth $perm for $userId, $userName");
                                dbSafeInsert($authInsertQ, 'is', array ($userId, $perm));
                            }
                        }
                    }
                }
                return array('id' => $userId, 'perid' => $perid, 'email' => $email, 'google_sub' => '', 'name' => $userName, 'new' => null);
            }
        }

        return null;
    }
}
