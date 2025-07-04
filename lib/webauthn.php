<?php
/* webauth server side library - Used by ConTroll, adapted from lbuch/WebAuthn
 * Copyright (C) 2022 Lukas Buchs
 * license https://github.com/lbuchs/WebAuthn/blob/master/LICENSE MIT
 *
 * Server test script for WebAuthn library. Saves new registrations in session.
 *
 *            JAVASCRIPT            |          SERVER
 * ------------------------------------------------------------
 *
 *               REGISTRATION
 *
 *      window.fetch  ----------------->     getCreateArgs
 *                                                |
 *   navigator.credentials.create   <-------------'
 *           |
 *           '------------------------->     processCreate
 *                                                |
 *         alert ok or fail      <----------------'
 *
 * ------------------------------------------------------------
 *
 *              VALIDATION
 *
 *      window.fetch ------------------>      getGetArgs
 *                                                |
 *   navigator.credentials.get   <----------------'
 *           |
 *           '------------------------->      processGet
 *                                                |
 *         alert ok or fail      <----------------'
 *
 * ------------------------------------------------------------
 */

    use lbuchs\WebAuthn\Binary\ByteBuffer;

    require_once(__DIR__ . '/../Composer/vendor/autoload.php');

function createWebauthnArgs($userId, $userName, $userDisplayName, $source) {
    $userDisplayName = filter_var($userDisplayName, FILTER_SANITIZE_SPECIAL_CHARS);
    $requireResidentKey = true;
    $userVerification = 'required';
    $rpLevel = getConfValue('global', 'passkeyRpLevel', '2');
    $server = $_SERVER['SERVER_NAME'];
    if ($server == null || trim($server) == '')
        $server = 'unknown';
    $serverArr = explode('.', $server);
    $elements = count($serverArr);
    if ($elements >= $rpLevel) {
        // take right most rpLevel elements of the server name
        $rpId = implode('.', array_slice($serverArr, -$rpLevel));
    } else {
        $rpId = $server;
    }
    $crossPlatformAttachment = null;

    $formats = ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'packed', 'tpm', 'none' ];
    $excludeCredentialIds = [];

    // new Instance of the server library.
    // make sure that $rpId is the domain name.
    $name = getConfValue('global', 'conname', 'ConTroll') . ' ConTroll';
    $WebAuthn = new lbuchs\WebAuthn\WebAuthn($name, $rpId, $formats);
    $createArgs = $WebAuthn->getCreateArgs(\hex2bin($userId), $userName, $userDisplayName, 60*4,
        $requireResidentKey, $userVerification, $crossPlatformAttachment, $excludeCredentialIds);

    // save challenge to session. you have to deliver it to processGet later.
    $challenge = base64_encode($WebAuthn->getChallenge()->getBinaryString());
    setSessionVar('passkeyChallenge', $challenge);
    setSessionVar('passkeyRPid', $rpId);
    setSessionVar('passkeyName', $name);
    setSessionVar('passkeyUserId', $userId);

    return $createArgs;
}

function savePasskey($att, $userId, $userName, $userDisplayName, $source) {
    $clientDataJSON = !empty($att['clientDataJSON']) ? base64_decode($att['clientDataJSON']) : null;
    $attestationObject = !empty($att['attestationObject']) ? base64_decode($att['attestationObject']) : null;
    $challengeStr = getSessionVar('passkeyChallenge');
    $challenge = base64_decode($challengeStr);

    $formats = ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'packed', 'tpm', 'none' ];

    $WebAuthn = new lbuchs\WebAuthn\WebAuthn(getSessionVar('passkeyName'), getSessionVar('passkeyRPid'), $formats);
    // processCreate returns data to be stored for future logins.
    $dataObj = $WebAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, true, true, false);
    $data = [];
    $data['rpId'] = $dataObj->rpId;
    $data['attestationFormat'] = $dataObj->attestationFormat;
    $data['credentialId'] = base64_encode($dataObj->credentialId);
    $data['credentialPublicKey'] = $dataObj->credentialPublicKey;
    $data['AAGUID'] = base64_encode($dataObj->AAGUID);
    $data['userPresent'] = $dataObj->userPresent;
    $data['userVerified'] = $dataObj->userVerified;

    // add user infos
    $data['userId'] = $userId;
    $data['userName'] = $userName;
    $data['userDisplayName'] = $userDisplayName;

    clearSession('passkey');
    // now insert the key into the database
    $insPK = <<<EOS
