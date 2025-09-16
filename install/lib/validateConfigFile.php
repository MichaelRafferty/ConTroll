<?php

// validate remainder of the config file, except for mysql
//  options: the array returned by getoptions.
function validateConfigFile($options) : int {
    global $configData;  // this is specific to walking the configuration, it needs $configData and will need to track it's new name
    
    $sectionsFound = [
        /* 'api' => false, API is optional, and a future development */
        'atcon' => false,
        'cc' => false,
        'client' => false,
        'con' => false,
        'controll' => false,
        'debug' => false,
        'email' => false,
        'global' => false,
        'google' => false,
        'local' => false,
        'log' => false,
        'mysql' => false,
        'portal' => false,
        'reg' => false,
        'usps' => false,
        'vendor' => false,
    ];
    
    $errors = 0;
    logEcho("Validating configuration file merge of reg_conf.ini, reg_admin.ini and reg_secret.ini");

    foreach($configData as $section => $presentflag) {
        $sectionsFound[$section] = true;
        if ($section == 'mysql')
            continue; // already checked this one first

        $cfg = $configData[$section];
        $required = [];
        $warn = [];
        $filepath = [];
        $email = [];

        switch ($section) {
            case 'con':
                $required = [
                    'label' => 'the name of the convention and conid to display on the pages',
                    'conname' => 'the name of the convention less and year identifier',
                    'id' => 'the identifier of the current instance of the convention and is usually the year or the number of this convention',
                    'server' => "the URL to this registration server's online registration page",
                    'rollover_eligible' => 'the list of memCategories that are eligible to be rolled over to next year',
                ];

                $warn = ['org', 'orgabv',  'policy', 'regpolicy', 'privacypolicy', 'privacytext', 'policytext', 'remindertext', 'endtext', 'website', 'regpage', 'schedulepage',
                    'dailywebsite', 'pickupareatext', 'addlpickuptext', 'hotelwebsite', 'hotelname', 'hoteladdr', 'survey_url', 'taxRate', 'minComp', 'compLen', 'conLen',
                ];

                $email = [ 'volunteers', 'regemail', 'regadminemail', 'regconfirmcc', 'infoemail', 'refundemail', 'feedbackemail', ];
                break;

            case 'debug':
                $required = [
                    'google' => '0 for not debugging google authentication validation and 1 for debugging issues with google authentication',
                ];
                break;

            case 'reg':
                $required = [
                    'https' => 'should always be 1 to redirect to the secure server. and only should be zero when testing servers prior to the SSL certificate being applied',
                    'test' => '0 for the production server and 1 for any test servers',
                    'regpage' => 'the URL where when registration will open will be posted.',
                    'open' => "0 when registration is not open and 1 when it's open",
                    'close' => '0 when registration is allowed to be open, and 1 when registration is closed for being too close to the convention or the convention is over',
                    'suspended' => '0 when registration is available and 1 when registration is temporarily closed, and the suspendreason is then shown to inform users',
                    'cancelled' => '0 in normal cases, 1 when the convention has had to be cancelled',
                    'server' => 'URL for the registration server used for follow-on pages and redirects',
                    'logoimage' => 'name of the logo file to display on the top of the page, it should be in onlinereg/images',
                ];
                $warn = [
                    'suspendreason',
                    'onsiteopen'
                ];
                break;

            case 'atcon':
                $required = [
                    'discount' => "who is allowed to offer discounts in the atcon system.  Valid values are explained in the sample configuration file",
                ];
                $filepath = [
                    'badgeps',
                ];
                break;

            case 'portal':
                $required = [
                    'https' => 'should always be 1 to redirect to the secure server. and only should be zero when testing servers prior to the SSL certificate being applied',
                    'test' => '0 for the production server and 1 for any test servers',
                    'open' => "0 when registration is not open and 1 when it's open",
                    'close' => '0 when registration is allowed to be open, and 1 when registration is closed for being too close to the convention or the convention is over',
                    'suspended' => '0 when registration is available and 1 when registration is temporarily closed, and the suspendreason is then shown to inform users',
                    'portalsite' => 'URL to login page (the default index.php location is all you need)',
                    'logoimage' => 'name of the logo file to display on the top of the page, it should be in onlinereg/images',
                ];
                $warn = [
                    'suspendreason',
                    'cancelled' => '0 in normal cases, 1 when the convention has had to be cancelled',
                ];
                break;

            case 'vendor':
                $required = [
                    'https' => 'should always be 1 to redirect to the secure server. and only should be zero when testing servers prior to the SSL certificate being applied',
                    'test' => '0 for the production server and 1 for any test servers',
                    'vendors' => 'the email address to for the from and cc: of the emails sent by vendor.',
                    'open' => "0 when registration is not open and 1 when it's open",
                    'vendorsite' => 'URL for the vendor portal used for follow-on pages and redirects',
                    'logoimage' => 'name of the logo file to display on the top of the page, it should be in onlinereg/images',
                    ];

                $warn = [ 'taxidextra' ];

                $email = [ 'vendors', 'artshow', 'dealer' ];
                break;

            case 'client':
                $file = [ 'path'];
                break;

            case 'google':
                $required = [
                    'app_name' => 'Name to display in the google login popup',
                    'redirect_base' => 'URL to prefix page to return to from google authentication',
                    'client_id' => 'Client id from the google authentication json file',
                    'client_secret' => 'Client secret from the google authentication json file'
                ];

                $file = [ 'json' ];
                break;

            case 'cc':
                $required = [
                    'type' => 'Which credit card authentication method to use, must be one of the ones in the lib directory, eg. square, bypass, test'
                ];

                if (array_key_exists('type', $cfg)) {
                    switch ($cfg['type']) {
                        case 'square':
                            $required['appid'] = 'Square application ID from the Square Developers Portal';
                            $required['token'] = 'Square authentication token from the Square Developers Portal';
                            $required['env'] = "either 'sandbox' for test=1 or 'production for test=0";
                            $required['location'] = 'Square location ID from the Square Developers Portal';
                            $required['apiversion'] = 'Square api version, from Composer json';
                            $required['webpaysdk'] = 'Square CDN URL to web payment SDK, usually https://web.squarecdn.com/v1/square.js';
                            break;
                    }
                }
                break;

            case 'log':
                $filepath = [ 'web', 'reg', 'artshow', 'vendors', 'cancel', 'db' ];
            break;

            case 'email':
                $required = [
                    'type' => "the email server type, one of 'aws', 'mta', 'symfony', 'file'",
                    'batchsize' => "number of emails to send before delaying for email server to catch up",
                    'delay' => 'seconds to delay between batches, if no delay is needed, use 0'
                    ];

                 if (array_key_exists('type', $cfg)) {
                     switch ($cfg['type']) {
                         case 'aws':
                             $required['aws_access_key_id'] = 'Access Key ID from Amazon SES Portal';
                             $required['aws_secret_access_key'] = 'Access Secret Key from Amazon SES Portal';
                             $required['username'] = 'Credential username from Amazon SES Portal';
                             $required['region'] = 'AWS SES Region from Amazon SES Portal';
                             $required['version'] = 'AWS SES API Version from Amazon SES Portal';
                             break;

                         case 'symfony':
                             $required['transport'] = 'Symfony transport type, supported are smtp, ses+smtp, ses+https';
                             $required['host'] = "FullyQualfiedDomainName:port of transport potentially with optional arguments, such as 'localhost:25?verify_peer=0'";

                             $warn[] = 'username';
                             $warn[] = 'password';
                             break;
                     }
                 }
                 break;

            case 'artshow':
                 $required = [
                     'https' => 'should always be 1 to redirect to the secure server. and only should be zero when testing servers prior to the SSL certificate being applied',
                     'test' => '0 for the production server and 1 for any test servers',
                     'open' => "0 when registration is not open and 1 when it's open",
                     'close' => '0 when registration is allowed to be open, and 1 when registration is closed for being too close to the convention or the convention is over',
                     'suspended' => '0 when registration is available and 1 when registration is temporarily closed, and the suspendreason is then shown to inform users',
                     'url' => 'URL to the art show portal',
                     ];

                 $warn = ['max_failures'];
                break;

            case 'control':
                $warn = ['clubname'];
                break;
        }
        logEcho("Validating Configuration Section [$section]");

        foreach ($required as $key => $help) {
            if (array_key_exists($key, $cfg)) {
                if (($cfg[$key] === null) || (mb_strlen($cfg[$key]) < 1)) {
                    logEcho("$key cannot be empty, its $help");
                    $errors++;
                }
            } else {
                logEcho("$key is missing, it is required and is $help");
                $errors++;
            }
        }

        foreach ($warn as $key) {
            if (array_key_exists($key, $cfg)) {
                if ($cfg[$key] === null || mb_strlen($cfg[$key] < 1)) {
                    logEcho("Warning: $key is empty");
                }
            } else {
                logEcho("Warning: $key is missing");
            }
        }

        foreach ($filepath as $key) {
            if (array_key_exists($key, $cfg)) {
                if ($cfg[$key] === null || mb_strlen($cfg[$key]) < 1) {
                    logEcho("Warning: $key is empty, and must point the a valid absolute file path");
                } else {
                    if (!is_readable($cfg[$key])) {
                        logEcho("The absolute file path for $key, " . $cfg[$key] . ", does not exist");
                        $errors++;
                    }
                }
            } else {
                logEcho("Warning: $key is missing, and must point the a valid absolute file path");
            }
        }

        foreach ($email as $key) {
            if (array_key_exists($key, $cfg)) {
                if ($cfg[$key] === null || mb_strlen($cfg[$key]) < 1) {
                    logEcho("Warning: $key is empty, and should be a valid email address");
                } else {
                    if (!filter_var($cfg[$key], FILTER_VALIDATE_EMAIL)) {
                        logEcho("$key (" . $cfg[$key] . ") is not a valid email address");
                        $errors++;
                    }
                }
            } else {
                logEcho("Warning: $key is missing, and should be a valid email address");
            }
        }

    }

    foreach($sectionsFound as $section => $found) {
        if ($found)
            continue;
        logEcho("Configuration Section [$section] was not found");
        $errors++;
    }

    return $errors;
}
