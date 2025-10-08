# System Requirements for the ConTroll™ Registration System

ConTroll is designed to run in a Linux environment using PHP and Mysql or Maria DB.

## System Software Requirements
1. PHP 8.2 (May work just fine in 8.3, untested in 8.4)
    - Requires packages
      - cgi-fpm/cgi-fcgi as required by your web server (cgi-fpm preferred)
      - curl
      - date
      - hash
      - iconv
      - intl
      - json
      - mbstring
      - mcrypt
      - mysqli (8.x or higher, which also supports MariaDB)
      - Phar
      - session
      - for debugging: Xdebug

2. Mysql 8.0 or higher (Tested with 8.0.32), Also works with MariaDB 10.11 or higher

3.  Webserver of choice
    - Tested with:
      - Apache: cgi-fpm
      - Caddy

4.  For controll (back end)
     - Google Client APIs for verifying identity (login) 
       - [Note: soon to be replaced with League Oauth Client used by portal]

5.  For Credit Card providers:
     - The top level lib directory has a PHP file cc__load_methods.php that calls the appropriate provider for credit cards based on the confiuration file.
     - The current supported providers are:
        - Square API
          - via cc_square.php
          - Uses Square PHP API V2, Code Version 43 or higher
     - Two test harness providers are provided:
        - cc_test.php: 
          - Allows selecting pass/fail at checkout time/call time to test paths.
          - cc_test simulates the square API internally but never call Square
        - cc_bypass.php: Bypasses all checking entirely, always returns true

    You can extend the list of providers by using the existing Square as a prototype and providing equivalent function call entry points for your new provider.

6.  Email MTA Access:
    ConTroll sends emails. It needs a way to send them.  It does not impliment a queue, so if a provider cannot accept the email, it is lost.
     - The tob level lib directory has a PHP file email__load_methods.php that calls the appropriate provider based on the configuration file.
     - The following email interfaces are provided:
        - email_mta.php:
          - Local host MTA via the PHP built-in function mail(). 
          - NOTE: this only support text email, to support text and html configure symphony for mta access)
        - email_awsses.php:
          - Uses the Amazon Simple Email Service API to send emails via Amazon SES
          - (This is recommended if your local MTA is rate limited)
        - email_symphony.php:
          - Uses the Sympony Composer add-on to talk to multiple typs of transports including the local mta.
          - (This is the recommended method)
        - email_file.php:
          - test harness that writes to a file in a path specified in the configuration file.
          - No actual email is sent.

7. Composer:
    - Extendes PHP with classes for credit card processor and MTA among others.
    - Install composer.phar as composer in your local bin directory
      - See: https://getcomposer.org/download/ to download and install composer on your systems
    - ConTroll distributes a composer.json and composer.lock file with each release or patch.
8. PDF Printing and Fonts
    - ConTroll uses PDF for printing basges and art show control sheets. This uses a customized PDF library that is part of the distribution. 
    We also provide the Google Freely Redistributable fonts we use as part of this library.