INSERT INTO passkeys(credentialId, relyingParty, source, userId, userName, userDisplayName, publicKey, createIP) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?);
EOS;
    $keyFind = <<<EOS
SELECT id
FROM passkeys
WHERE credentialId = ? AND relyingParty = ?;
EOS;
    $updPK = <<<EOS
UPDATE passkeys
SET userId = ?, userName = ?, userDisplayName = ?, publicKey = ?
WHERE id = ?;
EOS;
    $keyFindR = dbSafeQuery($keyFind, 'ss', array($data['credentialId'], $data['rpId']));
    if ($keyFindR === false || $keyFindR->num_rows === 0) {
        $insKey = dbSafeInsert($insPK, 'ssssssss', array($data['credentialId'], $data['rpId'], $source, $userId, $userName, $userDisplayName,
            $data['credentialPublicKey'], $_SERVER['REMOTE_ADDR']));
        if ($insKey === false) {
            $data['message'] = "Unable to store key in the database";
            $data['status'] = 'error';
            return $data;
        }
        $data['passkeyId'] = $insKey;
    } else {
        $id = $keyFindR->fetch_assoc()['id'];
        $keyFindR->free();
        $num_rows = dbSafeCmd($updPK, 'ssssi', array($userId, $userName, $userDisplayName, $data['publicKey'], $id));
        $data['passkeyId'] = $id;
    }

    return $data;
}

function getWebauthnArgs($source) {
    $requireResidentKey = true;
    $userVerification = 'required';
    $rpLevel = getConfValue('global', 'passkeyRpLevel', '2');
    $server = $_SERVER['SERVER_NAME'];
    if ($server == null || trim($server) == '')
        $server = 'unknown';
    $serverArr = explode('.', $server);
    $elements = count($serverArr);
    if ($elements >= $rpLevel) {
        // take right most rpLevel elements of the server name
        $rpId = implode('.', array_slice($serverArr, -$rpLevel));
    } else {
        $rpId = $server;
    }
    $crossPlatformAttachment = null;

    $formats = ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'packed', 'tpm', 'none'];
    $excludeCredentialIds = [];

    // new Instance of the server library.
    // make sure that $rpId is the domain name.
    $name = getConfValue('global', 'conname', 'ConTroll') . ' ConTroll';
    $WebAuthn = new lbuchs\WebAuthn\WebAuthn($name, $rpId, $formats);
    $getArgs = $WebAuthn->getGetArgs(null, 60*4, true, true, true, true, true, true);

    // save challenge to session. you have to deliver it to processGet later.
    $challenge = base64_encode($WebAuthn->getChallenge()->getBinaryString());
    setSessionVar('passkeyChallenge', $challenge);
    setSessionVar('passkeyRPid', $rpId);
    setSessionVar('passkeyName', $name);

    return $getArgs;
}

function checkPasskey($att, $source) {
    $clientDataJSON = !empty($att['clientDataJSON']) ? base64_decode($att['clientDataJSON']) : null;
    $authenticatorData = !empty($att['authenticatorData']) ? base64_decode($att['authenticatorData']) : null;
    $signature = !empty($att['signature']) ? base64_decode($att['signature']) : null;
    $userHandle = !empty($att['userHandle']) ? base64_decode($att['userHandle']) : null;
    $id = !empty($att['id']) ? base64_decode($att['id']) : null;
    $challengeStr = getSessionVar('passkeyChallenge');
    $challenge = base64_decode($challengeStr);
    $credentialPublicKey = null;

    // looking up correspondending public key of the credential id
    // you should also validate that only ids of the given user name
    // are taken for the login.
    $pkQ = <<<EOS
SELECT *
FROM passkeys
WHERE credentialId = ?;
EOS;
    $pkR = dbSafeQuery($pkQ, 's', array($att['id']));
    if ($pkR === false || $pkR->num_rows === 0) {
        // no matching key in our database
        return array('status' => 'error',
            'message' => 'No matching entry found for that passkey, please log in with a different passkey, or use a different method and create a new passkey.');
    }
    $passkey = $pkR->fetch_assoc();
    $credentialPublicKey = $passkey['publicKey'];

    $formats = ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'packed', 'tpm', 'none' ];

    $WebAuthn = new lbuchs\WebAuthn\WebAuthn(getSessionVar('passkeyName'), getSessionVar('passkeyRPid'), $formats);
    // process the get request. throws WebAuthnException if it fails
    try {
        $WebAuthn->processGet($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey, $challenge, null, true);
    }
    catch (Exception $e) {
        return array('status' => 'error',
            'message' => 'Invalid passkey returned, please log in with a different passkey, or use a different method and create a new passkey.');
    }

    // we got a match, update the last used and IP
    $upQ = <<<EOS
