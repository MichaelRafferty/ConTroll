<?php

// load the appropriate methods for processing credit cards based on the config file
function load_cc_procs() {
    $reg = get_conf('reg');
    $cc = get_conf('cc');

    switch ($cc['type']) {
        case 'convergepay':
            require_once("lib/convergepay.php");
            break;
        case 'square':
            require_once("lib/square.php");
            break;
        case 'test':
            if(($cc['env'] != 'sandbox') || $reg['test'] != 1) {
                ajaxSuccess(array('status'=>'error','data'=>'Something thinks this is a real charge method'));
                exit();
            }
            require_once("lib/test.php");
            break;
        case 'bypass':
            if (str_contains($con['server'], '//127.0.0.1') || str_contains($con['server'], '//192.168.149.128') || $reg['test'] == 1) {
                require_once("lib/bypass.php");
                break;
            } else {
                echo "Bypass is not a valid credit card processor for this configuration\n";
                exit();
            }
        default:
            echo "No valid credit card processor defined\n";
            exit();
    }
}
