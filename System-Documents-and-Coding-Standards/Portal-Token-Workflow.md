# Portal Token Workflow
This document will trace the flow of various portal tokens from creation and field contents to use and logging.

The followinfg types of tkens are used by the ConTroll Portal
* Email Login Tokens
* Oauth2 Login Tokens
* Account Selection from Login Token
* Management Approval Tokens
* Identity Verification Tokens
* Oauth2 Server Validation Tokens

## Email Login Token Workflow
This workflow is used by the login with email address option in the 'index' page of the Portal.
1. The user presses the 'Create Account or Logimn with Email with Authentication' button.
2. Javascript code display the prompt to enter the email and the 'Send Link' button disabled.
   1. When a valid email (by format) is typed in the prompt, the send button is enabled.
   2. Pressing the send button takes the user to the next step.
3. The script processLoginRequest is passed:
   1. email: email address
   2. type: 'token'
4. processLoginRequest.php processes the request via sendEmailToken(email)
   1. Check if a token was sent in the past 5 minutes... if so, ask them to wait
   2. Insert a token sent record in the portalTokenLinks table of type login
      1. //TODO: check to see why refresh type still adds login token entry in the table, should the action enum be extended for refresh?
   3. Create the associative array to be returned in the email link
      1. email => email address
      2. type => 'token-resp'
      3. ts => time()
      4. lid = portalTokenLinks entry id (key) from the insert above
      5. if 'logged in' and 'refresh' added to the array are
         1. id => login id
         2. idType => n or p for newperid/perid
         3. email_addr => email address logged in as
         4. refresh => 1
   5. The array is json encoded and encrypted using the encryptCipher, with URLencode
   6. the link is built for sending to the portal as a get string using the get parameter vid
   7. The email body is created (login or refresh versions)
   8. The email is sent as both HTML and Text versions
5. The user receives the email and clicks on the link, which calls index.php with the vid string encrypted above
   1. The token is validated to make sure it decrypts correctly, has a valid return token type ('email' in this case) and hasn't timed out. (timeout ).  
      (current hard coded email token timeout is 4 hours)
6. If logged in already
   1. the vid link is either a login as another user or a refresh of the token
      1. if email matches current logged in email
         1. If id does not match session id. this is a switch account request
            1. if banned/issue, request they contact reg and exit
      2. if email does not match currented login email, log out the session
   2. Create/Refresh the session token
   3. Set session multiple if the token indicates multiple accounts found
   4. Proceed to "Validation Complete"
7. If not loggerd in already
   1. Check to see if the link exists in the database, if not found or link email does not match the database email, declare invalid, ask user to request a new one and exit.
   2. if the link has been used an excessive numbrer of times (currently 100 hard coded), inform the user that the link has been used and ask them to 
      request a new one and exit
   3. Update the database link usage count
   4. create the token in the session
   5. use chooseAccount to find the matching accounts (see 'Choose Account')
      1. If none found, indicate error and redraw login page

## Oauth2 Login Token Workflow
1. Uses "Create Account of Login with <provider>" butto
   1. Current providers:
      1. Google
      2. Facebook (untested)
   3. Other Providers will need finding/building league/oauth2 client additions
2. Browser is instructed to reload the index.php page with the request 'oauth2=google' without refresh and a logged in session
3. Save off any existing oauth session state and then clear the current session, followed by restoring the oauth session state if it existed
4. if the session does not include that we are in the middle of an oauth2oass of the multi part process as indicated by the absense of the oauth2oass 
   session variable
   1. Set the status of the oauth2 process in ths session
      1. oauth2: <provider> as passed in request
      2. oauth2pass: setup
