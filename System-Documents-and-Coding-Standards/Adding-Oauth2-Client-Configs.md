# Creating Oauth2 Client Configurations
ConTroll uses Oauth2 clients to allow for "login with" or authorization. Each of these API requires entries in reg_secret.ini configuration file and setups 
by each Convention in the respective Oauth2 Providers to allow for this service.

This document will first discuss how to set up the already supported Oauth2 Providers and then discuss how to add an entirely new provider.

## Common Fields for each Provider
The configuration in the reg_secret.ini file is very standard for each provider.  It consists of three major fields:

- **app_name**: This is the name of the application that will be displayed to users when they are prompted to authorize the application.
- **client_id**: This is the unique identifier for the client application. It is provided by the Oauth2 provider when you register your application.
- **client_secret**: This is a secret key that is used to authenticate the client application. It is also provided by the Oauth2 provider when you register your application.


## Required Oauth2 "Login With" Provider
The only required login with provider is Google. This is so that conventions can control which of their staff has access to the ConTroll back end and can 
revoke same by disabling or removing them from their Google For Non Profits account.

### Setting Up Google as an Oauth2 Provider
1. Make sure you are logged into your Google for Non Profits Account
2. Go to the [Google Cloud Console](https://console.cloud.google.com/).
3. Create a new project or select an existing one.
4. Navigate to the "APIs & Services" > "Credentials" page.
5. Click on "Create credentials" and select "OAuth client ID".
6. Choose "Web application" as the application type.
7. Enter a name for your application.
8. Add the following Authorized JavaScript origins:
   * https://reg.<your domain>   (if you run portal as your reg site)
   * https://<your portal URL>    (if you run portal separate from online reg)
   * https://controll.<your domain>
9. Repeat the entries in the prior step for the URL of your test instance. (you should have 4 or 6 entries depending if portal is your main reg site or not)
10. Create the following Authorized redirect URIs:
   * https://reg.<your domain>/index.php
   * https://<your portal URL>/index.php    (if you run portal separate from online reg)
   * https://controll.<your domain>/index.php
11. Repeat the entries in the prior step for the URI's of your test instance. (you should have 4 or 6 entries depending if portal is your main reg site or not)
12. Click "Create" to generate the client ID and client secret.
13. Add the client ID and client secret to the reg_secret.ini file under the "[google]" section.
14. Save the changes to the reg_secret.ini file.

### Updating Google for any changes in the URL's / URI's:
1. Make sure you are logged into your Google for Non Profits Account
2. Go to the [Google Cloud Console](https://console.cloud.google.com/).
3. Select the existing Credentials Project.
4. Navigate to the "APIs & Services" > "Credentials" page.
5. Update the Name, Origins, or URI's as appropriate.
6. CLick Save.

There is nothing to change in the configuration file in this case, the client id and secret do not change.

## Optional Oauth2 "Login With" Providers Already In ConTroll:
The following providers are currently supported by ConTroll:
* google (required, and documented above)
* amazon
* discord
* yahoo

The following are in progress of being added:
* facebook
* microsoft

Note: Apple was considered, but there is a $99/year charge to each convention to be part of their developer program. That is required to use "Login with Apple".

### Discord
To use Discord, you need to have an account on discord and use their developer portal. There is no charge to be a member of discord.  
1. Login to Discord with your account.
2. Go to the Discord Developer Portal: https://discord.com/developers/home
3. click "Applications" in left menu.
4. Click "New Application" on the top right
5. Under the "General Information" tab enter:
* Name: this is the name displayed to the user when they click "login with discord"
* Description: This is a longer description.  Please add to the description that the registration portal only accesses your email address, discord id, and 
  user name.
* Put a square avatar image in the app icon field, this is used to display what app is requesting access.
* Ignore the rest of the fields on this page
6. Click the Oauth2 item in the left menu
* copy the client id and the secret into your reg_secret.ini file in the [discord] section.  This is the only chance you will have to access the secret, 
  onse saved, it will not show it to you again.
* Fill in the same Redirects as the Google Authorized Redirect URIs
* Click Save.
7. Update the reg_secret.ini by adding a section [discord] and the fields app_name, client_id, and client_secret.

### Amazon
To use Amazon, you need to be a part of their developer program. There is no charge to be a member.  To join:
1. Create an amazon account with an email which is in the domain of your convention. We recommend using a Google Group so that all the admins can receive 
   any updates from Amazon.
2. Login to Amazon with that account.
3. Go to the [Amazon Developer Console](https://developer.amazon.com/).
4. click "Console" in upper right corner.
5. Under the amzaon section select "Login with Amazon".
6. Click Create a New Security Profile
 * Assign the profile a Name, this is the app_name you will use in the secrets file
 * Choose a description, which is longer than the name, that will be showed to the user
 * Fill in the privacy notice URL from your conventions website
 * Upload a relatively small square image for the logo.  Amazon will scale it for you.
 * Click Save.
 * A new page will open with your client id and secret.  Copy these into your secrets file.
 * Click the Manage Gear for this security profile.
 * Click "Web Settings"
 * Click "edit" if it does not open in the editor
 * Fill in the same allowed Origins as the Google Authorized Javascript Origins
 * Fill in the same allowed URLs as the Google Authorized Redirect URIs
 * Click Save.
7. Update the reg_secret.ini by adding a section [amazon] and the fields app_name, client_id, and client_secret.

### Yahoo
To use Yahoo, you need to be a member of the Yahoo Developer Network.  To join:

1. Create a Yahoo account with an email which is in the domain of your convention. We recommend using a Google Group so that all the admins can receive 
   any updates from Yahoo.
2. Login to Yahoo with that account.
3. Go to the [Yahoo Developer Network](https://developer.yahoo.com/).
4. Click "Apps"
5. Click "Create App"
6. Fill in the fields:
   1. Application Name: Name that will be presented to the user, and the app_name in reg_secret.
   2. Application Description: Longer description that will be shown to the user.
   3. Homepage URL: Javascript Authorized Site (Note: Yahoo requires an app per authorized site)
   4. Redirect URI's: You can specify multiple here, just click the + to add more lines.
   5. Click Confidential Client
   6. Click OpenID Connect Permissions
   7. Click both Email and Profile under OpenID Connect pPermissions
   8. Click Save.

Repeat this for each source URL (production and test for example).

You will not be able to edit this once created, you can delete it and create a new one. (the Update button seems to be disabled)

Copy the values above including the client_id, client_secret, and app_name into the [yahoo] section of the reg_secret.ini file for the environment (test or 
production).

