<?php
    // Registration  Portal - help.php - Help pages for using the portal
    require_once('lib/base.php');
    require_once('lib/sessionManagement.php');

    global $config_vars;

    $con = get_conf('con');
    $conid = $con['id'];
    $portal_conf = get_conf('portal');
    $debug = get_conf('debug');
    $ini = get_conf('reg');
    $condata = get_con();

    if (isSessionVar('id') && isSessionVar('idType')) {
        $loginType = getSessionVar('idType');
        $loginId = getSessionVar('id');
        $expiration = getSessionVar('tokenExpiration');
        $refresh = time() > $expiration;
    }
    else {
        header('location:' . $portal_conf['portalsite']);
        exit();
    }

    if (array_key_exists('currency', $con)) {
        $currency = $con['currency'];
    }
    else {
        $currency = 'USD';
    }

    $info = getPersonInfo();

    portalPageInit('help', $info,
        /* css */ array(
                   ),
        /* js  */ array( //$cdn['luxon'],
                         'js/base.js',
                   ),
                   false // refresh
    );

    echo "<h1>Coming soon. - we need your help, please help write this section.</h1>";

    portalPageFoot();