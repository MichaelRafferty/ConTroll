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
