<?php
    // use Google Oauth to get the information about the user
    require_once(__DIR__ . '/../Composer/vendor/autoload.php');

    use League\OAuth2\Client\Provider\Google;

    // googleAuth - use Oauth2 to retrieve email and name of user
    //      redirectURI = which program is making the Oauth call, this is the path for it's return information.
    function googleAuth($redirectURI = null) {
        // first check for an error in the prior pass
        if (!empty($_GET['error'])) {
            $oauthParams['error'] = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
            return $oauthParams;
        }

        $googleConf = get_conf('google');
        if ($redirectURI == null || $redirectURI == '') {
            $redirectURI = $googleConf['redirect_base'];
        }

        // so we get back to here, mark that we are doing a google authentication session
        setSessionVar('oauth2', 'google');
        setSessionVar('oauth2pass', 'startup');
        $provider = new Google([
                                   'clientId' => $googleConf['client_id'],
                                   'clientSecret' => $googleConf['client_secret'],
                                   'redirectUri' => $redirectURI,
                               ]);

        if (empty($_GET['code'])) {
// If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl();
            setSessionVar('oauth2state', $provider->getState());
            setSessionVar('oauth2pass', 'auth');
            header('Location: ' . $authUrl);
            exit;
        }

        if (empty($_GET['state']) || ($_GET['state'] !== getSessionVar('oauth2state'))) {
// State is invalid, possible CSRF attack in progress
            unsetSessionVar('oauth2state');
            setSessionVar('oauth2pass', 'invalid');
            return null;
        }
        else {
// Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);

// Optional: Now you have a token you can look up a users profile data
            try {

// We got an access token, let's now get the owner details
                setSessionVar('oauth2pass', 'token');
                $ownerDetails = $provider->getResourceOwner($token);
            }
            catch (Exception $e) {

// Failed to get user details
                $oauthParams['error'] = 'Something went wrong: ' . $e->getMessage();
                return $oauthParams;
            }
        }

        if ($ownerDetails != null) {
            $oauthParams['email'] = $ownerDetails->getEmail();
            $oauthParams['displayName'] = $ownerDetails->getName();
            $oauthParams['firstName'] = $ownerDetails->getFirstName();
            $oauthParams['lastName'] = $ownerDetails->getLastName();
            $oauthParams['avatarURL'] = $ownerDetails->getAvatar();
            $oauthParams['subscriberId'] = $ownerDetails->getId();
        } else {
            $oauthParams['nodetails'] = 'Something went wrong!';
        }
        $oauthParams['token'] = $token->getToken();
        $oauthParams['refresh'] = $token->getRefreshToken();
        $oauthParams['expires'] = $token->getExpires();
        return $oauthParams;
    }