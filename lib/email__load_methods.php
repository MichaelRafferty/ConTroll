<?php

// load the appropriate methods for the email transport agent
function load_email_procs() {
    $emailConf = get_conf('email');
    switch ($emailConf['type']) {
    case 'aws':
    case 'awsses':
        require_once("email_awsses.php");
        break;
    case 'mta':
        require_once("email_mta.php");
        break;
    case 'file':
        require_once("email_file.php");
        break;
    case 'symfony':
        require_once("email_symfony.php");
        break;
    default:
        echo "No valid email transport agent defined\n";
        exit();
    }
}
?>