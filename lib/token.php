<?php
// token.php - a global library to deal with session cookie stored authentication tokens
// as well as email and oauth2 authentication.

// token rules:
// all tokens are good for 8 hours before needing refresh from the source.  This is the default amount.
//      It can be overridden by the appropriate section of the config file having variable oauthhrs= and emailhrs=
//          for the number of hours to use for each source.
//  front end checking of the token will indicate an immediate need to refresh the token before continuing
//  back end access (scripts/javascript) will add an additional 2 hours to the token for performing the script
//  but also set a return value that warns the caller that they need to refresh the token.
//  A corresponding javascript library will work on refreshing the token.
//  The goal is not to lose the page the person is on or any partial results, but to allow them to refresh the token.
//  NOTE: if the script is beyond the two hour limit, it will deny performing the action and return a refresh error
//      requiring an immediate refresh before thing can continue.

// Sessions are application specific, so any redirect point has to be within the application, so it can access the session token.
// All session tokens use the same set of names/values within the prefix of the application
//  1. The session prefix is a global variable $appSessionPrefix, which is defined with base.php
//  2. Tokens will start with the prefix T/ and will be re-labeled from their existing names to new ones accessed by this package once converted
//  3. Existing Session Variables related to the token  (new names will be T/)
//      a.  email: the matching email for this validated session (actual email validated, which may be a different perinfoIdentity from the perinfo email)
//      b.  id: the perid/newperid for this person in the table based on:
//      c.  idtype: the table id refers to, 'n' for newperson (temp id), 'p' for perinfo (permanent id)
//      c.  idSource: validation type: token (email), google, facebook, etc.
//      d.  multiple: if the email address refers to multiple email accounts this refers.
//               This is the mail for the person used to do the perinfo/newperson search
//      e.  tokenType: 'oauth2' for oauth2 based tokens, which provides some or all of the following fields
//          1. displayName: how that oauth2 vendor displays the name for the user
//          2. firstName: their first name
//          3. lastName: their last name
//          4. avatarURL: url to a thumbnail avatar or picture
//          5. subscriberId: unique number identifying this subscriber, remains constant even if email address is updated
//          6. oauth2pass: the multi-pass process of oauth2 is in progress, continue the redirect
//          7. oauth2timeout: a max amount of time to honor the oauth2pass variable to avoid loops (? should this become a loop count instead)
//      f.  tokenExpiration: unix time in seconds when the token expires

// isTokenValid(script = false) - check if a token exists and is still valid
//      script:     true, if being accessed by a script, and the 2 hour extra rule applies, false if front end token, no extra time limit, default=false
//  returns:    'none': not logged in, no token found
//              'valid': valid token exists
//              'refresh': refresh required now
//              'refsoon': refresh required soon (within 1 hour of refresh, or in 2 hour window for script
//              'expired': token exists, but it's expired, we need to do a refresh now and not do the work

function isTokenValid($script = false) {
    $tokenExpiration = getSessionVar('tokenExpiration');
    if ($tokenExpiration == null) // null = no token in session
        return 'none';

    $now = time();
    if ($now < ($tokenExpiration - 3600) // if not within 1 hour of refresh, valid
        return 'valid';

    if ($now < $tokenExpiration)  // if < expiration, we are within that hour refsoon;
        return 'refsoon';

    if ($now < ($tokenExpiration + ($script ? 1 : 2) * 3600)) // 1 hour for front end, 2 hour for script, allow refresh
        return 'refresh';

    return 'expired';
}

// getTokenProvider() - return a printable name of the token provider
function getTokenProvider() {
    $provider = getSessionVar('idSource');
    switch ($provider) {
        case 'facebook':
            return 'Facebook';
        case 'google':
            return 'Google';
        case 'token':
        case 'email':
            return 'Email';
    }
    return 'None';
}