3. If not in 'token' state
   1. if no timeout set, set a timeout of 5 minutes for the time to get the token //TODO is 5 minutes the proper value for this timeot
   2. Check if the timeout has expired (meant for if it was previously set)
      1. if expried, clear the session and redraw the login page with an error for the timeout and exit
   3. set up the redirectURI for the return to portal from the <provider>
   4. set up the specific arguments for the <provider> and redirect to the provder for the next step in the process and exit
      1. if an error occurs in this setup, redraw the login page with an appropriate error message and exit
   5. Check that the returned parameters from the <provider> included an email address, if not, redraw the login page with an appropriate error message and 
      exit
   6. Check if this is a refresh of the token as indicated by sessionEmail being set and the email being the same as the returned email
      1. not a refresh
         1. save off all of the oauth information
         2. clear the session to prepare for a new login
         3. restore the oauth information
   7. Set up fo the new session login from the Oauth2 provider information (for the provided fields among those below)
      1. CLear the timeout
      2. email: authenticated email from <provider>
      3. displayName: displayName
      4. firstName: firstName
      5. lastName: lastName
      6. avatarURL: avatarURL
      7. subscriberId: subscriberId
      8. tokenType: oauth2
   8. Update the database subscriber id for this login in case it's changed
   9. Set the expiration of the token
      1. From portal_conf: oauthhrs
      2. Default to 8 hours if not overridden
   10. if a refresh, continue with "Validation Complete"
   11. contine with "Choose Account"   
   
## Switch Account Token Workflow
1. The session has 'multiple' as an attribute, (if not the switch account button is suppressed)
2. The user clicks on the 'Switch Account' menu button and index.php page is called in a valid session request of 'switch=account'
3. Index checks:
   1. logged in (valid session with loginId)
   2. the session is of type 'multiple'
   3. the request was 'switch=account'
4. chooseAccount is used to find the matching accounts (ee 'Choose Account')

## Choose Account Workflow
1. Use getLoginMatch to find the account(s) that use this authenticated email address
   1. if email is numeric, this is a direct login from the test harness, just retrieve that perid/newperid.
    Numeric ids are not supported in email token or oauth2 provider.
   2. if this is refresh/switch account, there is a valid id
      1. Get any perinfo entries that match that id and email
      2. Get any newperson entities that match that id and email
   3. New login (no id passed)
      1. Get any perinfo entries that use that authenticated email address and are not 'merged records' (firstname  != 'Merged' AND middle_name != 'into')
      2. Get any perinfo entries that use that authenticated email address
   4. Add in any perinfo records where the email is listed in the alternate identities (perinfoIdentities) table again skipping merged ones.
   5. Return:
      1. The an array of the matches even if empty
      2. An error message if the sizd of the array is zero
      3. If the count is 1
         1. Set up the id and idType to log the user in
         2. Return a 'success' status
2. If no array of responses was passed, return the appropriate return as an error message to the caller
3. If (count == 1)
   1. Check for banned/issue and return the error string for them to see assistance
   2. Get the authenticated email, id and idType from the matched entry
   3. If there valid session //TODO: since id would be created by "getLoginMatch" this is the wrong place to determine type
      1. if oauth: set login type to 'validation'
      2. if id change: Set log item type 'id change login', clear transaction session items
      3. if no id change: set login type to refresh
   4. no valid session: set type to new login
   5. If passedMatch has multiple flag, or current match has multiple flag
      set new multiple flag
   6. Update identity usage if the idType is perinfo ('p')
   7. Continue with "Validation Complete"

## Validation Complete Workflow
1. If not an oauth validation (it is a login)
   1. if (id != session id) (id change)
      2. unset transaction session items
      3. set up new session items for login
      4. set multiple flag in session as passed in
   2. redirect user to portal and exit
2. oauth validation
   1. retrieve the infomation for the oauth request
   1. perid (null if 'n' idType login)
   2. newperid
   3. email address
   4. fullName, first_name, last_name
   4. memberships for this person
   5. transaction dates for those membrerships
   6. Comnputed 'nomination' rights as date < portal_conf.nomdate as inTime
3. create the oauth response array from the first row returned
   1. email: email
   2. perid: perid
   3. newperid: newperid
   4. resType: oauthReq['retdata']
   5. legalName: null (redacted, not to be passed)
   6. first_name: first_name
   7. last_name: last_name
   8. fullName: fullName
4. Compute the rights based on the the 'retData'
   1. nom: any reg is category 'wsfs' or 'wsfsnom' and inTime, add 'hugo_nominate' to the rights element
   2. vote: catetory 'wsfs' and reg label does not contain ' only', add 'hugo_vote' to the rights element
5, compute encrypted json encoded with urlencode array of the response
6. redirect the user to the oauth['returl'] location and exit
 
   
   
         

