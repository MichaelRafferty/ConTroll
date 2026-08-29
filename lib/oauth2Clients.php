<?php
    // use league oauth2 clients to get the information about the user
    require_once(__DIR__ . '/../Composer/vendor/autoload.php');
    require_once('oauth2-yahoo/Provider/Yahoo.php');
    require_once('oauth2-yahoo/Provider/YahooUser.php');

    use League\OAuth2\Client\Provider\Google;
    use League\OAuth2\Client\Provider\Facebook;
    use MichaelKaefer\OAuth2\Client\Provider\Amazon;
    use Unt\OAuth2\Client\Provider\MicrosoftProvider;
    use Hayageek\OAuth2\Client\Provider\Yahoo;

global $oauthProviders;
$oauthProviders = ['google', 'facebook', 'discord','amazon', 'microsoft', 'yahoo'];
// combo function for oauth identifcation

function oauth2Auth($client, $redirectURI = null) {
    global $oauthProviders;
    // check if the client is in the list of supported clients
    if (!in_array($client, $oauthProviders)) {
        return null;
    }

    // first check for an error in the prior pass
    if (!empty($_GET['error'])) {
        $oauthParams['error'] = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
        return $oauthParams;
    }

    if ($redirectURI == null || $redirectURI == '') {
        $redirectURI = getConfValue('portal', 'redirect_base', '/');
    }

    // so we get back to here, mark that we are doing a google authentication session
    setSessionVar('oauth2', $client);
    setSessionVar('oauth2pass', 'startup');
    $provider = null;
    $authURLParams = null;
    switch ($client) {
        case 'google':
            $provider = new Google([
                'clientId' => getConfValue($client, 'client_id'),
                'clientSecret' => getConfValue($client, 'client_secret'),
                'redirectUri' => $redirectURI,
            ]);
            break;
        case 'facebook':
            $provider = new Facebook([
                'clientId' => getConfValue('facebook', 'client_id'),
                'clientSecret' => getConfValue('facebook', 'client_secret'),
                'redirectUri' => $redirectURI,
            ]);
            break;
        case 'discord':
            $provider = new \Wohali\OAuth2\Client\Provider\Discord([
                'clientId' => getConfValue('discord', 'client_id'),
                'clientSecret' => getConfValue('discord', 'client_secret'),
                'redirectUri' => $redirectURI,
            ]);
            break;
        case 'amazon':
            $provider = new amazon([
                'clientId' => getConfValue('amazon', 'client_id'),
                'clientSecret' => getConfValue('amazon', 'client_secret'),
                'redirectUri' => $redirectURI,
            ]);
            break;
        case 'microsoft':
            $provider = new MicrosoftProvider([
                'clientId' => getConfValue('microsoft', 'client_id'),
                'clientSecret' => getConfValue('microsoft', 'client_secret'),
                'redirectUri' => $redirectURI,
            ]);
            $authURLParams = [
                'scope' => array_merge(
                    ['openid', 'profile', 'email'],
                    ['User.Read'])
            ];
            break;
        case 'yahoo':
            $provider = new Yahoo([
                'clientId' => getConfValue('yahoo', 'client_id'),
                'clientSecret' => getConfValue('yahoo', 'client_secret'),
                'redirectUri' => $redirectURI,
            ]);
            break;
    }

    if (empty($_GET['code'])) {
    // If we don't have an authorization code then get one
        if ($authURLParams != null && count($authURLParams) > 0) {
            $authUrl = $provider->getAuthorizationUrl($authURLParams);
        } else {
            $authUrl = $provider->getAuthorizationUrl();
        }
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
    }  else {
    // Try to get an access token (using the authorization code grant)
        $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);

    // Now you have a token you can look up a users profile data
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
        $oauthParams['subscriberId'] = $ownerDetails->getId();
        $oauthParams['email'] = $ownerDetails->getEmail();
        $oauthParams['displayName'] = $ownerDetails->getName();

        // now the ones that can vary by provider
        switch ($client) {
            case 'google':
                $oauthParams['firstName'] = $ownerDetails->getFirstName();
                $oauthParams['lastName'] = $ownerDetails->getLastName();
                $oauthParams['avatarURL'] = $ownerDetails->getAvatar();
                break;
            case 'facebook':
                $oauthParams['firstName'] = $ownerDetails->getFirstName();
                $oauthParams['lastName'] = $ownerDetails->getLastName();
                $oauthParams['avatarURL'] = $ownerDetails->getPictureUrl();
                break;
            case 'discord':
                break;
            case 'amazon':
                // nothing extra available for amazon
                break;
            case 'microsoft':
                // nothing extra available for microsoft
                break;
            case 'yahoo':
                $oauthParams['firstName'] = $ownerDetails->getFirstName();
                $oauthParams['lastName'] = $ownerDetails->getLastName();
                $oauthParams['avatarURL'] = $ownerDetails->getAvatar();
                break;
        }
    } else {
        $oauthParams['nodetails'] = 'Something went wrong!';
    }
    $oauthParams['token'] = $token->getToken();
    $oauthParams['refresh'] = $token->getRefreshToken();
    $oauthParams['expires'] = $token->getExpires();
    return $oauthParams;
}

