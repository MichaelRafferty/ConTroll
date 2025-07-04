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

require_once(__DIR__ . '/../Composer/vendor/autoload.php');

function createWebauthnArgs($userId, $userName, $userDisplayName, $source) {
    $userDisplayName = filter_var($userDisplayName, FILTER_SANITIZE_SPECIAL_CHARS);
    $requireResidentKey = true;
    $userVerification = 'required';
    $rpLevel = getConfValue('global', 'passkeyRpLevel', '2');
    $name=getConfValue('global', 'conname','ConTroll') . " ConTroll";
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

    $formats = ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'packed', 'tpm' ];
    $excludeCredentialIds = [];

    // new Instance of the server library.
    // make sure that $rpId is the domain name.
    $name = getConfValue('global', 'conname', 'ConTroll') . ' ConTroll';
    $WebAuthn = new lbuchs\WebAuthn\WebAuthn($name, $rpId, $formats);
    $createArgs = $WebAuthn->getCreateArgs(\hex2bin($userId), $userName, $userDisplayName, 60*4,
        $requireResidentKey, $userVerification, $crossPlatformAttachment, $excludeCredentialIds);

    // save challenge to session. you have to deliver it to processGet later.
    setSessionVar('passkeyChallenge', $WebAuthn->getChallenge());
    setSessionVar('passkeyRPid', $rpId);
    setSessionVar('passkeyName', $name);
    setSessionVar('passkeyUserId', $userId);

    return $createArgs;
}

function savePasskey($att, $userId, $userName, $userDisplayName) {
    $clientDataJSON = !empty($att->clientDataJSON) ? base64_decode($att->clientDataJSON) : null;
    $attestationObject = !empty($att->attestationObject) ? base64_decode($att->attestationObject) : null;
    $challenge = getSessionVar('passkeyChallenge');

    $formats = ['android-key', 'android-safetynet', 'apple', 'fido-u2f', 'packed', 'tpm' ];

    $WebAuthn = new lbuchs\WebAuthn\WebAuthn(getSessionVar('passkeyName'), getSessionVar('rpId'), $formats);
    // processCreate returns data to be stored for future logins.
    $data = $WebAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, true, true, false);
    $data = json_decode(json_encode($data), true); // convert from object to structure
    // add user infos
    $data['userId'] = $userId;
    $data['userName'] = $userName;
    $data['userDisplayName'] = $userDisplayName;

    // now insert the key into the database
    return $data;
}

/*

    // ------------------------------------
    // request for create arguments
    // ------------------------------------

    if ($fn === 'getCreateArgs') {
        $createArgs = $WebAuthn->getCreateArgs(\hex2bin($userId), $userName, $userDisplayName, 60*4, $requireResidentKey, $userVerification, $crossPlatformAttachment);

        header('Content-Type: application/json');
        print(json_encode($createArgs));

        // save challange to session. you have to deliver it to processGet later.
        $_SESSION['challenge'] = $WebAuthn->getChallenge();



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
    // process create
    // ------------------------------------

    } else if ($fn === 'processCreate') {
        $clientDataJSON = !empty($post->clientDataJSON) ? base64_decode($post->clientDataJSON) : null;
        $attestationObject = !empty($post->attestationObject) ? base64_decode($post->attestationObject) : null;
        $challenge = $_SESSION['challenge'] ?? null;

        // processCreate returns data to be stored for future logins.
        // in this example we store it in the php session.
        // Normally you have to store the data in a database connected
        // with the username.
        $data = $WebAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, $userVerification === 'required', true, false);

        // add user infos
        $data->userId = $userId;
        $data->userName = $userName;
        $data->userDisplayName = $userDisplayName;

        if (!isset($_SESSION['registrations']) || !array_key_exists('registrations', $_SESSION) || !is_array($_SESSION['registrations'])) {
            $_SESSION['registrations'] = [];
        }
        $_SESSION['registrations'][] = $data;

        $msg = 'registration success.';
        if ($data->rootValid === false) {
            $msg = 'registration ok, but certificate does not match any of the selected root ca.';
        }

        $return = new stdClass();
        $return->success = true;
        $return->msg = $msg;

        header('Content-Type: application/json');
        print(json_encode($return));



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

    // ------------------------------------
    // proccess clear registrations
    // ------------------------------------

    } else if ($fn === 'clearRegistrations') {
        $_SESSION['registrations'] = null;
        $_SESSION['challenge'] = null;

        $return = new stdClass();
        $return->success = true;
        $return->msg = 'all registrations deleted';

        header('Content-Type: application/json');
        print(json_encode($return));

    // ------------------------------------
    // display stored data as HTML
    // ------------------------------------

    } else if ($fn === 'getStoredDataHtml') {
        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html><head><style>tr:nth-child(even){background-color: #f2f2f2;}</style></head>';
        $html .= '<body style="font-family:sans-serif">';
        if (isset($_SESSION['registrations']) && is_array($_SESSION['registrations'])) {
            $html .= '<p>There are ' . count($_SESSION['registrations']) . ' registrations in this session:</p>';
            foreach ($_SESSION['registrations'] as $reg) {
                $html .= '<table style="border:1px solid black;margin:10px 0;">';
                foreach ($reg as $key => $value) {

                    if (is_bool($value)) {
                        $value = $value ? 'yes' : 'no';

                    } else if (is_null($value)) {
                        $value = 'null';

                    } else if (is_object($value)) {
                        $value = chunk_split(strval($value), 64);

                    } else if (is_string($value) && strlen($value) > 0 && htmlspecialchars($value, ENT_QUOTES) === '') {
                        $value = chunk_split(bin2hex($value), 64);
                    }
                    $html .= '<tr><td>' . htmlspecialchars($key) . '</td><td style="font-family:monospace;">' . nl2br(htmlspecialchars($value)) . '</td>';
                }
                $html .= '</table>';
            }
        } else {
            $html .= '<p>There are no registrations in this session.</p>';
        }
        $html .= '</body></html>';

        header('Content-Type: text/html');
        print $html;

    // ------------------------------------
    // get root certs from FIDO Alliance Metadata Service
    // ------------------------------------

    } else if ($fn === 'queryFidoMetaDataService') {

        $mdsFolder = 'rootCertificates/mds';
        $success = false;
        $msg = null;

        // fetch only 1x / 24h
        $lastFetch = \is_file($mdsFolder .  '/lastMdsFetch.txt') ? \strtotime(\file_get_contents($mdsFolder .  '/lastMdsFetch.txt')) : 0;
        if ($lastFetch + (3600*48) < \time()) {
            $cnt = $WebAuthn->queryFidoMetaDataService($mdsFolder);
            $success = true;
            \file_put_contents($mdsFolder .  '/lastMdsFetch.txt', date('r'));
            $msg = 'successfully queried FIDO Alliance Metadata Service - ' . $cnt . ' certificates downloaded.';

        } else {
            $msg = 'Fail: last fetch was at ' . date('r', $lastFetch) . ' - fetch only 1x every 48h';
        }

        $return = new stdClass();
        $return->success = $success;
        $return->msg = $msg;

        header('Content-Type: application/json');
        print(json_encode($return));
    }

} catch (Throwable $ex) {
    $return = new stdClass();
    $return->success = false;
    $return->msg = $ex->getMessage();

    header('Content-Type: application/json');
    print(json_encode($return));
}
*/