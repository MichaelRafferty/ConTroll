# ConTroll PHP Session Variables
While ConTroll has a large amount of debt, going forward all PHP programs should use the lib/global.php session management functions

## ConTroll PHP Session Variable Naming Format
All session variables using the new format are broken into a prefix and a name separated by slashes
as in `Ctrl/<appname>/<variablename>`.  This separates all ConTroll session variables into a grouping starting with `Ctrl/`.

### Setting the Session Prefix
In each PHP area, lib/base.php is responsible for setting the session prefix in the global variable:
`$appSessionPrefix;`.  This is done near the top of the file, and not in a function, 
so it occurs on execution of the 'Require' statement.

    global $appSessionprefix;
    $appSessionPrefix = 'Ctrl/Portal/';

### Currently Defined ConTroll Application Names

The following is a list of pre-defined appname's for the current ConTroll applications:

- API (future)
- Atcon
- ConTroll (Note: not yet converted)
- Exhibitor (consists of artist, exhibits hall, fan and vendor (dealer))
- Onlinereg (Note: not yet converted)
- Portal

## ConTroll Session PHP Functions
All of these functions are in the PHP include file` `lib/global.php`.

- `getSessionVar(name) : null | string`
    - returns the contents of the session variable `name` within the global prefix, or `null` if not found.
- `setSessionVar(name, value) : void`
    - sets (overwrites) the session variable `name` within the global prefix, to `value`.
- `isSessionVar(name) : bool`
    - returns `true` if the variable `name` within the global prefix, exists in `$_SESSION`
    - returns `false` otherwise
- `unsetSessionVar(name) : void`
    - removes the variable `with the `name`, within the global prefix, from the `$_SESSION` array
- `clearSession($prefix = '') : void`
  - removes all variables from `$_SESSION` with a name starting with the global prefix and the 
  prefix passed as `$prefix` which defaults to the empty string if omitted
  - Allows for removing multiple session variables used in a multi-step process at it's completion
if you follow a naming convention of starting those variable all with the same prefix.

## Current Session Variable usage (less prefixes) by application

- Portal:
  - avatarURL: picture returned by oauth2 providers for an account
  - displayName: Preferred Name, as returned by the oauth2 provider
  - email: email address of the login account (mail token, oauth provider)
  - firstName: first name, as returned by the oauth2 provider
  - id: id of the person logged in (from newperson or perinfo as per idType)
  - idSource: what validated the login: ('token', oauth2 provider, ...)
  - idType: 'n' or 'p' (newperson(id) or person(id))
  - lastName: last name, as returned by the oauth2 server
  - oauth2pass: intermediate variable used by oauth2 process
  - oauth2state: intermediate variable used by oauth2 process
  - sessionEmail: temporary saved value of email, used to detect if a re-auth changes the email address
  - subscriberId: subscriber identifier as returned by the oauth2 server
  - tokenExpiration: UNIX time when the session token needs refreshing.  
    - (Code should allow scripts to run 2 hours past this point,
      but notify the script caller that the session has expired and needs to be refreshed.)
  - TotalDue: intermediate variable for checking of the total amount due on a purchase
  - transId: the current transaction id of this session, unset when the payment is made and
    a new one built when something is added that will need a transaction