UPDATE passkeys
SET  lastUsedDate = NOW(), lastUsedIP = ?, useCount = useCount + 1
WHERE id = ?;
EOS;
    $numUpd = dbSafeCmd($upQ, 'si', array($_SERVER['REMOTE_ADDR'], $passkey['id']));

    clearSession('passkey');
    return array('status' => 'success', 'message' => 'Authentication successful', 'passkey' => $passkey);
}

/*

    // ------------------------------------
    // request for get arguments
    // ------------------------------------

    } else if ($fn === 'getGetArgs') {
        $ids = [];

        if ($requireResidentKey) {
            if (!isset($_SESSION['registrations']) || !is_array($_SESSION['registrations']) || count($_SESSION['registrations']) === 0) {
                throw new Exception('we do not have any registrations in session to check the registration');
            }

        } else {
            // load registrations from session stored there by processCreate.
            // normaly you have to load the credential Id's for a username
            // from the database.
            if (isset($_SESSION['registrations']) && is_array($_SESSION['registrations'])) {
                foreach ($_SESSION['registrations'] as $reg) {
                    if ($reg->userId === $userId) {
                        $ids[] = $reg->credentialId;
                    }
                }
            }

            if (count($ids) === 0) {
                throw new Exception('no registrations in session for userId ' . $userId);
            }
        }

        $getArgs = $WebAuthn->getGetArgs($ids, 60*4, $typeUsb, $typeNfc, $typeBle, $typeHyb, $typeInt, $userVerification);

        header('Content-Type: application/json');
        print(json_encode($getArgs));

        // save challange to session. you have to deliver it to processGet later.
        $_SESSION['challenge'] = $WebAuthn->getChallenge();

    // ------------------------------------
    // proccess get
    // ------------------------------------

    } else if ($fn === 'processGet') {
        $clientDataJSON = !empty($post->clientDataJSON) ? base64_decode($post->clientDataJSON) : null;
        $authenticatorData = !empty($post->authenticatorData) ? base64_decode($post->authenticatorData) : null;
        $signature = !empty($post->signature) ? base64_decode($post->signature) : null;
        $userHandle = !empty($post->userHandle) ? base64_decode($post->userHandle) : null;
        $id = !empty($post->id) ? base64_decode($post->id) : null;
        $challenge = $_SESSION['challenge'] ?? '';
        $credentialPublicKey = null;

        // looking up correspondending public key of the credential id
        // you should also validate that only ids of the given user name
        // are taken for the login.
        if (isset($_SESSION['registrations']) && is_array($_SESSION['registrations'])) {
            foreach ($_SESSION['registrations'] as $reg) {
                if ($reg->credentialId === $id) {
                    $credentialPublicKey = $reg->credentialPublicKey;
                    break;
                }
            }
        }

        if ($credentialPublicKey === null) {
            throw new Exception('Public Key for credential ID not found!');
        }

        // if we have resident key, we have to verify that the userHandle is the provided userId at registration
        if ($requireResidentKey && $userHandle !== hex2bin($reg->userId)) {
            throw new \Exception('userId doesnt match (is ' . bin2hex($userHandle) . ' but expect ' . $reg->userId . ')');
        }

        // process the get request. throws WebAuthnException if it fails
        $WebAuthn->processGet($clientDataJSON, $authenticatorData, $signature, $credentialPublicKey, $challenge, null, $userVerification === 'required');

        $return = new stdClass();
        $return->success = true;

        header('Content-Type: application/json');
        print(json_encode($return));
*/