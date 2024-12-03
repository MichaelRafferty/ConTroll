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
6. If logged in already
   1. the vid link is either a login as another user or a refresh of the token
      1. if email matches current logged in email, 